<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Mail\InscripcionConfirmada;
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
use Illuminate\Support\Facades\Mail;
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
     * Procesar el pago del autobús para una inscripción ya pagada
     */
    public function procesarPagoAutobus(Inscripcion $inscripcion)
    {
        // Verificar que la inscripción esté pagada y no tenga autobús
        if ($inscripcion->estado_pago !== 'pagado') {
            return redirect()->route('home')
                ->with('error', 'La inscripción debe estar pagada');
        }

        if ($inscripcion->necesita_autobus) {
            return redirect()->route('home')
                ->with('error', 'Ya tienes contratado el servicio de autobús');
        }

        // Verificar que hay una parada pendiente
        if (!$inscripcion->parada_autobus_pendiente) {
            return redirect()->route('inscripcion.consulta')
                ->with('error', 'Debes seleccionar una parada de autobús');
        }

        $inscripcion->load(['participante', 'edicion']);

        // Generar número de pedido único para el autobús
        // Formato: BUS + 3 dígitos ID + 6 caracteres timestamp
        $orderNumberFormatted = 'BUS' . str_pad((string)$inscripcion->id, 3, '0', STR_PAD_LEFT) . substr((string)time(), -6);
        
        // Guardar en un campo temporal (usaremos numero_pedido_autobus si existe, sino reutilizamos otro campo)
        $inscripcion->update(['numero_pedido_autobus' => $orderNumberFormatted]);

        $redsysClient = new RedsysClient(
            merchantCode: (int)config('redsys.tpv.merchantCode'),
            secretKey: config('redsys.tpv.key'),
            terminal: (int)config('redsys.tpv.terminal'),
            environment: config('redsys.environment') === 'production' ? Environment::Production : Environment::Test
        );

        // Precio del autobús en céntimos (usar tarifa tardía o normal según fecha)
        $esTarifaTardia = $inscripcion->edicion->esTarifaTardia();
        $precioAutobus = $esTarifaTardia 
            ? ($inscripcion->edicion->precio_autobus_tardia ?? 14)
            : ($inscripcion->edicion->precio_autobus_normal ?? 12);
        $amountInCents = (int)($precioAutobus * 100);

        $requestParams = new RequestParameters(
            amountInCents: $amountInCents,
            transactionType: TransactionType::Autorizacion,
            currency: Currency::EUR,
            order: $orderNumberFormatted,
            merchantUrl: route('redsys.notification'),
            urlOk: route('redsys.success'),
            urlKo: route('redsys.error'),
            merchantData: 'BUS_' . $inscripcion->id, // Prefijo para identificar que es pago de autobús
            productDescription: 'Autobús Nocturna Fredes Paüls ' . $inscripcion->edicion->anio,
            titular: $inscripcion->participante->nombre . ' ' . $inscripcion->participante->apellidos,
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
            'esAutobus' => true,
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

            Log::info('Redsys notification: Received', [
                'order' => $params->order,
                'merchantData' => $params->merchantData ?? null,
            ]);

            // Verificar si es un pago de autobús
            $esAutobus = isset($params->merchantData) && str_starts_with($params->merchantData, 'BUS_');
            
            if ($esAutobus) {
                // Extraer ID de inscripción del merchantData
                $inscripcionId = str_replace('BUS_', '', $params->merchantData);
                $inscripcion = Inscripcion::with('edicion')->find($inscripcionId);
                
                if (!$inscripcion) {
                    Log::error('Redsys notification: Inscripcion not found for bus payment', ['merchantData' => $params->merchantData]);
                    return response('Inscripcion not found', 404);
                }

                // Solo procesar si no tiene ya el autobús (evita duplicados)
                if (!$inscripcion->necesita_autobus) {
                    // Calcular precio autobús según tarifa normal o tardía
                    $esTarifaTardia = $inscripcion->edicion->esTarifaTardia();
                    $precioAutobus = $esTarifaTardia 
                        ? ($inscripcion->edicion->precio_autobus_tardia ?? 14)
                        : ($inscripcion->edicion->precio_autobus_normal ?? 12);

                    $inscripcion->update([
                        'necesita_autobus' => true,
                        'parada_autobus' => $inscripcion->parada_autobus_pendiente,
                        'parada_autobus_pendiente' => null,
                        'precio_total' => $inscripcion->precio_total + $precioAutobus,
                    ]);
                    Log::info('Redsys notification: Bus payment successful', ['inscripcion_id' => $inscripcion->id, 'precio_autobus' => $precioAutobus]);
                } else {
                    Log::info('Redsys notification: Bus already activated, skipping', ['inscripcion_id' => $inscripcion->id]);
                }

                return response('OK');
            }

            // Obtener la inscripción para pago normal
            $inscripcion = Inscripcion::where('numero_pedido', $params->order)->first();
            
            if (!$inscripcion && isset($params->merchantData)) {
                $inscripcion = Inscripcion::find($params->merchantData);
            }
            
            if (!$inscripcion) {
                Log::error('Redsys notification: Inscripcion not found', [
                    'order' => $params->order
                ]);
                return response('Inscripcion not found', 404);
            }

            // Solo procesar si no está ya pagado (evita duplicados)
            if ($inscripcion->estado_pago !== 'pagado') {
                $inscripcion->update([
                    'estado_pago' => 'pagado',
                    'numero_autorizacion' => $params->responseAuthorisationCode,
                    'fecha_pago' => now(),
                ]);
                Log::info('Redsys notification: Payment successful', [
                    'inscripcion_id' => $inscripcion->id,
                    'amount' => $params->amount,
                ]);
                
                // Enviar email de confirmación
                try {
                    Mail::to($inscripcion->participante->email)->send(new InscripcionConfirmada($inscripcion));
                    Log::info('Email de confirmación enviado', ['inscripcion_id' => $inscripcion->id]);
                } catch (\Exception $e) {
                    Log::error('Error enviando email de confirmación', [
                        'inscripcion_id' => $inscripcion->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                Log::info('Redsys notification: Already paid, skipping', ['inscripcion_id' => $inscripcion->id]);
            }

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

            // Verificar si es un pago de autobús
            $esAutobus = isset($params->merchantData) && str_starts_with($params->merchantData, 'BUS_');
            
            if ($esAutobus) {
                // Extraer ID de inscripción del merchantData
                $inscripcionId = str_replace('BUS_', '', $params->merchantData);
                $inscripcion = Inscripcion::with('edicion')->find($inscripcionId);
                
                if (!$inscripcion) {
                    Log::error('Redsys success: Inscripcion not found for bus payment', ['merchantData' => $params->merchantData]);
                    return redirect()->route('home')->with('error', 'Inscripción no encontrada');
                }

                // Solo procesar si no tiene ya el autobús (evita duplicados al recargar)
                if (!$inscripcion->necesita_autobus) {
                    // Calcular precio autobús según tarifa normal o tardía
                    $esTarifaTardia = $inscripcion->edicion->esTarifaTardia();
                    $precioAutobus = $esTarifaTardia 
                        ? ($inscripcion->edicion->precio_autobus_tardia ?? 14)
                        : ($inscripcion->edicion->precio_autobus_normal ?? 12);

                    $inscripcion->update([
                        'necesita_autobus' => true,
                        'parada_autobus' => $inscripcion->parada_autobus_pendiente,
                        'parada_autobus_pendiente' => null,
                        'numero_pedido_autobus' => null,
                        'precio_total' => $inscripcion->precio_total + $precioAutobus,
                    ]);
                    $inscripcion->refresh();
                    Log::info('Redsys success: Bus payment successful', ['inscripcion_id' => $inscripcion->id, 'precio_autobus' => $precioAutobus]);
                } else {
                    Log::info('Redsys success: Bus already activated, skipping', ['inscripcion_id' => $inscripcion->id]);
                }

                return Inertia::render('Pago/ExitoAutobus', [
                    'inscripcion' => $inscripcion->load(['participante', 'edicion']),
                ]);
            }

            // Pago normal de inscripción
            $inscripcion = Inscripcion::where('numero_pedido', $params->order)->first();
            
            if (!$inscripcion && isset($params->merchantData)) {
                $inscripcion = Inscripcion::find($params->merchantData);
                Log::info('Redsys success: Found by merchantData', ['id' => $params->merchantData]);
            }

            if (!$inscripcion) {
                Log::error('Redsys success: Inscripcion not found', ['order' => $params->order]);
                return redirect()->route('home')->with('error', 'Inscripción no encontrada');
            }

            // Solo procesar si no está ya pagado (evita duplicados al recargar)
            if ($inscripcion->estado_pago !== 'pagado') {
                $inscripcion->update([
                    'estado_pago' => 'pagado',
                    'numero_autorizacion' => $params->responseAuthorisationCode,
                    'fecha_pago' => now(),
                ]);
                
                // Enviar email de confirmación
                try {
                    Mail::to($inscripcion->participante->email)->send(new InscripcionConfirmada($inscripcion));
                    Log::info('Email de confirmación enviado', ['inscripcion_id' => $inscripcion->id]);
                } catch (\Exception $e) {
                    Log::error('Error enviando email de confirmación', [
                        'inscripcion_id' => $inscripcion->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

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

    /**
     * Procesar devolución de una inscripción pagada.
     * Las devoluciones en Redsys requieren WebService REST, no redirección.
     */
    public function procesarDevolucion(Request $request, Inscripcion $inscripcion)
    {
        Log::info('Iniciando devolución', ['inscripcion_id' => $inscripcion->id]);

        // Verificar que la inscripción esté pagada
        if ($inscripcion->estado_pago !== 'pagado') {
            return back()->withErrors(['error' => 'Solo se pueden devolver inscripciones pagadas']);
        }

        // Verificar que tenga número de pedido original
        if (!$inscripcion->numero_pedido) {
            return back()->withErrors(['error' => 'No se encontró el número de pedido original']);
        }

        // Calcular importe a devolver (puede ser parcial o total)
        $importeDevolucion = $request->input('importe', $inscripcion->precio_total);
        $amountInCents = (int)($importeDevolucion * 100);

        try {
            $redsysClient = new RedsysClient(
                merchantCode: (int)config('redsys.tpv.merchantCode'),
                secretKey: config('redsys.tpv.key'),
                terminal: (int)config('redsys.tpv.terminal'),
                environment: config('redsys.environment') === 'production' ? Environment::Production : Environment::Test
            );

            // Crear petición de devolución (TransactionType 3)
            $requestParams = new RequestParameters(
                amountInCents: $amountInCents,
                transactionType: TransactionType::Devolucion,
                currency: Currency::EUR,
                order: $inscripcion->numero_pedido,
            );

            $redsysRequest = RedsysRequest::create($redsysClient, $requestParams);

            // Enviar petición por WebService REST usando el método de la librería
            $response = $redsysRequest->sendPostRequest();
            
            Log::info('Respuesta devolución Redsys', ['response' => get_class($response)]);

            // Verificar si es una respuesta exitosa o un error
            if ($response instanceof \Creagia\Redsys\RedsysResponse) {
                $responseCode = $response->parameters->responseCode ?? null;

                // Códigos 0000-0099 = éxito
                if ($responseCode !== null && (int)$responseCode >= 0 && (int)$responseCode <= 99) {
                    // Devolución exitosa
                    $inscripcion->update([
                        'estado_pago' => 'devuelto',
                        'fecha_devolucion' => now(),
                        'importe_devolucion' => $importeDevolucion,
                    ]);

                    Log::info('Devolución exitosa', ['inscripcion_id' => $inscripcion->id, 'response_code' => $responseCode]);

                    return back()->with('success', 'Devolución procesada correctamente');
                } else {
                    $errorMsg = 'Devolución rechazada. Código: ' . ($responseCode ?? 'desconocido');
                    Log::error('Devolución rechazada', ['response_code' => $responseCode]);
                    return back()->withErrors(['error' => $errorMsg]);
                }
            } elseif ($response instanceof \Creagia\Redsys\Support\PostRequestError) {
                Log::error('Error en petición de devolución', ['code' => $response->code, 'message' => $response->message]);
                return back()->withErrors(['error' => 'Error de Redsys: ' . $response->message]);
            } else {
                Log::error('Respuesta inesperada de Redsys');
                return back()->withErrors(['error' => 'Error al procesar la devolución con Redsys']);
            }
        } catch (\Exception $e) {
            Log::error('Excepción en devolución', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors(['error' => 'Error interno: ' . $e->getMessage()]);
        }
    }

    /**
     * Marcar devolución manual (sin pasar por Redsys).
     * Útil cuando la devolución se procesa por transferencia, efectivo, etc.
     */
    public function devolucionManual(Request $request, Inscripcion $inscripcion)
    {
        Log::info('Devolución manual', ['inscripcion_id' => $inscripcion->id]);

        // Verificar que la inscripción esté pagada
        if ($inscripcion->estado_pago !== 'pagado') {
            return back()->withErrors(['error' => 'Solo se pueden devolver inscripciones pagadas']);
        }

        $importeDevolucion = $request->input('importe', $inscripcion->precio_total);

        $inscripcion->update([
            'estado_pago' => 'devuelto',
            'fecha_devolucion' => now(),
            'importe_devolucion' => $importeDevolucion,
        ]);

        Log::info('Devolución manual completada', ['inscripcion_id' => $inscripcion->id, 'importe' => $importeDevolucion]);

        return back()->with('success', 'Devolución manual registrada correctamente');
    }
}