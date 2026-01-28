<x-mail::message>
# Inscripció confirmada! 🎉

Hola **{{ $inscripcion->participante->nombre }} {{ $inscripcion->participante->apellidos }}**,

La teva inscripció per a la **Nocturna Fredes-Paüls {{ $inscripcion->edicion->nombre }}** s'ha confirmat correctament.

## Detalls de la inscripció

- **Número d'inscripció:** #{{ $inscripcion->id }}
- **DNI:** {{ $inscripcion->participante->dni }}
- **Modalitat:** {{ ucfirst($inscripcion->modalidad) }}
- **Dorsal:** {{ $inscripcion->dorsal ?? 'Pendent d\'assignar' }}

@if($inscripcion->autobus)
- **Autobús:** Sí (Parada: {{ $inscripcion->parada_autobus ?? 'No especificada' }})
@endif

@if($inscripcion->es_celiaco)
- **Menú celíac:** Sí
@endif

## Pagament

- **Import total:** **{{ number_format($inscripcion->total, 2) }}€**
- **Estat del pagament:** {{ $inscripcion->estado_pago === 'completado' ? 'Confirmat ✅' : 'Pendent' }}

@if($inscripcion->estado_pago === 'completado')
Hem rebut el teu pagament correctament. Et veurem a la sortida!
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
