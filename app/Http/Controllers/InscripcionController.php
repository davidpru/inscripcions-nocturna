<?php

namespace App\Http\Controllers;

use App\Models\Edicion;
use App\Models\Participante;
use App\Models\Inscripcion;
use App\Services\TarifaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InscripcionController extends Controller
{
    public function __construct(
        private TarifaService $tarifaService
    ) {}

    /**
     * Listado público de inscritos
     */
    public function listado(): Response
    {
        $edicionActiva = Edicion::where('estado', 'abierta')
            ->orderBy('anio', 'desc')
            ->first();

        if (!$edicionActiva) {
            abort(404, 'No hay ediciones abiertas');
        }

        $inscritos = Inscripcion::where('edicion_id', $edicionActiva->id)
            ->where('estado_pago', 'pagado')
            ->with(['participante:id,nombre,apellidos,poblacion'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($inscripcion) => [
                'id' => $inscripcion->id,
                'participante' => $inscripcion->participante,
                'club' => $inscripcion->club,
            ]);

        return Inertia::render('Inscripcion/Listado', [
            'edicion' => $edicionActiva,
            'inscritos' => $inscritos,
        ]);
    }

    public function index(Request $request): Response
    {
        $edicionActiva = Edicion::where('estado', 'abierta')
            ->orderBy('anio', 'desc')
            ->first();

        if (!$edicionActiva) {
            abort(404, 'No hay ediciones abiertas');
        }

        return Inertia::render('Inscripcion/Index', [
            'edicion' => $edicionActiva,
            'inscripcionesAbiertas' => $edicionActiva->inscripcionesAbiertas(),
            'fechaInicioInscripciones' => $edicionActiva->fecha_inicio_inscripciones?->toIso8601String(),
            'dni' => $request->query('dni'),
            'participante' => $request->query('participante'),
        ]);
    }

    public function buscarParticipante(Request $request)
    {
        $request->validate([
            'dni' => 'required|string|max:20',
            'edicion_id' => 'nullable|exists:ediciones,id',
        ]);

        $participante = Participante::where('dni', $request->dni)->first();
        
        $yaInscrito = false;
        if ($participante && $request->edicion_id) {
            $yaInscrito = Inscripcion::where('participante_id', $participante->id)
                ->where('edicion_id', $request->edicion_id)
                ->where('estado_pago', 'pagado')
                ->exists();
        }

        return response()->json([
            'encontrado' => (bool) $participante,
            'ya_inscrito' => $yaInscrito,
            'datos' => $participante,
        ]);
    }

    public function buscarInscripcion(Request $request)
    {
        $request->validate([
            'dni' => 'required|string',
            'fecha_nacimiento' => 'required|date',
        ]);

        $participante = Participante::where('dni', strtoupper($request->dni))
            ->whereDate('fecha_nacimiento', $request->fecha_nacimiento)
            ->first();

        if (!$participante) {
            return back()->withErrors(['dni' => 'No se ha encontrado ninguna inscripción con estos datos.']);
        }

        // Buscar edición activa
        $edicionActiva = Edicion::where('activa', true)
            ->orderBy('anio', 'desc')
            ->first();
            
        if (!$edicionActiva) {
             return back()->withErrors(['general' => 'No hay una edición activa en este momento.']);
        }

        $inscripcion = Inscripcion::where('participante_id', $participante->id)
            ->where('edicion_id', $edicionActiva->id)
            ->first();

        if (!$inscripcion) {
             return back()->withErrors(['dni' => 'No tienes ninguna inscripción para la edición actual.']);
        }

        // Mostrar siempre la vista de detalle de inscripción
        return Inertia::render('Inscripcion/Detalle', [
            'inscripcion' => $inscripcion->load(['participante', 'edicion']),
            'precioAutobus' => $edicionActiva->precio_autobus ?? 12,
        ]);
    }

    public function contratarAutobus(Request $request, Inscripcion $inscripcion)
    {
        $request->validate([
            'parada_autobus' => 'required|in:tortosa,roquetes,pauls',
        ]);

        // Verificar que la inscripción está pagada y no tiene autobús
        if ($inscripcion->estado_pago !== 'pagado') {
            return back()->withErrors(['general' => 'La inscripción debe estar pagada para contratar el autobús.']);
        }

        if ($inscripcion->necesita_autobus) {
            return back()->withErrors(['general' => 'Ya tienes contratado el servicio de autobús.']);
        }

        // Guardar la parada temporalmente y redirigir al pago
        $inscripcion->update([
            'parada_autobus_pendiente' => $request->parada_autobus,
        ]);

        return redirect()->route('redsys.procesar-autobus', $inscripcion);
    }

    public function cambiarParada(Request $request, Inscripcion $inscripcion)
    {
        $request->validate([
            'parada_autobus' => 'required|in:tortosa,roquetes,pauls',
        ]);

        // Verificar que la inscripción está pagada y tiene autobús
        if ($inscripcion->estado_pago !== 'pagado') {
            return back()->withErrors(['general' => 'La inscripción debe estar pagada.']);
        }

        if (!$inscripcion->necesita_autobus) {
            return back()->withErrors(['general' => 'No tienes contratado el servicio de autobús.']);
        }

        // Actualizar la parada
        $inscripcion->update([
            'parada_autobus' => $request->parada_autobus,
        ]);

        return back()->with('success', 'Parada de autobús actualizada correctamente.');
    }

    public function calcularPrecio(Request $request)
    {
        $request->validate([
            'edicion_id' => 'required|exists:ediciones,id',
            'es_socio_uec' => 'required|boolean',
            'esta_federado' => 'required|boolean',
            'necesita_autobus' => 'required|boolean',
            'seguro_anulacion' => 'required|boolean',
        ]);

        $edicion = Edicion::findOrFail($request->edicion_id);

        $precio = $this->tarifaService->calcularPrecio(
            $edicion,
            $request->es_socio_uec,
            $request->esta_federado,
            $request->necesita_autobus,
            $request->seguro_anulacion
        );

        return response()->json($precio);
    }

    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Inicio inscripción store', $request->all());
        
        try {
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
                'poblacion' => 'required|string|max:255',
                'provincia' => 'required|string|max:255',
                
                // Datos de la inscripción
                'edicion_id' => 'required|exists:ediciones,id',
                'es_socio_uec' => 'required|boolean',
                'esta_federado' => 'required|boolean',
                'numero_licencia' => 'nullable|required_if:esta_federado,true|string|max:50',
                'club' => 'nullable|string|max:255',
                'necesita_autobus' => 'required|boolean',
                'parada_autobus' => 'nullable|required_if:necesita_autobus,true|in:tortosa,pauls',
                'seguro_anulacion' => 'required|boolean',
                'talla_camiseta_caro' => 'required|string|max:10',
                'talla_camiseta_pauls' => 'required|string|max:10',
                'es_celiaco' => 'required|in:si,no',
                'acepta_reglamento' => 'required|accepted',
            ]);

            \Illuminate\Support\Facades\Log::info('Validación correcta');

            // Crear o actualizar participante
            $participante = Participante::updateOrCreate(
                ['dni' => $validated['dni']],
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

            // Verificar si ya está inscrito (independientemente del pago)
            $inscripcionExistente = Inscripcion::where('participante_id', $participante->id)
                ->where('edicion_id', $validated['edicion_id'])
                ->first();

            if ($inscripcionExistente && $inscripcionExistente->estado_pago === 'pagado') {
                \Illuminate\Support\Facades\Log::warning('Usuario ya inscrito y pagado', ['dni' => $validated['dni']]);
                return back()->withErrors(['dni' => 'Ya estás inscrito en esta edición']);
            }

            // Calcular precio
            $edicion = Edicion::findOrFail($validated['edicion_id']);
            $precio = $this->tarifaService->calcularPrecio(
                $edicion,
                $validated['es_socio_uec'],
                $validated['esta_federado'],
                $validated['necesita_autobus'],
                $validated['seguro_anulacion']
            );

            $datosInscripcion = [
                'es_socio_uec' => $validated['es_socio_uec'],
                'esta_federado' => $validated['esta_federado'],
                'numero_licencia' => $validated['numero_licencia'],
                'club' => $validated['club'],
                'necesita_autobus' => $validated['necesita_autobus'],
                'parada_autobus' => $validated['parada_autobus'] ?? null,
                'seguro_anulacion' => $validated['seguro_anulacion'],
                'talla_camiseta_caro' => $validated['talla_camiseta_caro'],
                'talla_camiseta_pauls' => $validated['talla_camiseta_pauls'],
                'es_celiaco' => $validated['es_celiaco'] === 'si',
                'tarifa_aplicada' => $precio['nombre_tarifa'],
                'precio_total' => $precio['precio_total'],
                'estado_pago' => 'pendiente',
            ];

            if ($inscripcionExistente) {
                // Actualizar inscripción existente si estaba pendiente/rechazada
                $inscripcionExistente->update($datosInscripcion);
                $inscripcion = $inscripcionExistente;
            } else {
                // Crear nueva inscripción
                $datosInscripcion['participante_id'] = $participante->id;
                $datosInscripcion['edicion_id'] = $validated['edicion_id'];
                $inscripcion = Inscripcion::create($datosInscripcion);
            }

            \Illuminate\Support\Facades\Log::info('Inscripción creada, redirigiendo a redsys: ' . $inscripcion->id);

            // Redirigir a la pasarela de pago
            return redirect()->route('redsys.procesar', $inscripcion);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Error de validación', $e->errors());
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error general en store inscripción: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Ha ocurrido un error inesperado. Por favor inténtalo de nuevo.']);
        }
    }

    public function confirmacion(Inscripcion $inscripcion): Response
    {
        $inscripcion->load(['participante', 'edicion']);

        return Inertia::render('Inscripcion/Confirmacion', [
            'inscripcion' => $inscripcion,
        ]);
    }
}