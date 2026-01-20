# Instrucciones del Proyecto - Inscripcions Nocturna

## Stack Tecnológico

- **Backend**: Laravel 12
- **Frontend**: Vue 3 + TypeScript + Inertia.js
- **Estilos**: Tailwind CSS v4 + shadcn-vue
- **Base de datos**: MySQL 8.0 (Docker)
- **Build**: Vite + vue-tsc

## Estructura del Proyecto

- `resources/js/` - Código TypeScript/Vue
- `resources/js/Pages/` - Páginas Inertia.js (Vue SFC)
- `resources/js/components/ui/` - Componentes shadcn-vue
- `resources/css/app.css` - Estilos globales con Tailwind
- `routes/web.php` - Rutas de la aplicación

## Convenciones de Código

### TypeScript

- Usar `lang="ts"` en todos los componentes Vue
- Definir props con `defineProps<{ ... }>()`
- Tipos estrictos habilitados
- Usar path alias `@/` para imports

### Vue 3

- Composition API con `<script setup>`
- Props tipadas con TypeScript
- Usar Inertia.js para navegación (`<Link>` component)

### Estilos

- Tailwind CSS v4 con sintaxis `@theme`
- Variables CSS para colores de shadcn
- Usar `cn()` utility para merge de clases
- Componentes shadcn-vue para UI consistente

### Laravel

- Usar Inertia::render() para páginas Vue
- Middleware HandleInertiaRequests configurado
- Rutas en `routes/web.php`

## Comandos Útiles

```bash
npm run dev          # Desarrollo con Vite
npm run build        # Build producción con type-check
npm run lint         # ESLint + auto-fix
php artisan serve    # Servidor Laravel
docker compose up -d # MySQL + PHPMyAdmin
```

## Base de Datos

- MySQL en Docker (puerto 3306)
- PHPMyAdmin en http://localhost:8080
- Conexión configurada en `.env`

## Directrices

1. Siempre usar TypeScript, no JavaScript
2. Componentes Vue deben tener tipos explícitos
3. Preferir componentes shadcn-vue antes de crear custom
4. Seguir convenciones de Tailwind v4 (no usar `@apply` innecesariamente)
5. Usar Inertia.js para SPA, no vue-router
