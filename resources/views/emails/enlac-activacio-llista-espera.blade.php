<x-mail::message>
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('images/logo-email.png') }}" alt="Nocturna Fredes-Paüls" style="max-width: 200px; height: auto;">
</div>

# Tens una plaça disponible! 🎉

Hola **{{ $inscripcion->participante->nombre }} {{ $inscripcion->participante->apellidos }}**,

Bones notícies! S'ha alliberat una plaça per a la **{{ $inscripcion->edicion->nombre ?? 'Nocturna Fredes-Paüls ' . $inscripcion->edicion->anio }}** i t'ha correspost a tu.

Tens **48 hores** per completar el pagament i confirmar la teva plaça. Un cop caduqui l'enllaç, la plaça passarà al següent de la llista.

## Resum de la teva inscripció

- **Import a pagar:** {{ number_format($inscripcion->precio_total, 2) }}€
- **Tarifa:** {{ $inscripcion->tarifa_aplicada }}
@if($inscripcion->necesita_autobus)
- **Autobús:** Sí (Parada: {{ $inscripcion->parada_autobus ?? 'No especificada' }})
@endif

<x-mail::button :url="$urlActivacio" color="success">
Confirmar i Pagar la Plaça
</x-mail::button>

⚠️ **Recorda:** Aquest enllaç caduca en 48 hores. Si no completes el pagament, la plaça passarà al següent de la llista.

Si tens qualsevol dubte, no dubtis en contactar amb nosaltres.

Salutacions,
**UEC Tortosa - Nocturna Fredes-Paüls**

<x-mail::subcopy>
Has rebut aquest correu perquè estaves a la llista d'espera de la Nocturna Fredes-Paüls.
</x-mail::subcopy>
</x-mail::message>
