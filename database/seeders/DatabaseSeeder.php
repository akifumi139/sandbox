<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::factory()->create([
            'name' => 'TestA(管理者)',
            'email' => 'a@example.com',
        ]);

        User::factory()->create([
            'name' => 'TestB(一般ユーザー)',
            'email' => 'b@example.com',
        ]);

        User::factory()->create([
            'name' => 'TestC(一般ユーザー)',
            'email' => 'c@example.com',
        ]);

        User::factory()->create([
            'name' => 'TestD(一般ユーザー)',
            'email' => 'd@example.com',
        ]);

        User::factory()->create([
            'name' => 'TestE(一般ユーザー)',
            'email' => 'e@example.com',
        ]);

    }
}
