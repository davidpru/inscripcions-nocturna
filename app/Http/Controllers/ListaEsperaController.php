<?php

namespace App\Http\Controllers;

use App\Models\Edicion;
use App\Models\Inscripcion;
use App\Models\Participante;
use App\Services\TarifaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ListaEsperaController extends Controller
{
    public function __construct(
        private TarifaService $tarifaService
    ) {}

    public function index(): Response
    {
        $edicionActiva = Edicion::where('activa', true)
            ->orderBy('anio', 'desc')
            ->first();

        if (!$edicionActiva) {
            abort(404, 'No hay ediciones activas');
        }

        return Inertia::render('Inscripcion/ListaEspera', [
            'edicion' => $edicionActiva,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
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
        ], [
            'numero_licencia.required_if' => 'El número de llicència és obligatori si estàs federat.',
            'parada_autobus.required_if' => 'Has de seleccionar una parada d\'autobús.',
        ]);

        // Crear o actualizar participante
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

        // Verificar si ya tiene inscripción activa
        $inscripcionExistente = Inscripcion::where('participante_id', $participante->id)
            ->where('edicion_id', $validated['edicion_id'])
            ->whereIn('estado_pago', ['pagado', 'invitado', 'lista_espera'])
            ->first();

        if ($inscripcionExistente) {
            $msg = $inscripcionExistente->estado_pago === 'lista_espera'
                ? 'Ja estàs a la llista d\'espera per a aquesta edició.'
                : 'Ja estàs inscrit en aquesta edició.';
            return back()->withErrors(['dni' => $msg]);
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

        // Crear inscripción en lista de espera
        Inscripcion::create([
            'participante_id' => $participante->id,
            'edicion_id' => $validated['edicion_id'],
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
            'descuento_cupon' => 0,
            'estado_pago' => 'lista_espera',
        ]);

        return Inertia::render('Inscripcion/ListaEsperaConfirmacion', [
            'edicion' => $edicion,
            'nombre' => $validated['nombre'],
        ]);
    }
}
