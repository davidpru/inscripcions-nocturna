<?php

namespace App\Http\Controllers;

use App\Models\ActivacioLlistaEspera;
use Creagia\Redsys\Enums\ConsumerLanguage;
use Creagia\Redsys\Enums\Currency;
use Creagia\Redsys\Enums\Environment;
use Creagia\Redsys\Enums\TransactionType;
use Creagia\Redsys\RedsysClient;
use Creagia\Redsys\RedsysRequest;
use Creagia\Redsys\Support\RequestParameters;
use Inertia\Inertia;

class ActivacioLlistaEsperaController extends Controller
{
    /**
     * Mostrar pàgina de confirmació/pagament per activar la plaça.
     */
    public function show(string $token)
    {
        $activacio = ActivacioLlistaEspera::where('token', $token)
            ->with(['inscripcion.participante', 'inscripcion.edicion'])
            ->firstOrFail();

        if ($activacio->estaCompletado()) {
            return Inertia::render('ActivacioLlistaEspera/Completat');
        }

        if (!$activacio->estaVigente()) {
            return Inertia::render('ActivacioLlistaEspera/Caducat');
        }

        $inscripcion = $activacio->inscripcion;
        $participant = $inscripcion->participante;
        $edicion = $inscripcion->edicion;

        return Inertia::render('ActivacioLlistaEspera/Form', [
            'activacio' => [
                'id'         => $activacio->id,
                'token'      => $activacio->token,
                'expires_at' => $activacio->expires_at->toIso8601String(),
            ],
            'inscripcion' => [
                'id'                 => $inscripcion->id,
                'precio_total'       => (float) $inscripcion->precio_total,
                'tarifa_aplicada'    => $inscripcion->tarifa_aplicada,
                'necesita_autobus'   => (bool) $inscripcion->necesita_autobus,
                'parada_autobus'     => $inscripcion->parada_autobus,
                'esta_federado'      => (bool) $inscripcion->esta_federado,
                'es_socio_uec'       => (bool) $inscripcion->es_socio_uec,
                'seguro_anulacion'   => (bool) $inscripcion->seguro_anulacion,
                'talla_camiseta_caro'  => $inscripcion->talla_camiseta_caro,
                'talla_camiseta_pauls' => $inscripcion->talla_camiseta_pauls,
                'edicion' => [
                    'anio'   => $edicion->anio,
                    'nombre' => $edicion->nombre ?? ('Nocturna Fredes-Paüls ' . $edicion->anio),
                ],
            ],
            'participant' => [
                'nom'     => $participant->nombre . ' ' . $participant->apellidos,
                'email'   => $participant->email,
                'dni'     => $participant->dni,
            ],
        ]);
    }

    /**
     * Redirigir a Redsys per pagar la plaça.
     */
    public function procesar(string $token)
    {
        $activacio = ActivacioLlistaEspera::where('token', $token)
            ->with(['inscripcion.participante', 'inscripcion.edicion'])
            ->firstOrFail();

        if (!$activacio->estaVigente()) {
            return back()->withErrors(['token' => 'L\'enllaç ha caducat o ja ha estat utilitzat.']);
        }

        $inscripcion = $activacio->inscripcion;

        // Format: ACTI + 3 dígits ID activació + 5 caràcters timestamp (= 12 chars màx Redsys)
        $orderNumber = 'ACTI' . str_pad((string) $activacio->id, 3, '0', STR_PAD_LEFT) . substr((string) time(), -5);
        $activacio->update(['numero_pedido' => $orderNumber]);

        $redsysClient = new RedsysClient(
            merchantCode: (int) config('redsys.tpv.merchantCode'),
            secretKey: config('redsys.tpv.key'),
            terminal: (int) config('redsys.tpv.terminal'),
            environment: config('redsys.environment') === 'production' ? Environment::Production : Environment::Test
        );

        $amountInCents = (int) ($inscripcion->precio_total * 100);
        $titular = $inscripcion->participante->nombre . ' ' . $inscripcion->participante->apellidos;

        $requestParams = new RequestParameters(
            amountInCents: $amountInCents,
            transactionType: TransactionType::Autorizacion,
            currency: Currency::EUR,
            order: $orderNumber,
            merchantUrl: route('redsys.notification'),
            urlOk: route('redsys.success'),
            urlKo: route('redsys.error'),
            merchantData: 'ACTIV_' . $activacio->id,
            productDescription: 'Activació plaça llista d\'espera Nocturna Fredes-Paüls ' . $inscripcion->edicion->anio,
            titular: $titular,
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
            'formAction'  => $redsysUrl,
            'formInputs'  => $formInputs,
            'esActivacio' => true,
        ]);
    }
}
