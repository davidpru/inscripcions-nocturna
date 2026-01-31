<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edicion;
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
            'fecha_limite_tarifa_normal' => 'required|date',
            'estado' => 'required|in:abierta,cerrada',
            'activa' => 'boolean',
            // Autobuses (JSON array)
            'autobuses' => 'nullable|array',
            'autobuses.*.nombre' => 'required|string|max:100',
            'autobuses.*.plazas' => 'required|integer|min:1',
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
        // Contar plazas de autobús vendidas
        $plazasAutobusVendidas = $edicion->inscripciones()
            ->where('necesita_autobus', true)
            ->where('estado_pago', 'pagado')
            ->count();

        // Contar plazas por parada
        $plazasPorParada = $edicion->inscripciones()
            ->where('necesita_autobus', true)
            ->where('estado_pago', 'pagado')
            ->selectRaw('parada_autobus, COUNT(*) as total')
            ->groupBy('parada_autobus')
            ->pluck('total', 'parada_autobus')
            ->toArray();

        return Inertia::render('Admin/Ediciones/Edit', [
            'edicion' => $edicion,
            'plazasAutobusVendidas' => $plazasAutobusVendidas,
            'plazasPorParada' => $plazasPorParada,
        ]);
    }

    public function update(Request $request, Edicion $edicion)
    {
        $validated = $request->validate([
            'anio' => 'required|integer|unique:ediciones,anio,' . $edicion->id,
            'fecha_inicio_inscripciones' => 'nullable|date',
            'fecha_evento' => 'required|date',
            'limite_inscritos' => 'required|integer|min:1',
            'fecha_limite_tarifa_normal' => 'required|date',
            'estado' => 'required|in:abierta,cerrada',
            'activa' => 'boolean',
            // Autobuses (JSON array)
            'autobuses' => 'nullable|array',
            'autobuses.*.nombre' => 'required|string|max:100',
            'autobuses.*.plazas' => 'required|integer|min:1',
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

        // Verificar que no se reduzcan las plazas por debajo de las vendidas
        $plazasAutobusVendidas = $edicion->inscripciones()
            ->where('necesita_autobus', true)
            ->where('estado_pago', 'pagado')
            ->count();

        $nuevasPlazas = collect($validated['autobuses'] ?? [])->sum('plazas');
        
        if ($nuevasPlazas < $plazasAutobusVendidas) {
            return back()->withErrors([
                'autobuses' => "No es pot reduir a {$nuevasPlazas} places. Hi ha {$plazasAutobusVendidas} places d'autobús venudes."
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
