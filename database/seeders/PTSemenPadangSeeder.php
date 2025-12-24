<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PTSemenPadangSeeder extends Seeder
{
    /**
     * Seed 5 Users untuk PT Semen Padang
     * Unit Perencanaan dan Pengawasan Tambang
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin Sistem',
                'email' => 'admin@semenpadang.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'department' => 'Unit Perencanaan dan Pengawasan Tambang',
            ],
            [
                'name' => 'Supervisor Tambang',
                'email' => 'supervisor@semenpadang.com',
                'password' => Hash::make('password'),
                'role' => 'supervisor',
                'department' => 'Pengawasan Tambang',
            ],
            [
                'name' => 'Operator Produksi 1',
                'email' => 'user1@semenpadang.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'department' => 'Operasional Tambang',
            ],
            [
                'name' => 'Operator Produksi 2',
                'email' => 'user2@semenpadang.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'department' => 'Operasional Tambang',
            ],
            [
                'name' => 'Operator Produksi 3',
                'email' => 'user3@semenpadang.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'department' => 'Operasional Tambang',
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']], // Check by email
                $userData // Update or create with this data
            );
        }

        $this->command->info('✅ 5 Users PT Semen Padang berhasil dibuat!');
        $this->command->info('');
        $this->command->info('Login Credentials:');
        $this->command->info('==================');
        $this->command->info('Admin     : admin@semenpadang.com / password');
        $this->command->info('Supervisor: supervisor@semenpadang.com / password');
        $this->command->info('User 1    : user1@semenpadang.com / password');
        $this->command->info('User 2    : user2@semenpadang.com / password');
        $this->command->info('User 3    : user3@semenpadang.com / password');
    }
}
