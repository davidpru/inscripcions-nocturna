<x-mail::message>
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('images/logo-email.png') }}" alt="Nocturna Fredes-Paüls" style="max-width: 200px; height: auto;">
</div>

# El teu dorsal ha estat transferit

Hola **{{ $nomOriginal }}**,

T'informem que el teu dorsal de la **Nocturna Fredes-Paüls {{ $inscripcion->edicion->anio }}** (inscripció #{{ $inscripcion->id }}) ha estat transferit correctament a:

**{{ $nomNouParticipant }}**

Si creus que això és un error o no has autoritzat aquest canvi, contacta amb l'organització immediatament.

<x-mail::button :url="config('app.url')">
Anar al web
</x-mail::button>

Salutacions,
**UEC Tortosa - Nocturna Fredes-Paüls**

<x-mail::subcopy>
Has rebut aquest correu perquè el teu dorsal ha estat transferit a un altre participant.
</x-mail::subcopy>
</x-mail::message>
