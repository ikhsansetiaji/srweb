<?php

namespace App\Services;

use Config\Database;

class AggregateQueueService
{
    public static function isQualifiedRequestCount(int $requestCount): bool
    {
        return $requestCount > 5;
    }

    /**
     * Sort aggregated candidates deterministically.
     *
     * @param array<int, array<string, mixed>> $candidates
     * @return array<string, mixed>|null
     */
    public static function pickTopCandidate(array $candidates): ?array
    {
        if (empty($candidates)) {
            return null;
        }

        usort($candidates, static function (array $left, array $right): int {
            $leftTotal = (int) ($left['total_nominal'] ?? 0);
            $rightTotal = (int) ($right['total_nominal'] ?? 0);
            if ($leftTotal !== $rightTotal) {
                return $rightTotal <=> $leftTotal;
            }

            $leftReached = strtotime((string) ($left['reached_at'] ?? '')) ?: 0;
            $rightReached = strtotime((string) ($right['reached_at'] ?? '')) ?: 0;
            if ($leftReached !== $rightReached) {
                return $leftReached <=> $rightReached;
            }

            $leftCount = (int) ($left['request_count'] ?? 0);
            $rightCount = (int) ($right['request_count'] ?? 0);
            if ($leftCount !== $rightCount) {
                return $rightCount <=> $leftCount;
            }

            $leftFirst = strtotime((string) ($left['first_requested_at'] ?? '')) ?: 0;
            $rightFirst = strtotime((string) ($right['first_requested_at'] ?? '')) ?: 0;
            if ($leftFirst !== $rightFirst) {
                return $leftFirst <=> $rightFirst;
            }

            return strcmp((string) ($left['track_key'] ?? ''), (string) ($right['track_key'] ?? ''));
        });

        return $candidates[0];
    }

    /**
     * Get next request from aggregated songs.
     * Only songs with > 5 valid paid requests qualify.
     */
    public function getNextSong(int $cafeId): ?array
    {
        $db = Database::connect();

        $qualifiedSongs = $db->query(
            <<<SQL
WITH valid_requests AS (
    SELECT
        sr.id,
        sr.cafe_id,
        sr.song_id,
        sr.requested_at,
        COALESCE(p.amount, sr.nominal) AS amount,
        COALESCE(NULLIF(s.api_song_id, ''), s.id::text) AS track_key,
        s.title,
        s.artist,
        s.duration,
        s.thumbnail,
        s.spotify_url,
        s.preview_url
    FROM song_requests sr
    INNER JOIN payments p
        ON p.request_id = sr.id
       AND p.payment_status = 'success'
    INNER JOIN songs s
        ON s.id = sr.song_id
    WHERE sr.cafe_id = ?
      AND sr.status = 'waiting'
),
song_stats AS (
    SELECT
        track_key,
        MIN(song_id) AS song_id,
        MIN(title) AS title,
        MIN(artist) AS artist,
        MIN(duration) AS duration,
        MIN(thumbnail) AS thumbnail,
        MIN(spotify_url) AS spotify_url,
        MIN(preview_url) AS preview_url,
        COUNT(*) AS request_count,
        SUM(amount) AS total_nominal,
        MAX(requested_at) AS reached_at,
        MIN(requested_at) AS first_requested_at
    FROM valid_requests
    GROUP BY track_key
    HAVING COUNT(*) > 5
)
SELECT *
FROM song_stats
SQL,
            [$cafeId]
        )->getResultArray();

        $qualifiedSong = self::pickTopCandidate($qualifiedSongs);

        if (empty($qualifiedSong) || empty($qualifiedSong['track_key'])) {
            return null;
        }

        $request = $db->query(
            <<<SQL
SELECT sr.*, s.title, s.artist, s.duration, s.thumbnail, s.spotify_url, s.preview_url, s.api_song_id
FROM song_requests sr
INNER JOIN payments p
    ON p.request_id = sr.id
   AND p.payment_status = 'success'
INNER JOIN songs s
    ON s.id = sr.song_id
WHERE sr.cafe_id = ?
  AND sr.status = 'waiting'
  AND COALESCE(NULLIF(s.api_song_id, ''), s.id::text) = ?
ORDER BY sr.requested_at ASC, sr.id ASC
LIMIT 1
SQL,
            [$cafeId, $qualifiedSong['track_key']]
        )->getRowArray();

        return $request ?: null;
    }
}
