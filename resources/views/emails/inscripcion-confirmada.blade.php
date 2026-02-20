<x-mail::message>
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('images/logo-email.png') }}" alt="Nocturna Fredes-Paüls" style="max-width: 200px; height: auto;">
</div>

# Inscripció confirmada! 🎉

Hola **{{ $inscripcion->participante->nombre }} {{ $inscripcion->participante->apellidos }}**,

La teva inscripció per a la **Nocturna Fredes-Paüls {{ $inscripcion->edicion->anio }}** s'ha confirmat correctament.

# Detalls de la inscripció

- **Número d'inscripció:** #{{ $inscripcion->id }}
- **DNI:** {{ $inscripcion->participante->dni }}
- **Tarifa aplicada:** {{ $inscripcion->tarifa_aplicada ?? 'Estàndard' }}
@if($inscripcion->es_socio_uec)
- **Soci UEC:** Sí
@endif
@if($inscripcion->esta_federado)
- **Federat:** Sí @if($inscripcion->numero_licencia)(Llicència: {{ $inscripcion->numero_licencia }})@endif
@endif

- **Samarreta Caro:** Talla {{ strtoupper($inscripcion->talla_camiseta_caro) }}
- **Samarreta Paüls:** Talla {{ strtoupper($inscripcion->talla_camiseta_pauls) }}
@if($inscripcion->necesita_autobus)
- **Autobús:** Sí (Parada: {{ $inscripcion->parada_autobus ?? 'No especificada' }})
@endif
@if($inscripcion->seguro_anulacion)
- **Assegurança d'anul·lació:** Sí
@endif
@if($inscripcion->es_celiaco)
- **Menú celíac:** Sí
@endif

# Pagament

- **Import total:** **{{ number_format($inscripcion->precio_total, 2) }}€**
- **Estat del pagament:** {{ $inscripcion->estado_pago === 'pagado' ? 'Confirmat ✅' : ($inscripcion->estado_pago === 'invitado' ? 'Convidat ✅' : 'Pendent') }}

@if($inscripcion->estado_pago === 'pagado' || $inscripcion->estado_pago === 'invitado')
Enhorabona estàs inscrit! Ens veiem a la sortida.

<x-mail::button :url="config('app.url') . '/inscripcio/d/' . $inscripcion->hash_token . '/pdf'">
Descarregar PDF amb codi QR
</x-mail::button>
@else
Recorda completar el pagament per confirmar definitivament la teva plaça.
@endif

<x-mail::button :url="config('app.url')">
Anar al web
</x-mail::button>

Si tens qualsevol dubte, no dubtis en contactar amb nosaltres.

Salutacions,<br>
**UEC Tortosa - Nocturna Fredes-Paüls**

<x-mail::subcopy>
Aquest és un correu automàtic de confirmació. Si no has realitzat aquesta inscripció, si us plau contacta amb nosaltres.
</x-mail::subcopy>
</x-mail::message>
