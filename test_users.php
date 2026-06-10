<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootTest($paths);

$db = \Config\Database::connect();
$query = $db->query('SELECT id, name, email, role, is_verified, is_active FROM users');
foreach ($query->getResultArray() as $row) {
    print_r($row);
}
