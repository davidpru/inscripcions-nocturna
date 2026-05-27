<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivacioLlistaEspera;
use App\Models\CambioDorsal;
use App\Models\Inscripcion;
use App\Models\Edicion;
use App\Models\Participante;
use App\Mail\EnlacActivacioLlistaEspera;
use App\Mail\InscripcionConfirmada;
use App\Services\TarifaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InscripcionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Inscripcion::with([
            'participante',
            'edicion',
            'cupon:id,codigo,descripcion,descuento_tipo,descuento_valor',
            'cambioDorsalPendent:id,inscripcion_id,token,expires_at',
        ])
            ->orderBy('created_at', 'desc');

        // Filtrar por edición si se especifica y tiene valor
        if ($request->filled('edicion_id')) {
            $query->where('edicion_id', $request->edicion_id);
        }

        // Filtrar por búsqueda (nombre, apellidos, DNI, email)
        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->whereHas('participante', function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('apellidos', 'like', "%{$busqueda}%")
                    ->orWhere('dni', 'like', "%{$busqueda}%")
                    ->orWhere('email', 'like', "%{$busqueda}%");
            });
        }

        // Clonar query para contar pagadas (incluye invitadas)
        $queryPagadas = clone $query;
        $queryPagadas->whereIn('estado_pago', ['pagado', 'invitado', 'compromiso']);
        
        $inscripciones = $query->get();
        $ediciones = Edicion::orderBy('anio', 'desc')->get();
        
        // Calcular total de inscripciones pagadas (respetando filtros)
        $totalInscripcionesPagadas = $queryPagadas->count();

        return Inertia::render('Admin/Inscripciones/Index', [
            'inscripciones' => $inscripciones,
            'ediciones' => $ediciones,
            'filtros' => $request->only(['edicion_id', 'busqueda']),
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
            'estado_pago' => 'required|in:pendiente,pagado,invitado,compromiso,lista_espera',
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

        // Calcular precio (0 si es invitado o compromiso)
        if (in_array($validated['estado_pago'], ['invitado', 'compromiso'])) {
            $precioTotal = 0;
            $tarifaAplicada = $validated['estado_pago'] === 'compromiso' ? 'Compromiso' : 'Invitado';
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
            'fecha_pago' => in_array($validated['estado_pago'], ['pagado', 'invitado', 'compromiso']) ? now() : null,
            'hash_token' => \Illuminate\Support\Str::random(32),
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
            'estado_pago' => 'required|in:pendiente,pagado,cancelado,invitado,compromiso,lista_espera',
            'es_socio_uec' => 'boolean',
            'esta_federado' => 'boolean',
            'numero_licencia' => 'nullable|string|max:50',
            'club' => 'nullable|string|max:100',
            'necesita_autobus' => 'boolean',
            'parada_autobus' => 'nullable|string|max:100',
            'seguro_anulacion' => 'boolean',
            'es_celiaco' => 'boolean',
            'talla_camiseta_caro' => 'required|string|max:10',
            'talla_camiseta_pauls' => 'required|string|max:10',
            'numero_dorsal' => [
                'nullable',
                'integer',
                'min:1',
                \Illuminate\Validation\Rule::unique('inscripciones', 'numero_dorsal')
                    ->where('edicion_id', $inscripcion->edicion_id)
                    ->ignore($inscripcion->id),
            ],
        ], [
            'numero_dorsal.unique' => 'Aquest dorsal ja està assignat a una altra inscripció d\'aquesta edició.',
        ]);

        // Si el DNI cambia y ya existe en otro participante, re-enlazar la inscripción
        // a ese participante existente (preserva histórico). Si no, update normal.
        $dniNormalizado = strtoupper($validated['dni']);
        $participanteExistente = Participante::where('dni', $dniNormalizado)
            ->where('id', '!=', $inscripcion->participante_id)
            ->first();

        $datosParticipante = [
            'nombre' => $validated['nombre'],
            'apellidos' => $validated['apellidos'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'],
            'direccion' => $validated['direccion'],
            'codigo_postal' => $validated['codigo_postal'],
            'poblacion' => $validated['poblacion'],
            'provincia' => $validated['provincia'],
            'genero' => $validated['genero'],
            'fecha_nacimiento' => $validated['fecha_nacimiento'],
        ];

        if ($participanteExistente) {
            $participanteExistente->update($datosParticipante);
            $inscripcion->participante_id = $participanteExistente->id;
            $inscripcion->save();
            $inscripcion->setRelation('participante', $participanteExistente);
        } else {
            $inscripcion->participante->update(array_merge($datosParticipante, [
                'dni' => $dniNormalizado,
            ]));
        }

        // Recalcular precio y tarifa SOLO si cambian campos que afectan al precio
        $camposTarifa = ['es_socio_uec', 'esta_federado', 'necesita_autobus', 'seguro_anulacion', 'estado_pago'];
        $cambiaTarifa = false;
        foreach ($camposTarifa as $campo) {
            $valorActual = $campo === 'estado_pago'
                ? $inscripcion->$campo
                : (bool) $inscripcion->$campo;
            $valorNuevo = $campo === 'estado_pago'
                ? $validated[$campo]
                : (bool) ($validated[$campo] ?? false);
            if ($valorActual !== $valorNuevo) {
                $cambiaTarifa = true;
                break;
            }
        }

        if ($cambiaTarifa) {
            if (in_array($validated['estado_pago'], ['invitado', 'compromiso'])) {
                $precioTotal = 0;
                $tarifaAplicada = $validated['estado_pago'] === 'compromiso' ? 'Compromiso' : 'Invitado';
                $descuentoCupon = 0;
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
        } else {
            // Mantener precio original
            $precioTotal = $inscripcion->precio_total;
            $tarifaAplicada = $inscripcion->tarifa_aplicada;
            $descuentoCupon = $inscripcion->descuento_cupon;
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
            'es_celiaco' => $validated['es_celiaco'] ?? false,
            'talla_camiseta_caro' => $validated['talla_camiseta_caro'],
            'talla_camiseta_pauls' => $validated['talla_camiseta_pauls'],
            'numero_dorsal' => array_key_exists('numero_dorsal', $validated) ? $validated['numero_dorsal'] : $inscripcion->numero_dorsal,
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

    public function generarEnlaceCambioDorsal(Inscripcion $inscripcion)
    {
        $inscripcion->load(['participante', 'edicion']);

        if (!in_array($inscripcion->estado_pago, ['pagado', 'invitado', 'compromiso'])) {
            return response()->json(['error' => 'La inscripció ha d\'estar pagada per generar un canvi de dorsal.'], 422);
        }

        // Invalidar tokens anteriores pendientes para esta inscripción
        CambioDorsal::where('inscripcion_id', $inscripcion->id)
            ->where('estado', 'pendiente')
            ->update(['estado' => 'caducado']);

        $cambioDorsal = CambioDorsal::create([
            'inscripcion_id'              => $inscripcion->id,
            'token'                       => Str::random(64),
            'estado'                      => 'pendiente',
            'precio'                      => 10.00,
            'expires_at'                  => now()->addHours(48),
            'email_participante_original' => $inscripcion->participante->email,
            'nombre_participante_original'=> $inscripcion->participante->nombre . ' ' . $inscripcion->participante->apellidos,
        ]);

        $url = route('canvi-dorsal.show', $cambioDorsal->token);

        return response()->json(['url' => $url]);
    }

    public function generarEnlacActivacioLlistaEspera(Inscripcion $inscripcion)
    {
        $inscripcion->load(['participante', 'edicion']);

        if ($inscripcion->estado_pago !== 'lista_espera') {
            return response()->json(['error' => 'La inscripció ha d\'estar en llista d\'espera per generar l\'activació.'], 422);
        }

        // Invalidar activacions anteriors pendents
        ActivacioLlistaEspera::where('inscripcion_id', $inscripcion->id)
            ->where('estado', 'pendiente')
            ->update(['estado' => 'caducado']);

        $activacio = ActivacioLlistaEspera::create([
            'inscripcion_id' => $inscripcion->id,
            'token'          => Str::random(64),
            'estado'         => 'pendiente',
            'expires_at'     => now()->addHours(48),
        ]);

        $url = route('activacio-llista-espera.show', $activacio->token);

        return response()->json(['url' => $url]);
    }

    public function enviarEnlacActivacioLlistaEspera(Inscripcion $inscripcion)
    {
        $inscripcion->load(['participante', 'edicion']);

        if ($inscripcion->estado_pago !== 'lista_espera') {
            return response()->json(['error' => 'La inscripció ha d\'estar en llista d\'espera.'], 422);
        }

        // Buscar activació vigent o crear-ne una de nova
        $activacio = ActivacioLlistaEspera::where('inscripcion_id', $inscripcion->id)
            ->where('estado', 'pendiente')
            ->where('expires_at', '>', now())
            ->first();

        if (!$activacio) {
            // Invalidar anteriors
            ActivacioLlistaEspera::where('inscripcion_id', $inscripcion->id)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'caducado']);

            $activacio = ActivacioLlistaEspera::create([
                'inscripcion_id' => $inscripcion->id,
                'token'          => Str::random(64),
                'estado'         => 'pendiente',
                'expires_at'     => now()->addHours(48),
            ]);
        }

        $url = route('activacio-llista-espera.show', $activacio->token);

        try {
            Mail::to($inscripcion->participante->email)
                ->send(new EnlacActivacioLlistaEspera($inscripcion, $url));
            return response()->json(['ok' => true, 'url' => $url]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error enviant el correu: ' . $e->getMessage()], 500);
        }
    }

    public function toggleDorsalRecogido(Inscripcion $inscripcion)
    {
        $inscripcion->dorsal_recogido = !$inscripcion->dorsal_recogido;
        $inscripcion->save();

        return redirect()->back();
    }

    public function exportar(Request $request): StreamedResponse
    {
        $query = Inscripcion::with(['participante', 'edicion'])
            ->whereIn('estado_pago', ['pagado', 'invitado', 'compromiso'])
            ->orderByRaw('numero_dorsal IS NULL, numero_dorsal ASC')
            ->orderBy('created_at');

        if ($request->filled('edicion_id')) {
            $query->where('edicion_id', $request->edicion_id);
        }

        $inscripciones = $query->get();

        $headers = [
            'Dorsal',
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

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inscripcions');
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($inscripciones as $i) {
            $p = $i->participante;
            $numeroPedido = $i->numero_pedido ?: ($i->estado_pago === 'compromiso' ? 'Compromís' : ($i->estado_pago === 'invitado' ? 'Invitat' : ''));
            $sheet->fromArray([
                $i->numero_dorsal ?? '',
                $numeroPedido,
                $i->edicion->anio,
                strtoupper($p->dni ?? ''),
                mb_strtoupper($p->nombre ?? '', 'UTF-8'),
                mb_strtoupper($p->apellidos ?? '', 'UTF-8'),
                $p->email ?? '',
                $p->telefono ?? '',
                $p->direccion ?? '',
                $p->codigo_postal ?? '',
                $p->poblacion ?? '',
                $p->provincia ?? '',
                $p->genero === 'M' ? 'Masculí' : ($p->genero === 'F' ? 'Femení' : $p->genero),
                $p->fecha_nacimiento,
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
                $i->estado_pago === 'pagado' ? 'Pagat' : ($i->estado_pago === 'compromiso' ? 'Compromís' : 'Invitat'),
                $i->fecha_pago ?? '',
                substr($i->created_at, 0, 10),
                $i->dorsal_recogido ? 'Sí' : 'No',
            ], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        foreach (['AA', 'AB', 'AC', 'AD'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'inscripcions_confirmades_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportar9hSports(Request $request): StreamedResponse
    {
        $query = Inscripcion::with(['participante', 'edicion'])
            ->whereIn('estado_pago', ['pagado', 'invitado', 'compromiso']);

        if ($request->filled('edicion_id')) {
            $query->where('edicion_id', $request->edicion_id);
        }

        $inscripciones = $query
            ->orderByRaw('numero_dorsal IS NULL, numero_dorsal ASC')
            ->orderBy('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inscrits');

        $headers = [
            'Nombre',
            'Apellidos',
            'DNI',
            'Sexe',
            'Data naixement',
            'Email',
            'Telefon',
            'Direcció',
            'Códi Postal',
            'Talla',
            'Club',
            'Preu',
            'Num. Federado',
            'Cursa',
            'Comentari',
            'Dorsal',
            'Dorsal equip',
            'Nació',
        ];
        $sheet->fromArray($headers, null, 'A1');

        $sexoMap = ['masculino' => 'H', 'femenino' => 'D'];
        $row = 2;
        foreach ($inscripciones as $i) {
            $p = $i->participante;
            if (!$p) {
                continue;
            }

            $fechaNac = $p->fecha_nacimiento
                ? \Carbon\Carbon::parse($p->fecha_nacimiento)->format('j-n-Y')
                : '';

            $sheet->fromArray([
                mb_strtoupper($p->nombre ?? '', 'UTF-8'),
                mb_strtoupper($p->apellidos ?? '', 'UTF-8'),
                strtoupper($p->dni ?? ''),
                $sexoMap[$p->genero] ?? '',
                $fechaNac,
                $p->email ?? '',
                $p->telefono ?? '',
                $p->direccion ?? '',
                $p->codigo_postal ?? '',
                '',
                mb_strtoupper($i->club ?? '', 'UTF-8'),
                '',
                $i->numero_licencia ?? '',
                'XXIX Travessia Nocturna Fredes-Paüls',
                '',
                $i->numero_dorsal ?? '',
                '',
                'ES',
            ], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = '9hsports_inscrits_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}