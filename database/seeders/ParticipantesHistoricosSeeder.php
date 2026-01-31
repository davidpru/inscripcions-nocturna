<?php

namespace Database\Seeders;

use App\Models\Participante;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParticipantesHistoricosSeeder extends Seeder
{
    public function run(): void
    {
        $participantesJson = file_get_contents('/tmp/participantes_export.json');
        $participantes = json_decode($participantesJson, true);

        $importados = 0;
        $duplicados = 0;

        foreach ($participantes as $participante) {
            // Verificar si ya existe por DNI y nombre
            $existe = Participante::where('dni', $participante['dni'])
                ->where('nombre', $participante['nombre'])
                ->where('apellidos', $participante['apellidos'])
                ->exists();

            if (!$existe) {
                Participante::create($participante);
                $importados++;
            } else {
                $duplicados++;
            }
        }

        $this->command->info("Importados: $importados participantes nuevos.");
        $this->command->info("Omitidos: $duplicados participantes duplicados.");
    }
}
