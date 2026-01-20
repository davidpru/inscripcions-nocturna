# Especificaciones del Proyecto - Nocturna Fredes Paüls

## Sobre la Carrera

**Nombre**: Nocturna Fredes Paüls  
**Tipo**: Marcha excursionista nocturna  
**Sistema**: Cada año tiene una nueva edición

## Funcionalidades Principales

### 1. Gestión de Ediciones

- Crear nuevas ediciones de la carrera cada año
- Cada edición tiene sus propios inscritos y configuración de tarifas
- Control del límite de inscripciones (1000 inscritos)
- Control de fecha límite (1 de mayo) para cambio de tarifas

### 2. Formulario de Inscripción

#### Datos Personales

- **DNI/NIE** (campo clave para autocompletar)
- **Nombre**
- **Apellidos**
- **Género**: Masculino / Femenino
- **Fecha de nacimiento**
- **Teléfono**
- **Email**

#### Dirección

- **Dirección**
- **Código Postal**
- **Población**
- **Provincia**

#### Información Deportiva

- **¿Eres socio de la UEC Tortosa?**: Sí / No
- **¿Estás federado?**: Sí / No
- **Número de licencia** (solo si está federado)
- **Club** (Si eres socio de UEC Tortosa se autocompleta)

#### Servicios Adicionales

- **¿Necesita autobús?** (Paüls-Fredes)
  - Coste: 12€ (tarifa normal) / 14€ (tarifa tardía)
- **Seguro de anulación**
  - Coste: 9€

#### Equipación

- **Talla de la camiseta de Caro**
- **Talla de la camiseta de Paüls**

### 3. Sistema de Autocompletado

**Base de Datos Global de Inscritos**:

1. El participante introduce su DNI/NIE
2. Si el DNI existe en la base de datos:
   - Se autocompletan todos sus datos personales
   - Solo debe actualizar campos si han cambiado
3. Si el DNI no existe:
   - Es un participante nuevo
   - Debe completar todos los campos del formulario

### 4. Sistema de Tarifas

#### Tarifas Base (Inscripción)

| Categoría         | Federado | Tarifa Normal | Tarifa Tardía\* |
| ----------------- | -------- | ------------- | --------------- |
| Público           | Sí       | 35 €          | 40 €            |
| Público           | No       | 40 €          | 45 €            |
| Socio UEC Tortosa | Sí       | 30 €          | 35 €            |
| Socio UEC Tortosa | No       | 35 €          | 40 €            |

\*La tarifa tardía se aplica después del **1 de mayo** o cuando se alcancen **650 inscritos** (lo que ocurra primero)

#### Servicios Adicionales

| Servicio             | Tarifa Normal | Tarifa Tardía\* |
| -------------------- | ------------- | --------------- |
| Autobús Paüls-Fredes | 12 €          | 14 €            |
| Seguro de anulación  | 9 €           | 9 €             |

### 5. Panel de Administración

#### Funcionalidades Requeridas:

- **Gestión de Ediciones**
  - Crear nueva edición de la carrera
  - Configurar fechas límite
  - Establecer límites de inscripciones
  - Configurar tarifas por edición
- **Gestión de Inscritos**
  - Ver listado de inscritos
  - Filtrar por edición
  - Exportar datos
  - Editar inscripciones
  - Gestionar pagos
  - Ver estadísticas (total inscritos, federados/no federados, socios, etc.)

- **Reportes**
  - Número total de inscritos
  - Distribución por género
  - Distribución por categoría (socio/no socio, federado/no federado)
  - Servicios contratados (autobús, seguro)
  - Tallas de camisetas

## Reglas de Negocio

1. **Cambio de Tarifa**: La tarifa aumenta automáticamente cuando:
   - Se alcanza el 1 de mayo, O
   - Se alcanzan 650 inscritos
   - (Lo que ocurra primero)

2. **Validación de Federado**: Si marca "Estás federado = Sí", debe introducir:
   - Número de licencia (obligatorio)
   - Club (opcional pero recomendado)

3. **Autocompletado**: El DNI/NIE es único y sirve como identificador del participante a lo largo de todas las ediciones

4. **Cálculo del Precio Total**:

   ```
   Precio Total = Tarifa Base + Autobús (si aplica) + Seguro (si aplica)
   ```

   - La tarifa base depende de: socio UEC, federado, y fecha/límite inscripciones
   - Autobús y seguro son opcionales

## Modelos de Datos Sugeridos

### Participante (global)

- DNI/NIE
- Nombre
- Apellidos
- Género
- Fecha de nacimiento
- Teléfono
- Email
- Dirección
- Código Postal
- Población
- Provincia

### Edición

- Año
- Fecha del evento
- Límite de inscritos
- Fecha límite tarifa normal
- Estado (abierta/cerrada)

### Inscripción

- ID Participante
- ID Edición
- Es socio UEC Tortosa
- Está federado
- Número de licencia
- Club
- Necesita autobús
- Seguro de anulación
- Talla camiseta Caro
- Talla camiseta Paüls
- Tarifa aplicada
- Precio total
- Estado pago
- Fecha inscripción

## Flujo de Inscripción

1. Usuario accede al formulario de inscripción
2. Introduce DNI/NIE
3. Sistema busca en la base de datos:
   - **Si existe**: Carga datos automáticamente
   - **Si no existe**: Formulario vacío para completar
4. Usuario completa/actualiza:
   - Información deportiva (socio, federado, club)
   - Servicios adicionales (autobús, seguro)
   - Tallas de camisetas
5. Sistema calcula precio según:
   - Perfil del participante (socio/federado)
   - Fecha actual vs fecha límite
   - Número de inscritos actual
6. Usuario confirma y procede al pago
7. Sistema guarda inscripción y actualiza contador
