<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CambioDorsal;
use App\Models\Edicion;
use Inertia\Inertia;

class CambioDorsalController extends Controller
{
    public function index()
    {
        $edicion = Edicion::where('activa', true)->first();

        $canvis = CambioDorsal::with([
                'inscripcion.participante',
                'nuevoParticipante',
            ])
            ->when($edicion, fn ($q) => $q->whereHas('inscripcion', fn ($q2) => $q2->where('edicion_id', $edicion->id)))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (CambioDorsal $c) {
                $inscripcion   = $c->inscripcion;
                $participantOrig = $inscripcion?->participante;

                $segsRestants = max(0, now()->diffInSeconds($c->expires_at, false));

                return [
                    'id'                       => $c->id,
                    'token'                    => $c->token,
                    'estado'                   => $c->estado,
                    'precio'                   => (float) $c->precio,
                    'expires_at'               => $c->expires_at->toIso8601String(),
                    'segons_restants'          => $segsRestants,
                    'created_at'               => $c->created_at->toIso8601String(),
                    'url'                      => route('canvi-dorsal.show', $c->token),
                    'inscripcion_id'           => $inscripcion?->id,
                    'participant_original'     => $participantOrig ? [
                        'nom'      => $participantOrig->nombre . ' ' . $participantOrig->apellidos,
                        'dni'      => $participantOrig->dni,
                        'email'    => $participantOrig->email,
                    ] : null,
                    'nou_participant'          => $c->nuevoParticipante ? [
                        'nom'      => $c->nuevoParticipante->nombre . ' ' . $c->nuevoParticipante->apellidos,
                        'dni'      => $c->nuevoParticipante->dni,
                    ] : null,
                    'nom_participant_original' => $c->nombre_participante_original,
                ];
            });

        return Inertia::render('Admin/CambioDorsal/Index', [
            'canvis' => $canvis,
        ]);
    }

    public function destroy(CambioDorsal $cambioDorsal)
    {
        if ($cambioDorsal->estado === 'completado') {
            return back()->withErrors(['error' => 'No es pot eliminar un canvi de dorsal completat.']);
        }

        $cambioDorsal->delete();

        return back()->with('success', 'Canvi de dorsal eliminat correctament.');
    }
}
