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
            'descuento_tipo' => 'required|in:porcentaje,fijo',
            'descuento_valor' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('descuento_tipo') === 'porcentaje' && (float) $value > 100) {
                        $fail('El descuento en porcentaje no puede superar el 100%.');
                    }
                },
            ],
            'usos_maximos' => 'required|integer|min:1',
            'incluye_autobus' => 'required|boolean',
            'incluye_federativa' => 'required|boolean',
            'activo' => 'required|boolean',
            'fecha_expiracion' => 'nullable|date',
        ]);

        // Convertir código a mayúsculas
        $validated['codigo'] = strtoupper($validated['codigo']);
        $validated['descuento_porcentaje'] = $validated['descuento_tipo'] === 'porcentaje'
            ? (float) $validated['descuento_valor']
            : 0;

        Cupon::create($validated);

        return redirect()->back()->with('success', 'Cupón creado correctamente.');
    }

    public function update(Request $request, Cupon $cupon)
    {
        $cuponUsado = $cupon->usos_actuales > 0 || $cupon->inscripciones()->exists();

        // Si el cupón ya fue usado, solo permitimos ampliar/ajustar usos máximos.
        if ($cuponUsado) {
            $validated = $request->validate([
                'usos_maximos' => 'required|integer|min:' . $cupon->usos_actuales,
                'fecha_expiracion' => 'nullable|date',
                'activo' => 'required|boolean',
            ]);

            $cupon->update([
                'usos_maximos' => $validated['usos_maximos'],
                'fecha_expiracion' => $validated['fecha_expiracion'] ?? null,
                'activo' => $validated['activo'],
            ]);

            return redirect()->back()->with('success', 'Usos máximos, expiración y estado del cupón actualizados correctamente.');
        }

        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:cupones,codigo,' . $cupon->id,
            'descripcion' => 'nullable|string|max:255',
            'edicion_id' => 'required|exists:ediciones,id',
            'descuento_tipo' => 'required|in:porcentaje,fijo',
            'descuento_valor' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('descuento_tipo') === 'porcentaje' && (float) $value > 100) {
                        $fail('El descuento en porcentaje no puede superar el 100%.');
                    }
                },
            ],
            'usos_maximos' => 'required|integer|min:1',
            'incluye_autobus' => 'required|boolean',
            'incluye_federativa' => 'required|boolean',
            'activo' => 'required|boolean',
            'fecha_expiracion' => 'nullable|date',
        ]);

        // Convertir código a mayúsculas
        $validated['codigo'] = strtoupper($validated['codigo']);
        $validated['descuento_porcentaje'] = $validated['descuento_tipo'] === 'porcentaje'
            ? (float) $validated['descuento_valor']
            : 0;

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
