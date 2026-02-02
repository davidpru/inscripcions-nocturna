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

        // Estadísticas detalladas por tipo de tarifa
        $estadistiques = $this->calcularEstadistiques($edicionActual);

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
            'estadistiques' => $estadistiques,
        ]);
    }

    private function calcularEstadistiques(?Edicion $edicion): array
    {
        if (!$edicion) {
            return [];
        }

        $baseQuery = fn() => Inscripcion::where('edicion_id', $edicion->id)
            ->where('estado_pago', 'pagado');

        // Precios de referencia (usamos normal por defecto)
        $precioBus = $edicion->precio_autobus_normal ?? 12.50;
        $precioSeguro = $edicion->precio_seguro ?? 7.30;

        // Total inscrits
        $totalInscrits = $baseQuery()->count();
        $importTotal = $baseQuery()->sum('precio_total');
        $placesBus = $baseQuery()->where('necesita_autobus', true)->count();
        $importBus = $placesBus * $precioBus;
        $assegurances = $baseQuery()->where('seguro_anulacion', true)->count();
        $importAsseg = $assegurances * $precioSeguro;
        $importInscr = $importTotal - $importBus - $importAsseg;

        // Públic no federat (no socio, no federado)
        $publicNoFederat = $baseQuery()
            ->where('es_socio_uec', false)
            ->where('esta_federado', false)
            ->count();
        $publicNoFederatBus = $baseQuery()
            ->where('es_socio_uec', false)
            ->where('esta_federado', false)
            ->where('necesita_autobus', true)
            ->count();
        $publicNoFederatAsseg = $baseQuery()
            ->where('es_socio_uec', false)
            ->where('esta_federado', false)
            ->where('seguro_anulacion', true)
            ->count();
        $publicNoFederatTotal = $baseQuery()
            ->where('es_socio_uec', false)
            ->where('esta_federado', false)
            ->sum('precio_total');

        // Públic federat (no socio, federado)
        $publicFederat = $baseQuery()
            ->where('es_socio_uec', false)
            ->where('esta_federado', true)
            ->count();
        $publicFederatBus = $baseQuery()
            ->where('es_socio_uec', false)
            ->where('esta_federado', true)
            ->where('necesita_autobus', true)
            ->count();
        $publicFederatAsseg = $baseQuery()
            ->where('es_socio_uec', false)
            ->where('esta_federado', true)
            ->where('seguro_anulacion', true)
            ->count();
        $publicFederatTotal = $baseQuery()
            ->where('es_socio_uec', false)
            ->where('esta_federado', true)
            ->sum('precio_total');

        // Soci no federat
        $sociNoFederat = $baseQuery()
            ->where('es_socio_uec', true)
            ->where('esta_federado', false)
            ->count();
        $sociNoFederatBus = $baseQuery()
            ->where('es_socio_uec', true)
            ->where('esta_federado', false)
            ->where('necesita_autobus', true)
            ->count();
        $sociNoFederatAsseg = $baseQuery()
            ->where('es_socio_uec', true)
            ->where('esta_federado', false)
            ->where('seguro_anulacion', true)
            ->count();
        $sociNoFederatTotal = $baseQuery()
            ->where('es_socio_uec', true)
            ->where('esta_federado', false)
            ->sum('precio_total');

        // Soci federat
        $sociFederat = $baseQuery()
            ->where('es_socio_uec', true)
            ->where('esta_federado', true)
            ->count();
        $sociFederatBus = $baseQuery()
            ->where('es_socio_uec', true)
            ->where('esta_federado', true)
            ->where('necesita_autobus', true)
            ->count();
        $sociFederatAsseg = $baseQuery()
            ->where('es_socio_uec', true)
            ->where('esta_federado', true)
            ->where('seguro_anulacion', true)
            ->count();
        $sociFederatTotal = $baseQuery()
            ->where('es_socio_uec', true)
            ->where('esta_federado', true)
            ->sum('precio_total');

        return [
            'totals' => [
                'total' => $totalInscrits,
                'importTotal' => $importTotal,
                'importInscr' => $importInscr,
                'importBus' => $importBus,
                'importAsseg' => $importAsseg,
            ],
            'publicNoFederat' => [
                'total' => $publicNoFederat,
                'importTotal' => $publicNoFederatTotal,
                'importInscr' => $publicNoFederatTotal - ($publicNoFederatBus * $precioBus) - ($publicNoFederatAsseg * $precioSeguro),
                'importBus' => $publicNoFederatBus * $precioBus,
                'importAsseg' => $publicNoFederatAsseg * $precioSeguro,
            ],
            'publicFederat' => [
                'total' => $publicFederat,
                'importTotal' => $publicFederatTotal,
                'importInscr' => $publicFederatTotal - ($publicFederatBus * $precioBus) - ($publicFederatAsseg * $precioSeguro),
                'importBus' => $publicFederatBus * $precioBus,
                'importAsseg' => $publicFederatAsseg * $precioSeguro,
            ],
            'sociNoFederat' => [
                'total' => $sociNoFederat,
                'importTotal' => $sociNoFederatTotal,
                'importInscr' => $sociNoFederatTotal - ($sociNoFederatBus * $precioBus) - ($sociNoFederatAsseg * $precioSeguro),
                'importBus' => $sociNoFederatBus * $precioBus,
                'importAsseg' => $sociNoFederatAsseg * $precioSeguro,
            ],
            'sociFederat' => [
                'total' => $sociFederat,
                'importTotal' => $sociFederatTotal,
                'importInscr' => $sociFederatTotal - ($sociFederatBus * $precioBus) - ($sociFederatAsseg * $precioSeguro),
                'importBus' => $sociFederatBus * $precioBus,
                'importAsseg' => $sociFederatAsseg * $precioSeguro,
            ],
            'placesBus' => [
                'total' => $placesBus,
                'importTotal' => $importBus,
            ],
            'busTortosa' => [
                'total' => $baseQuery()->where('necesita_autobus', true)->where('parada_autobus', 'tortosa')->count(),
                'importTotal' => $baseQuery()->where('necesita_autobus', true)->where('parada_autobus', 'tortosa')->count() * $precioBus,
            ],
            'busPauls' => [
                'total' => $baseQuery()->where('necesita_autobus', true)->where('parada_autobus', 'pauls')->count(),
                'importTotal' => $baseQuery()->where('necesita_autobus', true)->where('parada_autobus', 'pauls')->count() * $precioBus,
            ],
            'assegurances' => [
                'total' => $assegurances,
                'importTotal' => $importAsseg,
            ],
        ];
    }
}