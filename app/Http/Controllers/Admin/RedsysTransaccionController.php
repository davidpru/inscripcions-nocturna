<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RedsysTransaccion;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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
            'undo' => $request->session()->get('redsys_undo'),
        ]);
    }

    public function destroy(RedsysTransaccion $transaccion): RedirectResponse
    {
        if ($transaccion->estado !== 'error') {
            abort(403, 'Solo se pueden eliminar errores de conexion.');
        }

        session()->put('redsys_undo', [
            'inscripcion_id' => $transaccion->inscripcion_id,
            'tipo' => $transaccion->tipo,
            'estado' => $transaccion->estado,
            'numero_pedido' => $transaccion->numero_pedido,
            'numero_autorizacion' => $transaccion->numero_autorizacion,
            'importe' => $transaccion->importe,
            'moneda' => $transaccion->moneda,
            'response_code' => $transaccion->response_code,
            'descripcion_error' => $transaccion->descripcion_error,
            'es_autobus' => $transaccion->es_autobus,
            'payload' => $transaccion->payload,
            'created_at' => $transaccion->created_at,
            'updated_at' => $transaccion->updated_at,
        ]);

        $transaccion->delete();

        return back();
    }

    public function restore(Request $request): RedirectResponse
    {
        $data = $request->session()->get('redsys_undo');

        if (!$data || ($data['estado'] ?? null) !== 'error') {
            return back();
        }

        $transaccion = new RedsysTransaccion([
            'inscripcion_id' => $data['inscripcion_id'] ?? null,
            'tipo' => $data['tipo'] ?? 'error',
            'estado' => $data['estado'] ?? 'error',
            'numero_pedido' => $data['numero_pedido'] ?? null,
            'numero_autorizacion' => $data['numero_autorizacion'] ?? null,
            'importe' => $data['importe'] ?? null,
            'moneda' => $data['moneda'] ?? 'EUR',
            'response_code' => $data['response_code'] ?? null,
            'descripcion_error' => $data['descripcion_error'] ?? null,
            'es_autobus' => $data['es_autobus'] ?? false,
            'payload' => $data['payload'] ?? null,
        ]);

        if (!empty($data['created_at'])) {
            $transaccion->created_at = $data['created_at'];
        }

        if (!empty($data['updated_at'])) {
            $transaccion->updated_at = $data['updated_at'];
        }

        $transaccion->save();

        $request->session()->forget('redsys_undo');

        return back();
    }
}
