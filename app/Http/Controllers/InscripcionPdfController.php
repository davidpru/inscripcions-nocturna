<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InscripcionPdfController extends Controller
{
    /**
     * Descargar PDF por hash_token (ruta pública segura).
     */
    public function descargarPorHash(string $hash)
    {
        $inscripcion = Inscripcion::where('hash_token', $hash)->firstOrFail();

        return $this->generarPdf($inscripcion);
    }

    /**
     * Descargar PDF por ID (mantenida para admin y compatibilidad interna).
     */
    public function descargar(Inscripcion $inscripcion)
    {
        return $this->generarPdf($inscripcion);
    }

    /**
     * Genera y descarga el PDF de la inscripción.
     */
    private function generarPdf(Inscripcion $inscripcion)
    {
        // Verificar que la inscripción está pagada
        if (!in_array($inscripcion->estado_pago, ['pagado', 'invitado'])) {
            abort(403, 'La inscripció no està pagada');
        }

        // Cargar relaciones
        $inscripcion->load(['participante', 'edicion']);

        // Generar URL de verificación (usa hash para seguridad)
        $verificationUrl = route('inscripcion.verificar.hash', $inscripcion->hash_token);
        
        // Generar QR como SVG base64
        $qrCode = base64_encode(QrCode::format('svg')
            ->size(150)
            ->margin(1)
            ->generate($verificationUrl));

        $pdf = Pdf::loadView('pdf.inscripcion', [
            'inscripcion' => $inscripcion,
            'participante' => $inscripcion->participante,
            'edicion' => $inscripcion->edicion,
            'qrCode' => $qrCode,
            'verificationUrl' => $verificationUrl,
        ]);

        $filename = sprintf(
            'inscripcion-%s-%s.pdf',
            $inscripcion->edicion->anio,
            str_replace(' ', '-', strtolower($inscripcion->participante->nombre))
        );

        return $pdf->download($filename);
    }

    /**
     * Verificar inscripción por hash_token (ruta pública segura).
     */
    public function verificarPorHash(string $hash)
    {
        $inscripcion = Inscripcion::where('hash_token', $hash)->firstOrFail();
        $inscripcion->load(['participante', 'edicion']);

        return inertia('Inscripcion/Verificar', [
            'inscripcion' => $inscripcion,
            'participante' => $inscripcion->participante,
            'edicion' => $inscripcion->edicion,
        ]);
    }

    /**
     * Verificar inscripción por ID (mantenida para compatibilidad).
     */
    public function verificar(Inscripcion $inscripcion)
    {
        $inscripcion->load(['participante', 'edicion']);

        return inertia('Inscripcion/Verificar', [
            'inscripcion' => $inscripcion,
            'participante' => $inscripcion->participante,
            'edicion' => $inscripcion->edicion,
        ]);
    }
}
