INSCRIPCIÓ CONFIRMADA!

Hola {{ $inscripcion->participante->nombre }} {{ $inscripcion->participante->apellidos }},

La teva inscripció per a la Nocturna Fredes-Paüls {{ $inscripcion->edicion->anio }} s'ha confirmat correctament.

=== DETALLS DE LA INSCRIPCIÓ ===

- Número d'inscripció: #{{ $inscripcion->id }}
- DNI: {{ $inscripcion->participante->dni }}
- Tarifa aplicada: {{ $inscripcion->tarifa_aplicada ?? 'Estàndard' }}
@if($inscripcion->es_socio_uec)
- Soci UEC: Sí
@endif
@if($inscripcion->esta_federado)
- Federat: Sí @if($inscripcion->numero_licencia)(Llicència: {{ $inscripcion->numero_licencia }})@endif
@endif

=== SERVEIS CONTRACTATS ===

- Samarreta Caro: Talla {{ strtoupper($inscripcion->talla_camiseta_caro) }}
- Samarreta Paüls: Talla {{ strtoupper($inscripcion->talla_camiseta_pauls) }}
@if($inscripcion->necesita_autobus)
- Autobús: Sí (Parada: {{ $inscripcion->parada_autobus ?? 'No especificada' }})
@endif
@if($inscripcion->seguro_anulacion)
- Assegurança d'anul·lació: Sí
@endif
@if($inscripcion->es_celiaco)
- Menú celíac: Sí
@endif

=== PAGAMENT ===

- Import total: {{ number_format($inscripcion->precio_total, 2) }}€
- Estat del pagament: {{ $inscripcion->estado_pago === 'pagado' ? 'Confirmat' : ($inscripcion->estado_pago === 'invitado' ? 'Convidat' : 'Pendent') }}

@if($inscripcion->estado_pago === 'pagado' || $inscripcion->estado_pago === 'invitado')
Hem rebut el teu pagament correctament. Et veurem a la sortida!
@else
Recorda completar el pagament per confirmar definitivament la teva plaça.
@endif

Visita el nostre web: {{ config('app.url') }}

Si tens qualsevol dubte, no dubtis en contactar amb nosaltres.

Salutacions,
UEC Tortosa - Nocturna Fredes-Paüls

--
Aquest és un correu automàtic de confirmació. Si no has realitzat aquesta inscripció, si us plau contacta amb nosaltres.
