<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\Edicion;
use App\Models\Participante;
use App\Mail\InscripcionConfirmada;
use App\Services\TarifaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class InscripcionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Inscripcion::with(['participante', 'edicion'])
            ->orderBy('created_at', 'asc');

        // Filtrar por edición si se especifica y tiene valor
        if ($request->filled('edicion_id')) {
            $query->where('edicion_id', $request->edicion_id);
        }

        $inscripciones = $query->paginate(50);
        $ediciones = Edicion::orderBy('anio', 'desc')->get();
        
        // Calcular total de inscripciones pagadas (respetando filtros)
        $totalPagadasQuery = Inscripcion::where('estado_pago', 'pagado');
        if ($request->filled('edicion_id')) {
            $totalPagadasQuery->where('edicion_id', $request->edicion_id);
        }
        $totalInscripcionesPagadas = $totalPagadasQuery->count();

        return Inertia::render('Admin/Inscripciones/Index', [
            'inscripciones' => $inscripciones,
            'ediciones' => $ediciones,
            'filtros' => $request->only('edicion_id'),
            'totalInscripcionesPagadas' => $totalInscripcionesPagadas,
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Datos del participante
            'dni' => 'required|string|max:20',
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'genero' => 'required|in:masculino,femenino',
            'fecha_nacimiento' => 'required|date',
            'telefono' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'direccion' => 'required|string|max:255',
            'codigo_postal' => 'required|string|max:10',
            'poblacion' => 'required|string|max:100',
            'provincia' => 'required|string|max:100',
            // Datos de la inscripción
            'edicion_id' => 'required|exists:ediciones,id',
            'es_socio_uec' => 'boolean',
            'esta_federado' => 'boolean',
            'numero_licencia' => 'nullable|string|max:50',
            'club' => 'nullable|string|max:100',
            'necesita_autobus' => 'boolean',
            'parada_autobus' => 'nullable|string|max:100',
            'seguro_anulacion' => 'boolean',
            'talla_camiseta_caro' => 'required|string|max:10',
            'talla_camiseta_pauls' => 'required|string|max:10',
            'es_celiaco' => 'nullable|string|in:si,no',
            'estado_pago' => 'required|in:pendiente,pagado,invitado',
        ]);

        // Buscar o crear participante
        $participante = Participante::updateOrCreate(
            ['dni' => strtoupper($validated['dni'])],
            [
                'nombre' => $validated['nombre'],
                'apellidos' => $validated['apellidos'],
                'genero' => $validated['genero'],
                'fecha_nacimiento' => $validated['fecha_nacimiento'],
                'telefono' => $validated['telefono'],
                'email' => $validated['email'],
                'direccion' => $validated['direccion'],
                'codigo_postal' => $validated['codigo_postal'],
                'poblacion' => $validated['poblacion'],
                'provincia' => $validated['provincia'],
            ]
        );

        // Verificar si ya está inscrito en esta edición
        $yaInscrito = Inscripcion::where('participante_id', $participante->id)
            ->where('edicion_id', $validated['edicion_id'])
            ->exists();

        if ($yaInscrito) {
            return back()->withErrors(['dni' => 'Este participante ya está inscrito en esta edición.']);
        }

        // Obtener edición para calcular precio
        $edicion = Edicion::findOrFail($validated['edicion_id']);

        // Calcular precio (0 si es invitado)
        if ($validated['estado_pago'] === 'invitado') {
            $precioTotal = 0;
            $tarifaAplicada = 'Invitado';
        } else {
            $tarifaService = new TarifaService();
            $resultadoCalculo = $tarifaService->calcularPrecio(
                $edicion,
                $validated['es_socio_uec'] ?? false,
                $validated['esta_federado'] ?? false,
                $validated['necesita_autobus'] ?? false,
                $validated['seguro_anulacion'] ?? false
            );
            $precioTotal = $resultadoCalculo['precio_total'];
            $tarifaAplicada = $resultadoCalculo['nombre_tarifa'];
        }

        // Crear inscripción
        $inscripcion = Inscripcion::create([
            'participante_id' => $participante->id,
            'edicion_id' => $validated['edicion_id'],
            'es_socio_uec' => $validated['es_socio_uec'] ?? false,
            'esta_federado' => $validated['esta_federado'] ?? false,
            'numero_licencia' => $validated['numero_licencia'],
            'club' => $validated['club'],
            'necesita_autobus' => $validated['necesita_autobus'] ?? false,
            'parada_autobus' => $validated['parada_autobus'],
            'seguro_anulacion' => $validated['seguro_anulacion'] ?? false,
            'talla_camiseta_caro' => $validated['talla_camiseta_caro'],
            'talla_camiseta_pauls' => $validated['talla_camiseta_pauls'],
            'es_celiaco' => ($validated['es_celiaco'] ?? 'no') === 'si',
            'precio_total' => $precioTotal,
            'tarifa_aplicada' => $tarifaAplicada,
            'estado_pago' => $validated['estado_pago'],
            'fecha_pago' => in_array($validated['estado_pago'], ['pagado', 'invitado']) ? now() : null,
        ]);

        return back()->with('success', 'Inscripció creada correctament');
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
            'estado_pago' => 'required|in:pendiente,pagado,cancelado,invitado',
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

        // Recalcular precio y tarifa si no es invitado
        if ($validated['estado_pago'] === 'invitado') {
            $precioTotal = 0;
            $tarifaAplicada = 'Invitado';
        } else {
            $tarifaService = new TarifaService();
            $resultadoCalculo = $tarifaService->calcularPrecio(
                $inscripcion->edicion,
                $validated['es_socio_uec'] ?? false,
                $validated['esta_federado'] ?? false,
                $validated['necesita_autobus'] ?? false,
                $validated['seguro_anulacion'] ?? false
            );
            
            // Si tiene cupón aplicado, recalcular el descuento
            $descuentoCupon = 0;
            if ($inscripcion->cupon_id) {
                $cupon = $inscripcion->cupon;
                if ($cupon) {
                    $descuentoCupon = $cupon->calcularDescuento(
                        $inscripcion->edicion,
                        $validated['es_socio_uec'] ?? false,
                        $validated['esta_federado'] ?? false
                    );
                    
                    // Si incluye autobús, añadir el precio del autobús al descuento
                    if ($cupon->incluye_autobus && ($validated['necesita_autobus'] ?? false)) {
                        $descuentoCupon += $resultadoCalculo['precio_autobus'];
                    }
                }
            }
            
            $precioTotal = max(0, $resultadoCalculo['precio_total'] - $descuentoCupon);
            $tarifaAplicada = $resultadoCalculo['nombre_tarifa'];
        }

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
            'precio_total' => $precioTotal,
            'tarifa_aplicada' => $tarifaAplicada,
            'descuento_cupon' => $descuentoCupon ?? 0,
        ]);

        return back()->with('success', 'Inscripción actualizada con éxito');
    }

    public function destroy(Inscripcion $inscripcion)
    {
        $inscripcion->delete();

        return redirect()->route('admin.inscripciones.index')
            ->with('success', 'Inscripción eliminada con éxito');
    }

    public function reenviarCorreo(Inscripcion $inscripcion)
    {
        $inscripcion->load(['participante', 'edicion']);

        if (!$inscripcion->participante->email) {
            return back()->with('error', 'El participante no tiene un correo electrónico asociado.');
        }

        try {
            Mail::to($inscripcion->participante->email)->send(new InscripcionConfirmada($inscripcion));
            return back()->with('success', 'Correo de confirmación reenviado con éxito.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al enviar el correo: ' . $e->getMessage());
        }
    }

    public function toggleDorsalRecogido(Inscripcion $inscripcion)
    {
        $inscripcion->dorsal_recogido = !$inscripcion->dorsal_recogido;
        $inscripcion->save();

        return redirect()->back();
    }

    public function exportar(Request $request)
    {
        $query = Inscripcion::with(['participante', 'edicion'])
            ->whereIn('estado_pago', ['pagado', 'invitado'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('edicion_id')) {
            $query->where('edicion_id', $request->edicion_id);
        }

        $inscripciones = $query->get();

        // Generar CSV
        $headers = [
            'ID',
            'Número Pedido',
            'Edición',
            'DNI',
            'Nombre',
            'Apellidos',
            'Email',
            'Teléfono',
            'Dirección',
            'Código Postal',
            'Población',
            'Provincia',
            'Género',
            'Fecha Nacimiento',
            'Es Socio UEC',
            'Está Federado',
            'Club',
            'Número Licencia',
            'Necesita Autobús',
            'Parada Autobús',
            'Talla Camiseta Caro',
            'Talla Camiseta Paüls',
            'Seguro Anulación',
            'Precio Total',
            'Descuento Cupón',
            'Estado Pago',
            'Fecha Pago',
            'Fecha Inscripción',
            'Dorsal Recogido',
        ];

        $callback = function() use ($inscripciones, $headers) {
            $file = fopen('php://output', 'w');
            
            // BOM para Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Cabeceras
            fputcsv($file, $headers);
            
            // Datos
            foreach ($inscripciones as $i) {
                fputcsv($file, [
                    $i->id,
                    $i->numero_pedido ?? '',
                    $i->edicion->anio,
                    $i->participante->dni,
                    $i->participante->nombre,
                    $i->participante->apellidos,
                    $i->participante->email,
                    $i->participante->telefono,
                    $i->participante->direccion,
                    $i->participante->codigo_postal,
                    $i->participante->poblacion,
                    $i->participante->provincia,
                    $i->participante->genero === 'M' ? 'Masculí' : ($i->participante->genero === 'F' ? 'Femení' : $i->participante->genero),
                    $i->participante->fecha_nacimiento,
                    $i->es_socio_uec ? 'Sí' : 'No',
                    $i->esta_federado ? 'Sí' : 'No',
                    $i->club ?? '',
                    $i->numero_licencia ?? '',
                    $i->necesita_autobus ? 'Sí' : 'No',
                    $i->parada_autobus ?? '',
                    $i->talla_camiseta_caro,
                    $i->talla_camiseta_pauls,
                    $i->seguro_anulacion ? 'Sí' : 'No',
                    number_format($i->precio_total, 2, ',', '') . '€',
                    $i->descuento_cupon ? number_format($i->descuento_cupon, 2, ',', '') . '€' : '',
                    $i->estado_pago === 'pagado' ? 'Pagat' : 'Invitat',
                    $i->fecha_pago ?? '',
                    substr($i->created_at, 0, 10),
                    $i->dorsal_recogido ? 'Sí' : 'No',
                ]);
            }
            
            fclose($file);
        };

        $filename = 'inscripcions_confirmades_' . date('Y-m-d') . '.csv';

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}