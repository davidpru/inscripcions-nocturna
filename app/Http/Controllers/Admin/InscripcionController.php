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

        // Filtrar por edición si se especifica y tiene valor
        if ($request->filled('edicion_id')) {
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
            // Datos del participante
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'dni' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'telefono' => 'required|string|max:20',
            'direccion' => 'required|string|max:255',
            'codigo_postal' => 'required|string|max:10',
            'poblacion' => 'required|string|max:100',
            'provincia' => 'required|string|max:100',
            'genero' => 'required|in:masculino,femenino',
            'fecha_nacimiento' => 'required|date',
            // Datos de la inscripción
            'estado_pago' => 'required|in:pendiente,pagado,cancelado',
            'es_socio_uec' => 'boolean',
            'esta_federado' => 'boolean',
            'numero_licencia' => 'nullable|string|max:50',
            'club' => 'nullable|string|max:100',
            'necesita_autobus' => 'boolean',
            'parada_autobus' => 'nullable|string|max:100',
            'seguro_anulacion' => 'boolean',
            'talla_camiseta_caro' => 'required|string|max:10',
            'talla_camiseta_pauls' => 'required|string|max:10',
        ]);

        // Actualizar participante
        $inscripcion->participante->update([
            'nombre' => $validated['nombre'],
            'apellidos' => $validated['apellidos'],
            'dni' => $validated['dni'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'],
            'direccion' => $validated['direccion'],
            'codigo_postal' => $validated['codigo_postal'],
            'poblacion' => $validated['poblacion'],
            'provincia' => $validated['provincia'],
            'genero' => $validated['genero'],
            'fecha_nacimiento' => $validated['fecha_nacimiento'],
        ]);

        // Actualizar inscripción
        $inscripcion->update([
            'estado_pago' => $validated['estado_pago'],
            'es_socio_uec' => $validated['es_socio_uec'] ?? false,
            'esta_federado' => $validated['esta_federado'] ?? false,
            'numero_licencia' => $validated['numero_licencia'],
            'club' => $validated['club'],
            'necesita_autobus' => $validated['necesita_autobus'] ?? false,
            'parada_autobus' => $validated['parada_autobus'],
            'seguro_anulacion' => $validated['seguro_anulacion'] ?? false,
            'talla_camiseta_caro' => $validated['talla_camiseta_caro'],
            'talla_camiseta_pauls' => $validated['talla_camiseta_pauls'],
        ]);

        return back()->with('success', 'Inscripción actualizada con éxito');
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