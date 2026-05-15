<?php

namespace App\Services;

use App\Models\Edicion;
use App\Models\Inscripcion;
use Illuminate\Support\Facades\DB;

class DorsalService
{
    private const ESTADOS_ACTIVOS = ['pagado', 'invitado', 'compromiso'];

    /**
     * Asigna dorsales a una edición:
     * - Fuerza dorsal 1 al dorsal_primer_masculino_id (libera al anterior holder si distinto)
     * - Fuerza dorsal 2 al dorsal_primera_femenina_id (igual)
     * - Resto sin dorsal: orden created_at ASC, recibe siguiente disponible
     * - Respeta dorsales ya asignados (no reasigna)
     */
    public function asignar(Edicion $edicion): array
    {
        return DB::transaction(function () use ($edicion) {
            $cambios = ['reservado_1' => null, 'reservado_2' => null, 'asignados_nuevos' => 0];

            $this->forzarReservado($edicion, $edicion->dorsal_primer_masculino_id, 1, $cambios, 'reservado_1');
            $this->forzarReservado($edicion, $edicion->dorsal_primera_femenina_id, 2, $cambios, 'reservado_2');

            $usados = Inscripcion::where('edicion_id', $edicion->id)
                ->whereNotNull('numero_dorsal')
                ->pluck('numero_dorsal')
                ->all();
            $usados = array_flip($usados);

            $pendientes = Inscripcion::where('edicion_id', $edicion->id)
                ->whereIn('estado_pago', self::ESTADOS_ACTIVOS)
                ->whereNull('numero_dorsal')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $next = 1;
            foreach ($pendientes as $insc) {
                while (isset($usados[$next])) {
                    $next++;
                }
                $insc->numero_dorsal = $next;
                $insc->save();
                $usados[$next] = true;
                $cambios['asignados_nuevos']++;
                $next++;
            }

            return $cambios;
        });
    }

    /**
     * Asigna siguiente dorsal disponible a una inscripción (max+1 atómico).
     * Idempotente: si ya tiene dorsal, no toca.
     */
    public function asignarSiguiente(Inscripcion $inscripcion): ?int
    {
        if ($inscripcion->numero_dorsal !== null) {
            return $inscripcion->numero_dorsal;
        }
        if (!in_array($inscripcion->estado_pago, self::ESTADOS_ACTIVOS, true)) {
            return null;
        }

        return DB::transaction(function () use ($inscripcion) {
            $max = (int) Inscripcion::where('edicion_id', $inscripcion->edicion_id)
                ->lockForUpdate()
                ->max('numero_dorsal');
            $siguiente = $max + 1;
            $inscripcion->numero_dorsal = $siguiente;
            $inscripcion->save();
            return $siguiente;
        });
    }

    private function forzarReservado(Edicion $edicion, ?int $inscripcionId, int $dorsal, array &$cambios, string $clave): void
    {
        if (!$inscripcionId) {
            return;
        }

        $insc = Inscripcion::where('id', $inscripcionId)
            ->where('edicion_id', $edicion->id)
            ->whereIn('estado_pago', self::ESTADOS_ACTIVOS)
            ->first();

        if (!$insc) {
            return;
        }

        if ($insc->numero_dorsal === $dorsal) {
            $cambios[$clave] = $insc->id;
            return;
        }

        $actual = Inscripcion::where('edicion_id', $edicion->id)
            ->where('numero_dorsal', $dorsal)
            ->first();

        if ($actual && $actual->id !== $insc->id) {
            $actual->numero_dorsal = null;
            $actual->save();
        }

        $insc->numero_dorsal = $dorsal;
        $insc->save();
        $cambios[$clave] = $insc->id;
    }
}
