<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use Creagia\Redsys\RedsysRequest;
use Creagia\Redsys\Enums\ConsumerLanguage;
use Creagia\Redsys\Enums\PaymentMethod;
use Creagia\Redsys\Enums\ResponseCode;
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

        // Guardar el número de pedido en la inscripción
        $orderNumber = $inscripcion->id;
        $orderNumberFormatted = str_pad((string)$orderNumber, 4, '0', STR_PAD_LEFT); 
        $inscripcion->update(['numero_pedido' => $orderNumber]);

        $redsysClient = new \Creagia\Redsys\RedsysClient(
            config('redsys.tpv.key'),
            config('redsys.tpv.merchantCode'),
            config('redsys.tpv.terminal'),
            config('redsys.environment') === 'production' ? \Creagia\Redsys\Enums\Environment::Production : \Creagia\Redsys\Enums\Environment::Test
        );

        $redsysRequest = \Creagia\Redsys\RedsysRequest::create($redsysClient, new \Creagia\Redsys\RequestParameters(
            amount: (float)$inscripcion->precio_total,
            order: $orderNumberFormatted,
            transactionType: \Creagia\Redsys\Enums\TransactionType::Autorizacion,
            currency: \Creagia\Redsys\Enums\Currency::EUR,
            merchantUrl: route('redsys.notification'),
            urlOk: route('redsys.success'),
            urlKo: route('redsys.error')
        ));
        
        $redsysRequest->setMerchantData($inscripcion->id);
        $redsysRequest->setProductDescription('Inscripción Nocturna Fredes Paüls ' . $inscripcion->edicion->anio);
        $redsysRequest->setTitular($inscripcion->participante->nombre . ' ' . $inscripcion->participante->apellidos);
        $redsysRequest->setConsumerLanguage(ConsumerLanguage::CA);

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
            $notification = RedsysRequest::getNotification($request->all());
            
            // Validar la firma de la notificación
            if (!$notification->isValid()) {
                Log::error('Redsys notification: Invalid signature', [
                    'data' => $request->all()
                ]);
                return response('Invalid signature', 400);
            }

            // Obtener la inscripción
            $inscripcion = Inscripcion::where('numero_pedido', $notification->getOrder())->first();
            
            if (!$inscripcion) {
                Log::error('Redsys notification: Inscripcion not found', [
                    'order' => $notification->getOrder()
                ]);
                return response('Inscripcion not found', 404);
            }

            // Verificar el estado del pago
            if ($notification->isSuccessful()) {
                $inscripcion->update([
                    'estado_pago' => 'pagado',
                    'numero_autorizacion' => $notification->getAuthorisationCode(),
                    'fecha_pago' => now(),
                ]);

                Log::info('Redsys notification: Payment successful', [
                    'inscripcion_id' => $inscripcion->id,
                    'amount' => $notification->getAmount(),
                ]);
            } else {
                $inscripcion->update([
                    'estado_pago' => 'rechazado',
                ]);

                Log::warning('Redsys notification: Payment failed', [
                    'inscripcion_id' => $inscripcion->id,
                    'response_code' => $notification->getResponseCode(),
                ]);
            }

            return response('OK', 200);
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
        try {
            $response = RedsysRequest::getNotification($request->all());
            
            if (!$response->isValid()) {
                Log::error('Redsys success: Invalid signature');
                return redirect()->route('redsys.error');
            }

            $inscripcion = Inscripcion::where('numero_pedido', $response->getOrder())->first();

            if (!$inscripcion) {
                Log::error('Redsys success: Inscripcion not found');
                return redirect()->route('redsys.error');
            }

            // Asegurar que guardamos la transacción aunque el webhook se retrase
            $inscripcion->update([
                'estado_pago' => 'pagado',
                'numero_autorizacion' => $response->getAuthorisationCode(), // Guardar ID transacción
                'fecha_pago' => now(),
            ]);

            return Inertia::render('Pago/Exito', [
                'inscripcion' => $inscripcion->load(['participante', 'edicion']),
            ]);
        } catch (\Exception $e) {
            Log::error('Redsys success error', ['error' => $e->getMessage()]);
            return redirect()->route('redsys.error');
        }
    }

    /**
     * Usuario es redirigido aquí si el pago falla o cancela.
     */
    public function error(Request $request)
    {
        try {
            $response = RedsysRequest::getNotification($request->all());
            
            $inscripcion = null;
            $errorMessage = 'El pago no pudo ser procesado.';

            if ($response->isValid()) {
                $inscripcion = Inscripcion::where('numero_pedido', $response->getOrder())->first();
                
                $responseCode = $response->getResponseCode();
                $errorMessage = $this->getErrorMessage($responseCode);
            }

            return Inertia::render('Pago/Error', [
                'inscripcion' => $inscripcion,
                'errorMessage' => $errorMessage,
            ]);
        } catch (\Exception $e) {
            Log::error('Redsys error page', ['error' => $e->getMessage()]);
            
            return Inertia::render('Pago/Error', [
                'inscripcion' => null,
                'errorMessage' => 'Ha ocurrido un error al procesar el pago.',
            ]);
        }
    }

    private function getErrorMessage($responseCode): string
    {
        return match ($responseCode) {
            ResponseCode::CANCELLED_BY_USER->value => 'Has cancelado el pago.',
            ResponseCode::EXPIRED_CARD->value => 'La tarjeta ha caducado.',
            ResponseCode::INSUFFICIENT_FUNDS->value => 'No hay fondos suficientes.',
            ResponseCode::CARD_NOT_VALID->value => 'Tarjeta no válida.',
            default => 'El pago no pudo ser procesado. Código: ' . $responseCode,
        };
    }
}