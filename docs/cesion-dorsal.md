# Cesion de dorsal (futuro)

## Objetivo

Permitir a un administrador ceder un dorsal a otro participante antes de la carrera, recalculando el precio con tarifas actuales y cobrando solo la diferencia. No se realizan reembolsos.

## Flujo propuesto (admin)

1. En el panel de la inscripcion, boton "Ceder dorsal".
2. Formulario con datos del nuevo participante (dni, nombre, apellidos, email, telefono, etc.).
3. Recalculo de precio con tarifas actuales.
4. Si la diferencia > 0:
   - Se genera pago Redsys por la diferencia.
   - La inscripcion queda en estado `pendiente` hasta el pago.
5. Si la diferencia = 0:
   - Se actualiza titular sin pago y se mantiene `pagado`.
6. Se registra un log de cesion (tabla nueva o audit log).

## Backend (futuro)

### Nuevo endpoint

`POST /uec-admin/inscripciones/{inscripcion}/ceder`

### Validaciones

- Solo si `estado_pago` es `pagado` o `invitado`.
- Bloquear si `dorsal_recogido` es `true`.
- Bloquear si hay devolucion en curso.

### Pasos en el metodo

1. Buscar o crear participante por DNI.
2. Recalcular precio con `TarifaService` usando datos del nuevo participante.
3. Calcular `diferencia = nuevo_precio - precio_total`.
4. Si `diferencia > 0`:
   - Actualizar inscripcion con nuevo participante y datos.
   - Cambiar `estado_pago` a `pendiente`.
   - Generar nuevo `numero_pedido` y redirigir a Redsys por el importe de la diferencia.
5. Si `diferencia <= 0`:
   - Actualizar inscripcion con nuevo participante y mantener `pagado`.
6. Registrar cesion en una tabla `cesiones` (opcional): admin, fecha, diferencia, ids.

## Frontend (futuro)

- En `InscripcionSheetEdit`:
  - Boton "Ceder dorsal".
  - Modal con datos del nuevo participante.
  - Mostrar diferencia calculada antes de confirmar.
  - Si hay pago, mostrar enlace o iniciar Redsys.

## Notas importantes

- Nunca crear una inscripcion nueva: se reutiliza la misma.
- Si el dorsal ya fue recogido, no permitir cesion.
- Asegurar que la cesion quede auditada.
