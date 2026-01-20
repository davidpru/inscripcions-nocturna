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

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
