<x-mail::message>
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('images/logo-email.png') }}" alt="Nocturna Fredes-Paüls" style="max-width: 200px; height: auto;">
</div>

# Plaça confirmada! ✅

Hola **{{ $inscripcion->participante->nombre }} {{ $inscripcion->participante->apellidos }}**,

El teu pagament s'ha processat correctament. Estàs inscrit/a a la **{{ $inscripcion->edicion->nombre ?? 'Nocturna Fredes-Paüls ' . $inscripcion->edicion->anio }}**!

## Detalls de la teva inscripció

- **Número d'inscripció:** #{{ $inscripcion->id }}
- **DNI:** {{ $inscripcion->participante->dni }}
- **Import pagat:** {{ number_format($inscripcion->precio_total, 2) }}€
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
Has rebut aquest correu perquè has activat la teva plaça de la llista d'espera de la Nocturna Fredes-Paüls.
</x-mail::subcopy>
</x-mail::message>
