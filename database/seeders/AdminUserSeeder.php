<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea el usuario administrador inicial.
     */
    public function run(): void
    {
        // Crear admin por defecto solo si no existe ningún admin
        if (User::where('is_admin', true)->count() === 0) {
            User::create([
                'name' => 'Administrador',
                'email' => env('ADMIN_EMAIL', 'admin@nocturna.cat'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'NocturnaAdmin2026!')),
                'is_admin' => true,
                'activo' => true,
            ]);

            $this->command->info('Usuari administrador creat: admin@nocturna.cat');
        } else {
            $this->command->info('Ja existeix almenys un usuari administrador.');
        }
    }
}
