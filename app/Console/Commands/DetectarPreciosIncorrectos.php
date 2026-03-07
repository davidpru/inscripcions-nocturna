<?php

namespace App\Console\Commands;

use App\Models\Inscripcion;
use App\Services\TarifaService;
use Illuminate\Console\Command;

class DetectarPreciosIncorrectos extends Command
{
    protected $signature = 'inscripciones:detectar-precios-incorrectos {--edicion= : ID de la edición (por defecto la activa)}';
    protected $description = 'Detecta inscripciones cuyo precio guardado no coincide con el precio que les correspondía';

    public function handle(): int
    {
        $edicionId = $this->option('edicion');

        $query = Inscripcion::with(['participante', 'edicion', 'cupon'])
            ->where('estado_pago', 'pagado');

        if ($edicionId) {
            $query->where('edicion_id', $edicionId);
        } else {
            $query->whereHas('edicion', fn ($q) => $q->where('activa', true));
        }

        $inscripciones = $query->get();
        $tarifaService = new TarifaService();
        $incorrectas = [];

        foreach ($inscripciones as $inscripcion) {
            $resultado = $tarifaService->calcularPrecio(
                $inscripcion->edicion,
                (bool) $inscripcion->es_socio_uec,
                (bool) $inscripcion->esta_federado,
                (bool) $inscripcion->necesita_autobus,
                (bool) $inscripcion->seguro_anulacion
            );

            $precioEsperado = $resultado['precio_total'];

            // Aplicar descuento cupón si existe
            if ($inscripcion->descuento_cupon > 0) {
                $precioEsperado = max(0, $precioEsperado - $inscripcion->descuento_cupon);
            }

            $precioGuardado = (float) $inscripcion->precio_total;

            if (abs($precioGuardado - $precioEsperado) >= 0.01) {
                $incorrectas[] = [
                    'ID' => $inscripcion->id,
                    'Nom' => $inscripcion->participante->nombre . ' ' . $inscripcion->participante->apellidos,
                    'Guardat' => number_format($precioGuardado, 2) . '€',
                    'Esperat' => number_format($precioEsperado, 2) . '€',
                    'Diff' => number_format($precioGuardado - $precioEsperado, 2) . '€',
                    'Soci' => $inscripcion->es_socio_uec ? 'Sí' : 'No',
                    'Federat' => $inscripcion->esta_federado ? 'Sí' : 'No',
                    'Data' => $inscripcion->created_at->format('d/m/Y H:i'),
                ];
            }
        }

        if (empty($incorrectas)) {
            $this->info('✅ Cap inscripció amb preu incorrecte trobada.');
            return 0;
        }

        $this->warn("⚠️  Trobades " . count($incorrectas) . " inscripcions amb preu incorrecte:");
        $this->newLine();
        $this->table(
            ['ID', 'Nom', 'Guardat', 'Esperat', 'Diff', 'Soci', 'Federat', 'Data'],
            $incorrectas
        );

        $totalDiferencia = array_sum(array_map(fn ($i) => (float) str_replace('€', '', $i['Diff']), $incorrectas));
        $this->newLine();
        $this->info("Total diferència: " . number_format($totalDiferencia, 2) . '€');

        return 0;
    }
}