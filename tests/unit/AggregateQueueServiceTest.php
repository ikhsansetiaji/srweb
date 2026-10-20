<?php

use App\Services\AggregateQueueService;
use CodeIgniter\Test\CIUnitTestCase;

final class AggregateQueueServiceTest extends CIUnitTestCase
{
    public function testRequestCountQualificationThreshold(): void
    {
        $this->assertFalse(AggregateQueueService::isQualifiedRequestCount(5));
        $this->assertTrue(AggregateQueueService::isQualifiedRequestCount(6));
    }

    public function testHigherAggregateTotalWinsOverLowerTotal(): void
    {
        $candidates = [
            [
                'track_key' => 'spotify-a',
                'total_nominal' => 60000,
                'request_count' => 6,
                'reached_at' => '2026-10-13 11:00:00',
                'first_requested_at' => '2026-10-13 10:00:00',
            ],
            [
                'track_key' => 'spotify-b',
                'total_nominal' => 50000,
                'request_count' => 7,
                'reached_at' => '2026-10-13 09:30:00',
                'first_requested_at' => '2026-10-13 09:00:00',
            ],
        ];

        $picked = AggregateQueueService::pickTopCandidate($candidates);

        $this->assertSame('spotify-a', $picked['track_key']);
    }

    public function testEarlierReachedAtWinsWhenTotalsMatch(): void
    {
        $candidates = [
            [
                'track_key' => 'spotify-a',
                'total_nominal' => 80000,
                'request_count' => 6,
                'reached_at' => '2026-10-13 11:15:00',
                'first_requested_at' => '2026-10-13 10:00:00',
            ],
            [
                'track_key' => 'spotify-c',
                'total_nominal' => 80000,
                'request_count' => 7,
                'reached_at' => '2026-10-13 11:00:00',
                'first_requested_at' => '2026-10-13 09:45:00',
            ],
        ];

        $picked = AggregateQueueService::pickTopCandidate($candidates);

        $this->assertSame('spotify-c', $picked['track_key']);
    }

    public function testRequestCountBreaksRemainingTie(): void
    {
        $candidates = [
            [
                'track_key' => 'spotify-a',
                'total_nominal' => 90000,
                'request_count' => 6,
                'reached_at' => '2026-10-13 11:00:00',
                'first_requested_at' => '2026-10-13 10:00:00',
            ],
            [
                'track_key' => 'spotify-z',
                'total_nominal' => 90000,
                'request_count' => 8,
                'reached_at' => '2026-10-13 11:00:00',
                'first_requested_at' => '2026-10-13 09:30:00',
            ],
        ];

        $picked = AggregateQueueService::pickTopCandidate($candidates);

        $this->assertSame('spotify-z', $picked['track_key']);
    }

    public function testEmptyCandidateListReturnsNull(): void
    {
        $this->assertNull(AggregateQueueService::pickTopCandidate([]));
    }
}
