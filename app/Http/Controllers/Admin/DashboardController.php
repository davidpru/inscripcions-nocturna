<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edicion;
use App\Models\Inscripcion;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $edicionActual = Edicion::where('estado', 'abierta')
            ->orderBy('anio', 'desc')
            ->first();

        $stats = [
            'totalInscripciones' => Inscripcion::count(),
            'inscripcionesPagadas' => Inscripcion::where('estado_pago', 'pagado')->count(),
            'inscripcionesPendientes' => Inscripcion::where('estado_pago', 'pendiente')->count(),
            'totalRecaudado' => Inscripcion::where('estado_pago', 'pagado')->sum('precio_total'),
            'edicionActual' => $edicionActual ? [
                'id' => $edicionActual->id,
                'anio' => $edicionActual->anio,
                'inscritos' => Inscripcion::where('edicion_id', $edicionActual->id)
                    ->where('estado_pago', 'pagado')
                    ->count(),
                'limite' => $edicionActual->limite_inscritos,
            ] : null,
        ];

        // Datos para la gráfica de inscripciones por día
        $inscripcionesPorDia = [];
        if ($edicionActual) {
            // Obtener inscripciones agrupadas por día
            $inscripciones = Inscripcion::where('edicion_id', $edicionActual->id)
                ->where('estado_pago', 'pagado')
                ->selectRaw('DATE(fecha_pago) as fecha, COUNT(*) as total')
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->get()
                ->pluck('total', 'fecha')
                ->toArray();

            // Si hay inscripciones, rellenar todos los días desde la primera venta hasta el evento
            if (!empty($inscripciones)) {
                $primeraFecha = min(array_keys($inscripciones));
                $fechaEvento = $edicionActual->fecha_evento;

                $fechaInicio = new \DateTime($primeraFecha);
                $fechaFin = new \DateTime($fechaEvento);

                $inscripcionesPorDia = [];
                while ($fechaInicio <= $fechaFin) {
                    $fechaStr = $fechaInicio->format('Y-m-d');
                    $inscripcionesPorDia[] = [
                        'fecha' => $fechaStr,
                        'total' => $inscripciones[$fechaStr] ?? 0,
                    ];
                    $fechaInicio->modify('+1 day');
                }
            }
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'inscripcionesPorDia' => $inscripcionesPorDia,
        ]);
    }
}