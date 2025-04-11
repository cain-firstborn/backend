<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::query()
            ->insert(
                values: [
                    [
                        'name'       => 'Aleksandar Žeželj',
                        'email'      => 'alex@cainfirstborn.com',
                        'password'   => '$2y$12$M2UWN4/k1Yk10FuhAflHFeGch9cSOkVAvIuXwbPvZu8Ckp6Imj3Wa',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'name'       => 'Admin',
                        'email'      => 'admin@cainfirstborn.com',
                        'password'   => '$2y$12$M2UWN4/k1Yk10FuhAflHFeGch9cSOkVAvIuXwbPvZu8Ckp6Imj3Wa',
                        'created_at' => now(),
                        'updated_at' => now(),

                    ],
                    [
                        'name'       => 'Support',
                        'email'      => config('mail.from.address'),
                        'password'   => '$2y$12$M2UWN4/k1Yk10FuhAflHFeGch9cSOkVAvIuXwbPvZu8Ckp6Imj3Wa',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]
            );
    }
}
