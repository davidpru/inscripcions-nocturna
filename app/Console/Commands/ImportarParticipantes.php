<?php

namespace App\Console\Commands;

use App\Models\Participante;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportarParticipantes extends Command
{
    protected $signature = 'participantes:importar {archivo=database/seeders/inscritos-2025.csv}';
    protected $description = 'Importa participantes históricos desde un archivo CSV';

    public function handle()
    {
        $archivo = base_path($this->argument('archivo'));

        if (!file_exists($archivo)) {
            $this->error("El archivo no existe: {$archivo}");
            return 1;
        }

        $this->info("Importando participantes desde: {$archivo}");

        $handle = fopen($archivo, 'r');
        
        // Leer cabecera
        $cabecera = fgetcsv($handle, 0, ';');
        
        $importados = 0;
        $actualizados = 0;
        $errores = 0;

        DB::beginTransaction();

        try {
            while (($fila = fgetcsv($handle, 0, ';')) !== false) {
                try {
                    // Mapear columnas del CSV
                    $datos = array_combine($cabecera, $fila);

                    // Validar DNI
                    $dni = strtoupper(trim($datos['DNI'] ?? ''));
                    if (empty($dni)) {
                        $errores++;
                        continue;
                    }

                    // Extraer datos
                    $nombre = trim($datos['Nom'] ?? '');
                    $apellidos = trim($datos['Cognoms'] ?? '');
                    $email = trim($datos['Email'] ?? '');
                    $telefono = trim($datos['Telèfon'] ?? '');
                    $direccion = trim($datos['Adreça'] ?? '');
                    $codigoPostal = trim($datos['CP'] ?? '');
                    $poblacion = trim($datos['Població'] ?? '');
                    $provincia = trim($datos['Província'] ?? '');
                    
                    // Convertir género (1 = masculino, 2 = femenino)
                    $generoNum = trim($datos['Gènere'] ?? '1');
                    $genero = $generoNum === '2' ? 'femenino' : 'masculino';
                    
                    // Convertir fecha de nacimiento (dd/mm/yyyy a yyyy-mm-dd)
                    $fechaNacimiento = $this->convertirFecha($datos['Data naixement'] ?? '');
                    
                    // Datos deportivos
                    $numeroLicencia = trim($datos['Num. llicència'] ?? '');
                    $club = trim($datos['Club'] ?? '');

                    // Validar datos mínimos
                    if (empty($nombre) || empty($apellidos)) {
                        $this->warn("Saltando DNI {$dni}: faltan nombre o apellidos");
                        $errores++;
                        continue;
                    }

                    // Crear o actualizar participante
                    $participante = Participante::updateOrCreate(
                        ['dni' => $dni],
                        [
                            'nombre' => $nombre,
                            'apellidos' => $apellidos,
                            'genero' => $genero,
                            'fecha_nacimiento' => $fechaNacimiento,
                            'telefono' => $telefono ?: null,
                            'email' => $email ?: null,
                            'direccion' => $direccion ?: null,
                            'codigo_postal' => $codigoPostal ?: null,
                            'poblacion' => $poblacion ?: null,
                            'provincia' => $provincia ?: null,
                            'club' => $club ?: null,
                            'numero_licencia' => $numeroLicencia ?: null,
                        ]
                    );

                    if ($participante->wasRecentlyCreated) {
                        $importados++;
                    } else {
                        $actualizados++;
                    }

                } catch (\Exception $e) {
                    $this->error("Error procesando fila: " . $e->getMessage());
                    $errores++;
                }
            }

            DB::commit();
            fclose($handle);

            $this->newLine();
            $this->info("✓ Importación completada:");
            $this->info("  - Nuevos participantes: {$importados}");
            $this->info("  - Participantes actualizados: {$actualizados}");
            
            if ($errores > 0) {
                $this->warn("  - Errores: {$errores}");
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            $this->error("Error en la importación: " . $e->getMessage());
            return 1;
        }
    }

    private function convertirFecha(?string $fecha): ?string
    {
        if (empty($fecha)) {
            return null;
        }

        try {
            // Formato esperado: dd/mm/yyyy
            $partes = explode('/', $fecha);
            if (count($partes) === 3) {
                return sprintf('%04d-%02d-%02d', $partes[2], $partes[1], $partes[0]);
            }
        } catch (\Exception $e) {
            // Ignorar errores de conversión
        }

        return null;
    }
}
