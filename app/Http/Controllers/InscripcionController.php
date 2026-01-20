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

        // Si está pagada
        if ($inscripcion->estado_pago === 'pagado') {
             // Usamos la vista de éxito de pago para mostrar que todo está OK, 
             // o podríamos crear una vista específica de "Estado Inscripción".
             // Por ahora, reutilizamos la lógica de éxito visual si es posible, 
             // pero la ruta redsys.success requiere parámetros de Redsys.
             // Mejor redirigir a un metodo confirmacion si existe o renderizar Exito directamente.
             
             // Vamos a renderizar la vista de Exito directamente como si fuera confirmación
             return Inertia::render('Pago/Exito', [
                'inscripcion' => $inscripcion->load(['participante', 'edicion']),
            ]);
        }

        // Si está pendiente, permitir pagar de nuevo
        return redirect()->route('redsys.procesar', $inscripcion);
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
                'seguro_anulacion' => 'required|boolean',
                'talla_camiseta_caro' => 'required|string|max:10',
                'talla_camiseta_pauls' => 'required|string|max:10',
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

            // Verificar si ya está inscrito
            $yaInscrito = Inscripcion::where('participante_id', $participante->id)
                ->where('edicion_id', $validated['edicion_id'])
                ->exists();

            if ($yaInscrito) {
                \Illuminate\Support\Facades\Log::warning('Usuario ya inscrito', ['dni' => $validated['dni']]);
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

            // Crear inscripción
            $inscripcion = Inscripcion::create([
                'participante_id' => $participante->id,
                'edicion_id' => $validated['edicion_id'],
                'es_socio_uec' => $validated['es_socio_uec'],
                'esta_federado' => $validated['esta_federado'],
                'numero_licencia' => $validated['numero_licencia'],
                'club' => $validated['club'],
                'necesita_autobus' => $validated['necesita_autobus'],
                'seguro_anulacion' => $validated['seguro_anulacion'],
                'talla_camiseta_caro' => $validated['talla_camiseta_caro'],
                'talla_camiseta_pauls' => $validated['talla_camiseta_pauls'],
                'tarifa_aplicada' => $precio['tarifa_base'],
                'precio_total' => $precio['precio_total'],
                'estado_pago' => 'pendiente',
            ]);

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
