<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\Edicion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InscripcionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Inscripcion::with(['participante', 'edicion'])
            ->orderBy('created_at', 'desc');

        // Filtrar por edición si se especifica
        if ($request->has('edicion_id')) {
            $query->where('edicion_id', $request->edicion_id);
        }

        $inscripciones = $query->paginate(50);
        $ediciones = Edicion::orderBy('anio', 'desc')->get();

        return Inertia::render('Admin/Inscripciones/Index', [
            'inscripciones' => $inscripciones,
            'ediciones' => $ediciones,
            'filtros' => $request->only('edicion_id'),
        ]);
    }

    public function show(Inscripcion $inscripcion): Response
    {
        $inscripcion->load(['participante', 'edicion']);

        return Inertia::render('Admin/Inscripciones/Show', [
            'inscripcion' => $inscripcion,
        ]);
    }

    public function edit(Inscripcion $inscripcion): Response
    {
        $inscripcion->load(['participante', 'edicion']);

        return Inertia::render('Admin/Inscripciones/Edit', [
            'inscripcion' => $inscripcion,
        ]);
    }

    public function update(Request $request, Inscripcion $inscripcion)
    {
        $validated = $request->validate([
            'estado_pago' => 'required|in:pendiente,pagado,cancelado',
            'talla_camiseta_caro' => 'nullable|string|max:10',
            'talla_camiseta_pauls' => 'nullable|string|max:10',
        ]);

        $inscripcion->update($validated);

        return redirect()->route('admin.inscripciones.index')
            ->with('success', 'Inscripción actualizada con éxito');
    }

    public function destroy(Inscripcion $inscripcion)
    {
        $inscripcion->delete();

        return redirect()->route('admin.inscripciones.index')
            ->with('success', 'Inscripción eliminada con éxito');
    }

    public function exportar(Request $request)
    {
        $query = Inscripcion::with(['participante', 'edicion']);

        if ($request->has('edicion_id')) {
            $query->where('edicion_id', $request->edicion_id);
        }

        $inscripciones = $query->get();

        // TODO: Implementar exportación a CSV/Excel
        return response()->json([
            'message' => 'Función de exportación pendiente de implementar',
            'total' => $inscripciones->count(),
        ]);
    }
}
