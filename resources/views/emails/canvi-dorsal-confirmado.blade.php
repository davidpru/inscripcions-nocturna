<x-mail::message>
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('images/logo-email.png') }}" alt="Nocturna Fredes-Paüls" style="max-width: 200px; height: auto;">
</div>

# Canvi de dorsal confirmat! ✅

Hola **{{ $nouParticipant->nombre }} {{ $nouParticipant->apellidos }}**,

El teu canvi de dorsal per a la **Nocturna Fredes-Paüls {{ $inscripcion->edicion->anio }}** s'ha processat correctament.

# Detalls de la teva inscripció

- **Número d'inscripció:** #{{ $inscripcion->id }}
- **DNI:** {{ $nouParticipant->dni }}
- **Samarreta Caro:** Talla {{ strtoupper($inscripcion->talla_camiseta_caro) }}
- **Samarreta Paüls:** Talla {{ strtoupper($inscripcion->talla_camiseta_pauls) }}
@if($inscripcion->necesita_autobus)
- **Autobús:** Sí (Parada: {{ $inscripcion->parada_autobus ?? 'No especificada' }})
@endif

Enhorabona, estàs inscrit/a! Ens veiem a la sortida.

<x-mail::button :url="config('app.url') . '/inscripcio/d/' . $inscripcion->hash_token . '/pdf'">
Descarregar PDF amb codi QR
</x-mail::button>

Si tens qualsevol dubte, no dubtis en contactar amb nosaltres.

Salutacions,
**UEC Tortosa - Nocturna Fredes-Paüls**

<x-mail::subcopy>
Has rebut aquest correu perquè has realitzat un canvi de dorsal a la Nocturna Fredes-Paüls.
</x-mail::subcopy>
</x-mail::message>
