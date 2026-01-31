<?php

namespace Database\Seeders;

use App\Models\Administrador;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdministradoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin principal
        Administrador::updateOrCreate(
            ['email' => 'admin@nocturna.cat'],
            [
                'nombre' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'tipo' => 'super_admin',
                'activo' => true,
            ]
        );

        // Admin de gestió
        Administrador::updateOrCreate(
            ['email' => 'gestio@nocturna.cat'],
            [
                'nombre' => 'Admin Gestió',
                'password' => Hash::make('admin123'),
                'tipo' => 'admin',
                'activo' => true,
            ]
        );

        // Editor
        Administrador::updateOrCreate(
            ['email' => 'editor@nocturna.cat'],
            [
                'nombre' => 'Editor',
                'password' => Hash::make('admin123'),
                'tipo' => 'editor',
                'activo' => true,
            ]
        );
    }
}
