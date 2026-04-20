<?php

namespace App\Http\Controllers;

use App\Models\CambioDorsal;
use App\Models\Inscripcion;
use App\Models\Participante;
use Creagia\Redsys\Enums\ConsumerLanguage;
use Creagia\Redsys\Enums\Currency;
use Creagia\Redsys\Enums\Environment;
use Creagia\Redsys\Enums\TransactionType;
use Creagia\Redsys\RedsysClient;
use Creagia\Redsys\RedsysRequest;
use Creagia\Redsys\Support\RequestParameters;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CambioDorsalController extends Controller
{
    /**
     * Mostrar formulario público de canvi de dorsal.
     */
    public function show(string $token)
    {
        $cambioDorsal = CambioDorsal::where('token', $token)
            ->with(['inscripcion.participante', 'inscripcion.edicion'])
            ->firstOrFail();

        if ($cambioDorsal->estaCompletado()) {
            return Inertia::render('CambioDorsal/Completat');
        }

        if (!$cambioDorsal->estaVigente()) {
            return Inertia::render('CambioDorsal/Caducat');
        }

        $inscripcion = $cambioDorsal->inscripcion;

        return Inertia::render('CambioDorsal/Form', [
            'cambioDorsal' => [
                'id' => $cambioDorsal->id,
                'token' => $cambioDorsal->token,
                'precio_base' => (float) $cambioDorsal->precio,
                'expires_at' => $cambioDorsal->expires_at->toIso8601String(),
            ],
            'inscripcion' => [
                'id' => $inscripcion->id,
                'esta_federado' => (bool) $inscripcion->esta_federado,
                'talla_camiseta_caro' => $inscripcion->talla_camiseta_caro,
                'talla_camiseta_pauls' => $inscripcion->talla_camiseta_pauls,
                'necesita_autobus' => $inscripcion->necesita_autobus,
                'parada_autobus' => $inscripcion->parada_autobus,
                'edicion' => [
                    'anio' => $inscripcion->edicion->anio,
                    'nombre' => $inscripcion->edicion->nombre ?? ('Nocturna Fredes-Paüls ' . $inscripcion->edicion->anio),
                    'precio_licencia_federativa_socio' => (float) ($inscripcion->edicion->precio_licencia_federativa_socio ?? 0),
                    'precio_licencia_federativa_publico' => (float) ($inscripcion->edicion->precio_licencia_federativa_publico ?? 0),
                ],
            ],
        ]);
    }

    /**
     * Guardar datos del nuevo participante y redirigir a Redsys.
     */
    public function procesar(Request $request, string $token)
    {
        $cambioDorsal = CambioDorsal::where('token', $token)
            ->with(['inscripcion.participante', 'inscripcion.edicion'])
            ->firstOrFail();

        if (!$cambioDorsal->estaVigente()) {
            return back()->withErrors(['token' => 'L\'enllaç ha caducat o ja ha estat utilitzat.']);
        }

        $validated = $request->validate([
            'dni'             => 'required|string|max:20',
            'nombre'          => 'required|string|max:100',
            'apellidos'       => 'required|string|max:150',
            'genero'          => 'required|in:masculino,femenino',
            'fecha_nacimiento'=> 'required|date',
            'telefono'        => 'required|string|max:20',
            'email'           => 'required|email|max:200',
            'email_confirm'   => 'required|same:email',
            'direccion'       => 'required|string|max:200',
            'codigo_postal'   => 'required|string|max:10',
            'poblacion'       => 'required|string|max:100',
            'provincia'       => 'required|string|max:100',
            'es_socio_uec'    => 'boolean',
            'club'            => 'nullable|string|max:150',
            'esta_federado'   => 'boolean',
            'numero_licencia' => 'nullable|required_if:esta_federado,true|string|max:50',
            'es_celiaco'      => 'boolean',
        ]);

        // Excluir email_confirm del JSON guardado
        unset($validated['email_confirm']);

        // Verificar que el nuevo participante no tenga ya una inscripción en la misma edición
        $inscripcion = $cambioDorsal->inscripcion;
        $participantExistent = Participante::where('dni', strtoupper(trim($validated['dni'])))->first();
        if ($participantExistent) {
            $jaInscrit = Inscripcion::where('participante_id', $participantExistent->id)
                ->where('edicion_id', $inscripcion->edicion_id)
                ->where('id', '!=', $inscripcion->id)
                ->whereIn('estado_pago', ['pagado', 'invitado', 'compromiso'])
                ->exists();
            if ($jaInscrit) {
                return back()->withErrors(['dni' => 'Aquest participant ja té una inscripció activa en aquesta edició.']);
            }
        }

        // Calcular precio real: base + federativa si origen no la tenía y destino sí
        $precioFinal = (float) $cambioDorsal->precio; // precio base (10€)
        $esFederado = (bool) ($validated['esta_federado'] ?? false);
        $esSoci = (bool) ($validated['es_socio_uec'] ?? false);
        if ($esFederado && !$inscripcion->esta_federado) {
            $precioLicencia = $esSoci
                ? ($inscripcion->edicion->precio_licencia_federativa_socio ?? 0)
                : ($inscripcion->edicion->precio_licencia_federativa_publico ?? 0);
            $precioFinal += (float) $precioLicencia;
        }

        // Guardar datos para usar cuando llegue la notificación de Redsys
        $cambioDorsal->update([
            'datos_nuevo_participante' => $validated,
            'precio' => $precioFinal,
        ]);

        return $this->redirigirRedsys($cambioDorsal);
    }

    private function redirigirRedsys(CambioDorsal $cambioDorsal)
    {
        $inscripcion = $cambioDorsal->inscripcion;

        // Formato: CDOR + 3 dígitos ID cambio dorsal + 5 caracteres timestamp
        $orderNumber = 'CDOR' . str_pad((string)$cambioDorsal->id, 3, '0', STR_PAD_LEFT) . substr((string)time(), -5);

        $cambioDorsal->update(['numero_pedido' => $orderNumber]);

        $redsysClient = new RedsysClient(
            merchantCode: (int) config('redsys.tpv.merchantCode'),
            secretKey: config('redsys.tpv.key'),
            terminal: (int) config('redsys.tpv.terminal'),
            environment: config('redsys.environment') === 'production' ? Environment::Production : Environment::Test
        );

        $amountInCents = (int) ($cambioDorsal->precio * 100);

        $datos = $cambioDorsal->datos_nuevo_participante ?? [];
        $titular = trim(($datos['nombre'] ?? '') . ' ' . ($datos['apellidos'] ?? ''));

        $requestParams = new RequestParameters(
            amountInCents: $amountInCents,
            transactionType: TransactionType::Autorizacion,
            currency: Currency::EUR,
            order: $orderNumber,
            merchantUrl: route('redsys.notification'),
            urlOk: route('redsys.success'),
            urlKo: route('redsys.error'),
            merchantData: 'CDORSAL_' . $cambioDorsal->id,
            productDescription: 'Canvi de dorsal Nocturna Fredes-Paüls ' . $inscripcion->edicion->anio,
            titular: $titular ?: ($inscripcion->participante->nombre . ' ' . $inscripcion->participante->apellidos),
            consumerLanguage: ConsumerLanguage::Spanish
        );

        $redsysRequest = RedsysRequest::create($redsysClient, $requestParams);
        $formFields = $redsysRequest->getRequestFieldsArray();

        $formInputs = [];
        foreach ($formFields as $name => $value) {
            $formInputs[] = ['name' => $name, 'value' => $value];
        }

        $redsysEnvironment = config('redsys.environment', 'test');
        $redsysUrl = $redsysEnvironment === 'production'
            ? 'https://sis.redsys.es/sis/realizarPago'
            : 'https://sis-t.redsys.es:25443/sis/realizarPago';

        return Inertia::render('Pago/Redsys', [
            'inscripcion' => $inscripcion,
            'formAction' => $redsysUrl,
            'formInputs' => $formInputs,
            'esCambioDorsal' => true,
        ]);
    }
}
