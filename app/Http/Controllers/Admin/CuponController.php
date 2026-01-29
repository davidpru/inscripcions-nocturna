<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cupon;
use App\Models\Edicion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CuponController extends Controller
{
    public function index()
    {
        $cupones = Cupon::with('edicion')
            ->orderBy('created_at', 'desc')
            ->get();

        $ediciones = Edicion::orderBy('anio', 'desc')->get();

        return Inertia::render('Admin/Cupones/Index', [
            'cupones' => $cupones,
            'ediciones' => $ediciones,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:cupones,codigo',
            'descripcion' => 'nullable|string|max:255',
            'edicion_id' => 'required|exists:ediciones,id',
            'usos_maximos' => 'required|integer|min:1',
            'incluye_autobus' => 'required|boolean',
            'incluye_federativa' => 'required|boolean',
            'activo' => 'required|boolean',
            'fecha_expiracion' => 'nullable|date',
        ]);

        // Convertir código a mayúsculas
        $validated['codigo'] = strtoupper($validated['codigo']);

        Cupon::create($validated);

        return redirect()->back()->with('success', 'Cupón creado correctamente.');
    }

    public function update(Request $request, Cupon $cupon)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:cupones,codigo,' . $cupon->id,
            'descripcion' => 'nullable|string|max:255',
            'edicion_id' => 'required|exists:ediciones,id',
            'usos_maximos' => 'required|integer|min:1',
            'incluye_autobus' => 'required|boolean',
            'incluye_federativa' => 'required|boolean',
            'activo' => 'required|boolean',
            'fecha_expiracion' => 'nullable|date',
        ]);

        // Convertir código a mayúsculas
        $validated['codigo'] = strtoupper($validated['codigo']);

        $cupon->update($validated);

        return redirect()->back()->with('success', 'Cupón actualizado correctamente.');
    }

    public function destroy(Cupon $cupon)
    {
        // Verificar si hay inscripciones usando este cupón
        if ($cupon->inscripciones()->count() > 0) {
            return redirect()->back()->with('error', 'No se puede eliminar un cupón que ya ha sido usado.');
        }

        $cupon->delete();

        return redirect()->back()->with('success', 'Cupón eliminado correctamente.');
    }

    /**
     * Resetear los usos del cupón
     */
    public function resetUsos(Cupon $cupon)
    {
        $cupon->update(['usos_actuales' => 0]);

        return redirect()->back()->with('success', 'Usos del cupón reseteados.');
    }
}
