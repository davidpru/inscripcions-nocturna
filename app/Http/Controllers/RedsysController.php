<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use Creagia\Redsys\RedsysClient;
use Creagia\Redsys\RedsysRequest;
use Creagia\Redsys\Support\RequestParameters;
use Creagia\Redsys\Enums\ConsumerLanguage;
use Creagia\Redsys\Enums\Currency;
use Creagia\Redsys\Enums\Environment;
use Creagia\Redsys\Enums\TransactionType;
use Creagia\Redsys\RedsysResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class RedsysController extends Controller
{
    /**
     * Procesar el pago iniciando la transacción con Redsys
     */
    public function procesarPago(Inscripcion $inscripcion)
    {
        // Verificar que el pago esté pendiente
        if ($inscripcion->estado_pago !== 'pendiente') {
            return redirect()->route('home')
                ->with('error', 'Esta inscripción ya ha sido procesada');
        }

        // Cargar relaciones necesarias
        $inscripcion->load(['participante', 'edicion']);

        // Generar número de pedido único (Redsys no permite repetidos)
        // Formato: 4 dígitos del ID + 8 caracteres timestamp = 12 chars máx
        $orderNumberFormatted = str_pad((string)$inscripcion->id, 4, '0', STR_PAD_LEFT) . substr((string)time(), -8);
        $inscripcion->update(['numero_pedido' => $orderNumberFormatted]);

        $redsysClient = new RedsysClient(
            merchantCode: (int)config('redsys.tpv.merchantCode'),
            secretKey: config('redsys.tpv.key'),
            terminal: (int)config('redsys.tpv.terminal'),
            environment: config('redsys.environment') === 'production' ? Environment::Production : Environment::Test
        );

        // Convertir precio a céntimos (Redsys espera céntimos)
        $amountInCents = (int)($inscripcion->precio_total * 100);

        $requestParams = new RequestParameters(
            amountInCents: $amountInCents,
            transactionType: TransactionType::Autorizacion,
            currency: Currency::EUR,
            order: $orderNumberFormatted,
            merchantUrl: route('redsys.notification'),
            urlOk: route('redsys.success'),
            urlKo: route('redsys.error'),
            merchantData: (string)$inscripcion->id,
            productDescription: 'Inscripción Nocturna Fredes Paüls ' . $inscripcion->edicion->anio,
            titular: $inscripcion->participante->nombre . ' ' . $inscripcion->participante->apellidos,
            consumerLanguage: ConsumerLanguage::Spanish
        );

        $redsysRequest = RedsysRequest::create($redsysClient, $requestParams);

        $formFields = $redsysRequest->getRequestFieldsArray();
        
        // Convertir a formato array de objetos para Vue
        $formInputs = [];
        foreach ($formFields as $name => $value) {
            $formInputs[] = ['name' => $name, 'value' => $value];
        }

        // Determinar URL de Redsys según entorno
        $redsysEnvironment = config('redsys.environment', 'test');
        $redsysUrl = $redsysEnvironment === 'production'
            ? 'https://sis.redsys.es/sis/realizarPago'
            : 'https://sis-t.redsys.es:25443/sis/realizarPago';

        // Renderizar vista con el formulario
        return Inertia::render('Pago/Redsys', [
            'inscripcion' => $inscripcion,
            'formAction' => $redsysUrl,
            'formInputs' => $formInputs,
        ]);
    }

    /**
     * Redsys notifica el resultado del pago aquí (webhook).
     */
    public function notification(Request $request)
    {
        try {
            $redsysClient = new RedsysClient(
                merchantCode: (int)config('redsys.tpv.merchantCode'),
                secretKey: config('redsys.tpv.key'),
                terminal: (int)config('redsys.tpv.terminal'),
                environment: config('redsys.environment') === 'production' ? Environment::Production : Environment::Test
            );

            $redsysResponse = new RedsysResponse($redsysClient);
            $redsysResponse->setParametersFromResponse($request->all());
            
            // Validar firma y obtener parámetros
            $params = $redsysResponse->checkResponse();

            // Obtener la inscripción
            $inscripcion = Inscripcion::where('numero_pedido', $params->order)->first();
            
            if (!$inscripcion) {
                Log::error('Redsys notification: Inscripcion not found', [
                    'order' => $params->order
                ]);
                return response('Inscripcion not found', 404);
            }

            // Pago exitoso
            $inscripcion->update([
                'estado_pago' => 'pagado',
                'numero_autorizacion' => $params->responseAuthorisationCode,
                'fecha_pago' => now(),
            ]);

            Log::info('Redsys notification: Payment successful', [
                'inscripcion_id' => $inscripcion->id,
                'amount' => $params->amount,
            ]);

            return response('OK', 200);
        } catch (\Creagia\Redsys\Exceptions\DeniedRedsysPaymentResponseException $e) {
            Log::warning('Redsys notification: Payment denied', [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);
            return response('Payment denied', 200);
        } catch (\Exception $e) {
            Log::error('Redsys notification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response('Error', 500);
        }
    }

    /**
     * Usuario es redirigido aquí tras pago exitoso.
     */
    public function success(Request $request)
    {
        Log::info('Redsys success: Request received', ['data' => $request->all()]);
        
        try {
            $redsysClient = new RedsysClient(
                merchantCode: (int)config('redsys.tpv.merchantCode'),
                secretKey: config('redsys.tpv.key'),
                terminal: (int)config('redsys.tpv.terminal'),
                environment: config('redsys.environment') === 'production' ? Environment::Production : Environment::Test
            );

            $redsysResponse = new RedsysResponse($redsysClient);
            $redsysResponse->setParametersFromResponse($request->all());
            
            // Validar firma y obtener parámetros
            $params = $redsysResponse->checkResponse();
            
            Log::info('Redsys success: Response params', [
                'order' => $params->order,
                'authCode' => $params->responseAuthorisationCode,
                'merchantData' => $params->merchantData ?? null,
            ]);

            // Buscar por numero_pedido O por ID en merchantData
            $inscripcion = Inscripcion::where('numero_pedido', $params->order)->first();
            
            if (!$inscripcion && isset($params->merchantData)) {
                $inscripcion = Inscripcion::find($params->merchantData);
                Log::info('Redsys success: Found by merchantData', ['id' => $params->merchantData]);
            }

            if (!$inscripcion) {
                Log::error('Redsys success: Inscripcion not found', ['order' => $params->order]);
                return redirect()->route('home')->with('error', 'Inscripción no encontrada');
            }

            // Asegurar que guardamos la transacción aunque el webhook se retrase
            $inscripcion->update([
                'estado_pago' => 'pagado',
                'numero_autorizacion' => $params->responseAuthorisationCode,
                'fecha_pago' => now(),
            ]);

            return Inertia::render('Pago/Exito', [
                'inscripcion' => $inscripcion->load(['participante', 'edicion']),
            ]);
        } catch (\Exception $e) {
            Log::error('Redsys success error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->route('home')->with('error', 'Error procesando el pago');
        }
    }

    /**
     * Usuario es redirigido aquí si el pago falla o cancela.
     */
    public function error(Request $request)
    {
        $errorMessage = 'El pago no pudo ser procesado.';
        $inscripcion = null;

        try {
            $redsysClient = new RedsysClient(
                merchantCode: (int)config('redsys.tpv.merchantCode'),
                secretKey: config('redsys.tpv.key'),
                terminal: (int)config('redsys.tpv.terminal'),
                environment: config('redsys.environment') === 'production' ? Environment::Production : Environment::Test
            );

            $redsysResponse = new RedsysResponse($redsysClient);
            $redsysResponse->setParametersFromResponse($request->all());
            
            $inscripcion = Inscripcion::where('numero_pedido', $redsysResponse->parameters->order)->first();
            $errorMessage = 'El pago fue rechazado. Código: ' . $redsysResponse->parameters->responseCode;
        } catch (\Exception $e) {
            Log::error('Redsys error page', ['error' => $e->getMessage()]);
        }

        return Inertia::render('Pago/Error', [
            'inscripcion' => $inscripcion,
            'errorMessage' => $errorMessage,
        ]);
    }
}