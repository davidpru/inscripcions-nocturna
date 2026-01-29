<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InscripcionPdfController extends Controller
{
    public function descargar(Inscripcion $inscripcion)
    {
        // Verificar que la inscripción está pagada
        if ($inscripcion->estado_pago !== 'pagado') {
            abort(403, 'La inscripció no està pagada');
        }

        // Cargar relaciones
        $inscripcion->load(['participante', 'edicion']);

        // Generar URL de verificación
        $verificationUrl = route('inscripcion.verificar', $inscripcion->id);
        
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
