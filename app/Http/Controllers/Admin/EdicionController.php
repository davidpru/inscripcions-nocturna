<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edicion;
use App\Models\Inscripcion;
use App\Services\DorsalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EdicionController extends Controller
{
    public function index(): Response
    {
        $ediciones = Edicion::withCount(['inscripciones' => function ($query) {
                $query->whereIn('estado_pago', ['pagado', 'invitado']);
            }])
            ->orderBy('anio', 'desc')
            ->get();

        return Inertia::render('Admin/Ediciones/Index', [
            'ediciones' => $ediciones,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Ediciones/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'anio' => 'required|integer|unique:ediciones,anio',
            'fecha_inicio_inscripciones' => 'nullable|date',
            'fecha_evento' => 'required|date',
            'limite_inscritos' => 'required|integer|min:1',
            'limite_tarifa_tardia_inscritos' => 'required|integer|min:1|lte:limite_inscritos',
            'fecha_limite_tarifa_normal' => 'required|date',
            'estado' => 'required|in:abierta,cerrada',
            'lista_espera_cerrada' => 'boolean',
            'dorsal_primer_masculino_id' => 'nullable|integer|exists:inscripciones,id',
            'dorsal_primera_femenina_id' => 'nullable|integer|exists:inscripciones,id',
            'activa' => 'boolean',
            // Autobuses
            'autobuses' => 'nullable|array',
            'autobuses.*.nombre' => 'required|string|max:100',
            'autobuses.*.plazas' => 'required|integer|min:1',
            'plazas_autobus' => 'required|integer|min:0',
            // Nova estructura de preus
            'precio_inscripcion_socio_normal' => 'nullable|numeric|min:0',
            'precio_inscripcion_publico_normal' => 'nullable|numeric|min:0',
            'precio_inscripcion_socio_tardia' => 'nullable|numeric|min:0',
            'precio_inscripcion_publico_tardia' => 'nullable|numeric|min:0',
            'precio_licencia_federativa_socio' => 'nullable|numeric|min:0',
            'precio_licencia_federativa_publico' => 'nullable|numeric|min:0',
            // Extras
            'precio_autobus_normal' => 'nullable|numeric|min:0',
            'precio_autobus_tardia' => 'nullable|numeric|min:0',
            'precio_seguro' => 'nullable|numeric|min:0',
        ]);

        // Si se activa esta edición, desactivar todas las demás
        if ($validated['activa'] ?? false) {
            Edicion::where('activa', true)->update(['activa' => false]);
        }

        Edicion::create($validated);

        return redirect()->route('admin.ediciones.index')
            ->with('success', 'Edición creada con éxito');
    }

    public function edit(Edicion $edicion): Response
    {
        // Plazas físicas ocupadas: pagado + invitado + compromiso
        $plazasAutobusVendidas = $edicion->inscripciones()
            ->where('necesita_autobus', true)
            ->whereIn('estado_pago', ['pagado', 'invitado', 'compromiso'])
            ->count();

        // Contar plazas por parada
        $plazasPorParada = $edicion->inscripciones()
            ->where('necesita_autobus', true)
            ->whereIn('estado_pago', ['pagado', 'invitado', 'compromiso'])
            ->selectRaw('parada_autobus, COUNT(*) as total')
            ->groupBy('parada_autobus')
            ->pluck('total', 'parada_autobus')
            ->toArray();

        $candidatosDorsal = $edicion->inscripciones()
            ->whereIn('estado_pago', ['pagado', 'invitado', 'compromiso'])
            ->with('participante:id,nombre,apellidos,dni,genero')
            ->get(['id', 'participante_id', 'numero_dorsal', 'estado_pago', 'created_at'])
            ->map(fn($i) => [
                'id' => $i->id,
                'nombre' => trim(($i->participante->nombre ?? '') . ' ' . ($i->participante->apellidos ?? '')),
                'dni' => $i->participante->dni ?? '',
                'genero' => $i->participante->genero ?? null,
                'numero_dorsal' => $i->numero_dorsal,
            ])
            ->sortBy('nombre')
            ->values();

        $dorsalesStats = [
            'asignados' => $edicion->inscripciones()->whereNotNull('numero_dorsal')->count(),
            'pendientes' => $edicion->inscripciones()
                ->whereIn('estado_pago', ['pagado', 'invitado', 'compromiso'])
                ->whereNull('numero_dorsal')
                ->count(),
        ];

        // Preview: qué dorsal recibiría cada pendiente al ejecutar
        $pendientesPreview = $edicion->inscripciones()
            ->whereIn('estado_pago', ['pagado', 'invitado', 'compromiso'])
            ->whereNull('numero_dorsal')
            ->with('participante:id,nombre,apellidos,dni')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $usados = array_flip(
            $edicion->inscripciones()->whereNotNull('numero_dorsal')->pluck('numero_dorsal')->all()
        );
        // Si admin seleccionó #1/#2 y aún no están asignados, simular reserva
        $reservadosSimulados = [];
        foreach ([
            1 => $edicion->dorsal_primer_masculino_id,
            2 => $edicion->dorsal_primera_femenina_id,
        ] as $dorsal => $inscId) {
            if ($inscId) {
                $reservadosSimulados[$dorsal] = $inscId;
                $usados[$dorsal] = true;
            }
        }

        $next = 1;
        $previewAsignacion = $pendientesPreview->map(function ($i) use (&$next, &$usados) {
            while (isset($usados[$next])) {
                $next++;
            }
            $dorsalAsignado = $next;
            $usados[$next] = true;
            $next++;
            return [
                'id' => $i->id,
                'nombre' => trim(($i->participante->nombre ?? '') . ' ' . ($i->participante->apellidos ?? '')),
                'dni' => $i->participante->dni ?? '',
                'estado_pago' => $i->estado_pago,
                'created_at' => $i->created_at?->format('Y-m-d H:i'),
                'dorsal_previsto' => $dorsalAsignado,
            ];
        })->values();

        return Inertia::render('Admin/Ediciones/Edit', [
            'edicion' => $edicion,
            'plazasAutobusVendidas' => $plazasAutobusVendidas,
            'plazasPorParada' => $plazasPorParada,
            'plazasAutobusDisponibles' => $edicion->plazas_autobus - $plazasAutobusVendidas,
            'candidatosDorsal' => $candidatosDorsal,
            'dorsalesStats' => $dorsalesStats,
            'previewAsignacion' => $previewAsignacion,
        ]);
    }

    public function asignarDorsales(Edicion $edicion, DorsalService $dorsalService)
    {
        $resultado = $dorsalService->asignar($edicion);

        return back()->with('success', "Dorsals assignats. Reservats: 1=#{$resultado['reservado_1']}, 2=#{$resultado['reservado_2']}. Nous: {$resultado['asignados_nuevos']}.");
    }

    public function update(Request $request, Edicion $edicion)
    {
        $validated = $request->validate([
            'anio' => 'required|integer|unique:ediciones,anio,' . $edicion->id,
            'fecha_inicio_inscripciones' => 'nullable|date',
            'fecha_evento' => 'required|date',
            'limite_inscritos' => 'required|integer|min:1',
            'limite_tarifa_tardia_inscritos' => 'required|integer|min:1|lte:limite_inscritos',
            'fecha_limite_tarifa_normal' => 'required|date',
            'estado' => 'required|in:abierta,cerrada',
            'lista_espera_cerrada' => 'boolean',
            'dorsal_primer_masculino_id' => 'nullable|integer|exists:inscripciones,id',
            'dorsal_primera_femenina_id' => 'nullable|integer|exists:inscripciones,id',
            'activa' => 'boolean',
            // Autobuses
            'autobuses' => 'nullable|array',
            'autobuses.*.nombre' => 'required|string|max:100',
            'autobuses.*.plazas' => 'required|integer|min:1',
            'plazas_autobus' => 'required|integer|min:0',
            // Nova estructura de preus
            'precio_inscripcion_socio_normal' => 'nullable|numeric|min:0',
            'precio_inscripcion_publico_normal' => 'nullable|numeric|min:0',
            'precio_inscripcion_socio_tardia' => 'nullable|numeric|min:0',
            'precio_inscripcion_publico_tardia' => 'nullable|numeric|min:0',
            'precio_licencia_federativa_socio' => 'nullable|numeric|min:0',
            'precio_licencia_federativa_publico' => 'nullable|numeric|min:0',
            // Extras
            'precio_autobus_normal' => 'nullable|numeric|min:0',
            'precio_autobus_tardia' => 'nullable|numeric|min:0',
            'precio_seguro' => 'nullable|numeric|min:0',
        ]);

        // Verificar que no se reduzcan las plazas por debajo de las ocupadas
        $plazasAutobusVendidas = $edicion->inscripciones()
            ->where('necesita_autobus', true)
            ->whereIn('estado_pago', ['pagado', 'invitado', 'compromiso'])
            ->count();

        $nuevasPlazas = $validated['plazas_autobus'] ?? 0;
        
        if ($nuevasPlazas > 0 && $nuevasPlazas < $plazasAutobusVendidas) {
            return back()->withErrors([
                'plazas_autobus' => "No es pot reduir a {$nuevasPlazas} places. Hi ha {$plazasAutobusVendidas} places d'autobús venudes."
            ]);
        }

        // Si se activa esta edición, desactivar todas las demás
        if ($validated['activa'] ?? false) {
            Edicion::where('activa', true)
                ->where('id', '!=', $edicion->id)
                ->update(['activa' => false]);
        }

        $edicion->update($validated);

        return redirect()->route('admin.ediciones.index')
            ->with('success', 'Edición actualizada con éxito');
    }

    public function destroy(Edicion $edicion)
    {
        $edicion->delete();

        return redirect()->route('admin.ediciones.index')
            ->with('success', 'Edición eliminada con éxito');
    }
}