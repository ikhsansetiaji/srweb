<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $userModel = new UserModel();

        // Check if user already exists
        if ($userModel->countAllResults() > 0) {
            echo "Users already exist, skipping...\n";
            return;
        }

        $data = [
            // Superadmin
            [
                'name' => 'Superadmin',
                'email' => 'superadmin@songrequest.id',
                'password' => password_hash('SuperAdmin@123', PASSWORD_BCRYPT, ['cost' => 12]),
                'role' => 'superadmin',
                'is_verified' => true,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            // Admin Cafe
            [
                'name' => 'Admin Cafe',
                'email' => 'admin@cafe.id',
                'password' => password_hash('AdminCafe@123', PASSWORD_BCRYPT, ['cost' => 12]),
                'role' => 'admin',
                'is_verified' => true,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            // Regular User
            [
                'name' => 'Test User',
                'email' => 'user@songrequest.id',
                'password' => password_hash('TestUser@123', PASSWORD_BCRYPT, ['cost' => 12]),
                'role' => 'user',
                'is_verified' => true,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $userModel->insertBatch($data);

        echo "Users seeded successfully!\n";
        echo "Test Credentials:\n";
        echo "- Superadmin: superadmin@songrequest.id / SuperAdmin@123\n";
        echo "- Admin: admin@cafe.id / AdminCafe@123\n";
        echo "- User: user@songrequest.id / TestUser@123\n";
    }
}

