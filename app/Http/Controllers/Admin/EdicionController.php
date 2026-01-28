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
        $ediciones = Edicion::withCount('inscripciones')
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
            // Autobuses (JSON array)
            'autobuses' => 'nullable|array',
            'autobuses.*.nombre' => 'required|string|max:100',
            'autobuses.*.plazas' => 'required|integer|min:1',
            // Tarifas normales
            'tarifa_publico_federado_normal' => 'nullable|numeric|min:0',
            'tarifa_publico_no_federado_normal' => 'nullable|numeric|min:0',
            'tarifa_socio_federado_normal' => 'nullable|numeric|min:0',
            'tarifa_socio_no_federado_normal' => 'nullable|numeric|min:0',
            // Tarifas tardías
            'tarifa_publico_federado_tardia' => 'nullable|numeric|min:0',
            'tarifa_publico_no_federado_tardia' => 'nullable|numeric|min:0',
            'tarifa_socio_federado_tardia' => 'nullable|numeric|min:0',
            'tarifa_socio_no_federado_tardia' => 'nullable|numeric|min:0',
            // Extras
            'precio_autobus_normal' => 'nullable|numeric|min:0',
            'precio_autobus_tardia' => 'nullable|numeric|min:0',
            'precio_seguro' => 'nullable|numeric|min:0',
        ]);

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
            // Autobuses (JSON array)
            'autobuses' => 'nullable|array',
            'autobuses.*.nombre' => 'required|string|max:100',
            'autobuses.*.plazas' => 'required|integer|min:1',
            // Tarifas normales
            'tarifa_publico_federado_normal' => 'nullable|numeric|min:0',
            'tarifa_publico_no_federado_normal' => 'nullable|numeric|min:0',
            'tarifa_socio_federado_normal' => 'nullable|numeric|min:0',
            'tarifa_socio_no_federado_normal' => 'nullable|numeric|min:0',
            // Tarifas tardías
            'tarifa_publico_federado_tardia' => 'nullable|numeric|min:0',
            'tarifa_publico_no_federado_tardia' => 'nullable|numeric|min:0',
            'tarifa_socio_federado_tardia' => 'nullable|numeric|min:0',
            'tarifa_socio_no_federado_tardia' => 'nullable|numeric|min:0',
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
