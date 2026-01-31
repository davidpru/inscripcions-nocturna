<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Administrador;
use Illuminate\Support\Facades\Hash;

class AdministradorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@nocturna.cat');
        $password = env('ADMIN_PASSWORD', 'NocturnaAdmin2026!');

        // Comprobar si ya existe
        $admin = Administrador::where('email', $email)->first();

        if ($admin) {
            // Actualizar si ya existe
            $admin->update([
                'password' => Hash::make($password),
                'activo' => true,
            ]);
            $this->command->info("Administrador actualitzat: {$email}");
        } else {
            // Crear nuevo
            Administrador::create([
                'nombre' => 'Administrador',
                'email' => $email,
                'password' => Hash::make($password),
                'tipo' => 'super_admin',
                'activo' => true,
            ]);
            $this->command->info("Administrador creat: {$email}");
        }
    }
}
