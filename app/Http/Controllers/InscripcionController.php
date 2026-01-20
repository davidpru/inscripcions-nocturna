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
        ]);

        $participante = Participante::where('dni', $request->dni)->first();

        return response()->json([
            'encontrado' => (bool) $participante,
            'datos' => $participante,
        ]);
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

        return redirect()->route('inscripcion.confirmacion', $inscripcion)
            ->with('success', 'Inscripción realizada con éxito');
    }

    public function confirmacion(Inscripcion $inscripcion): Response
    {
        $inscripcion->load(['participante', 'edicion']);

        return Inertia::render('Inscripcion/Confirmacion', [
            'inscripcion' => $inscripcion,
        ]);
    }
}
