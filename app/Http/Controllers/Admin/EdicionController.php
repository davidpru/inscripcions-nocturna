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
            'fecha_evento' => 'required|date',
            'limite_inscritos' => 'required|integer|min:1',
            'fecha_limite_tarifa_normal' => 'required|date',
            'estado' => 'required|in:abierta,cerrada',
        ]);

        Edicion::create($validated);

        return redirect()->route('admin.ediciones.index')
            ->with('success', 'Edición creada con éxito');
    }

    public function edit(Edicion $edicion): Response
    {
        return Inertia::render('Admin/Ediciones/Edit', [
            'edicion' => $edicion,
        ]);
    }

    public function update(Request $request, Edicion $edicion)
    {
        $validated = $request->validate([
            'anio' => 'required|integer|unique:ediciones,anio,' . $edicion->id,
            'fecha_evento' => 'required|date',
            'limite_inscritos' => 'required|integer|min:1',
            'fecha_limite_tarifa_normal' => 'required|date',
            'estado' => 'required|in:abierta,cerrada',
        ]);

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
