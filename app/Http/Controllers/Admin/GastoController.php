<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaGasto;
use App\Models\Edicion;
use App\Models\Gasto;
use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GastoController extends Controller
{
    public function index(Request $request)
    {
        $edicionId = $request->query('edicion_id');

        $ediciones = Edicion::orderBy('anio', 'desc')->get();

        $edicionActual = $edicionId
            ? Edicion::findOrFail($edicionId)
            : $ediciones->first();

        $gastos = [];
        $totalGastos = 0;
        $costePorKm = null;
        $totalRecaudado = 0;

        if ($edicionActual) {
            $totalRecaudado = Inscripcion::where('edicion_id', $edicionActual->id)
                ->where('estado_pago', 'pagado')
                ->sum('precio_total');
            $gastos = Gasto::where('edicion_id', $edicionActual->id)
                ->with(['categorias', 'presupuestadoPorAdmin:id,nombre', 'aceptadoPorAdmin:id,nombre', 'pagadoPorAdmin:id,nombre'])
                ->orderBy('created_at', 'desc')
                ->get();

            $totalGastos = $gastos->where('aceptado', true)->sum('total');

            if ($edicionActual->distancia_km && $edicionActual->distancia_km > 0) {
                $costePorKm = round($totalGastos / $edicionActual->distancia_km, 2);
            }
        }

        $categorias = CategoriaGasto::orderBy('nombre')->get();

        return Inertia::render('Admin/Gastos/Index', [
            'ediciones' => $ediciones,
            'edicionActual' => $edicionActual,
            'gastos' => $gastos,
            'categorias' => $categorias,
            'totalGastos' => round($totalGastos, 2),
            'totalRecaudado' => round($totalRecaudado, 2),
            'costePorKm' => $costePorKm,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'edicion_id' => 'required|exists:ediciones,id',
            'categoria_ids' => 'required|array|min:1',
            'categoria_ids.*' => 'exists:categorias_gasto,id',
            'titulo' => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:1000',
            'base_imponible' => 'required|numeric|min:0',
            'tipo_iva' => 'required|in:0,4,10,21',
            'presupuestado' => 'required|boolean',
            'aceptado' => 'required|boolean',
            'pagado' => 'required|boolean',
        ]);

        $baseImponible = (float) $validated['base_imponible'];
        $tipoIva = (int) $validated['tipo_iva'];
        $total = round($baseImponible * (1 + $tipoIva / 100), 2);

        $adminId = Auth::guard('administrador')->id();

        $gasto = Gasto::create([
            'edicion_id' => $validated['edicion_id'],
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?? '',
            'base_imponible' => $baseImponible,
            'tipo_iva' => (string) $tipoIva,
            'total' => $total,
            'presupuestado' => $validated['presupuestado'],
            'presupuestado_por' => $validated['presupuestado'] ? $adminId : null,
            'aceptado' => $validated['aceptado'],
            'aceptado_por' => $validated['aceptado'] ? $adminId : null,
            'pagado' => $validated['pagado'],
            'pagado_por' => $validated['pagado'] ? $adminId : null,
        ]);

        $gasto->categorias()->sync($validated['categoria_ids']);

        return redirect()->back()->with('success', 'Despesa afegida correctament.');
    }

    public function update(Request $request, Gasto $gasto)
    {
        $validated = $request->validate([
            'categoria_ids' => 'required|array|min:1',
            'categoria_ids.*' => 'exists:categorias_gasto,id',
            'titulo' => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:1000',
            'base_imponible' => 'required|numeric|min:0',
            'tipo_iva' => 'required|in:0,4,10,21',
            'presupuestado' => 'required|boolean',
            'aceptado' => 'required|boolean',
            'pagado' => 'required|boolean',
        ]);

        $baseImponible = (float) $validated['base_imponible'];
        $tipoIva = (int) $validated['tipo_iva'];
        $total = round($baseImponible * (1 + $tipoIva / 100), 2);

        $adminId = Auth::guard('administrador')->id();

        $gasto->update([
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?? '',
            'base_imponible' => $baseImponible,
            'tipo_iva' => (string) $tipoIva,
            'total' => $total,
            'presupuestado' => $validated['presupuestado'],
            'presupuestado_por' => $validated['presupuestado'] && !$gasto->presupuestado ? $adminId : ($validated['presupuestado'] ? $gasto->presupuestado_por : null),
            'aceptado' => $validated['aceptado'],
            'aceptado_por' => $validated['aceptado'] && !$gasto->aceptado ? $adminId : ($validated['aceptado'] ? $gasto->aceptado_por : null),
            'pagado' => $validated['pagado'],
            'pagado_por' => $validated['pagado'] && !$gasto->pagado ? $adminId : ($validated['pagado'] ? $gasto->pagado_por : null),
        ]);

        $gasto->categorias()->sync($validated['categoria_ids']);

        return redirect()->back()->with('success', 'Despesa actualitzada correctament.');
    }

    public function destroy(Gasto $gasto)
    {
        $gasto->delete();

        return redirect()->back()->with('success', 'Despesa eliminada correctament.');
    }

    // --- Categorías ---

    public function storeCategoria(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias_gasto,nombre',
            'color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        CategoriaGasto::create($validated);

        return redirect()->back()->with('success', 'Categoria afegida correctament.');
    }

    public function updateCategoria(Request $request, CategoriaGasto $categoria)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias_gasto,nombre,' . $categoria->id,
            'color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $categoria->update($validated);

        return redirect()->back()->with('success', 'Categoria actualitzada correctament.');
    }

    public function destroyCategoria(CategoriaGasto $categoria)
    {
        if ($categoria->gastos()->exists()) {
            return redirect()->back()->with('error', 'No es pot eliminar una categoria amb despeses associades.');
        }

        $categoria->delete();

        return redirect()->back()->with('success', 'Categoria eliminada correctament.');
    }

    public function updateDistanciaKm(Request $request, Edicion $edicion)
    {
        $validated = $request->validate([
            'distancia_km' => 'required|numeric|min:0.1|max:9999',
        ]);

        $edicion->update(['distancia_km' => $validated['distancia_km']]);

        return redirect()->back()->with('success', 'Distància actualitzada correctament.');
    }
}
