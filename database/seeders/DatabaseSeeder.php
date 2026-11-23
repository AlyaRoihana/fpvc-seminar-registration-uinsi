<?php

namespace Database\Seeders;

use App\Models\Seminar;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $panitia = User::firstOrCreate(
            ['email' => 'panitia@uinsi.test'],
            [
                'name' => 'Panitia UINSI',
                'password' => Hash::make('password'),
                'role' => 'panitia',
            ]
        );

        User::firstOrCreate(
            ['email' => 'mahasiswa@uinsi.test'],
            [
                'name' => 'Mahasiswa UINSI',
                'password' => Hash::make('password'),
                'role' => 'mahasiswa',
            ]
        );

        Seminar::firstOrCreate(
            ['title' => 'Seminar Moderasi Beragama'],
            [
                'speaker' => 'Narasumber UINSI',
                'seminar_date' => now()->addDays(7)->setTime(9, 0),
                'location' => 'Aula UINSI Samarinda',
                'capacity' => 5,
                'registered_count' => 0,
                'created_by' => $panitia->id,
            ]
        );
    }
}
