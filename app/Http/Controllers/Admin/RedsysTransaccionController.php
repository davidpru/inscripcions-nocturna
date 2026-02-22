<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RedsysTransaccion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RedsysTransaccionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = RedsysTransaccion::with(['inscripcion.participante'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->where('numero_pedido', 'like', "%{$busqueda}%")
                    ->orWhere('numero_autorizacion', 'like', "%{$busqueda}%")
                    ->orWhereHas('inscripcion', function ($sub) use ($busqueda) {
                        $sub->where('id', $busqueda)
                            ->orWhereHas('participante', function ($p) use ($busqueda) {
                                $p->where('dni', 'like', "%{$busqueda}%")
                                    ->orWhere('email', 'like', "%{$busqueda}%");
                            });
                    });
            });
        }

        $transacciones = $query->paginate(50)->withQueryString();

        return Inertia::render('Admin/Transacciones/Index', [
            'transacciones' => $transacciones,
            'filtros' => $request->only(['estado', 'tipo', 'desde', 'hasta', 'busqueda']),
        ]);
    }
}
