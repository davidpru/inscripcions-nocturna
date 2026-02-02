# GitHub Actions - Build Automático

Este proyecto utiliza **GitHub Actions** para compilar automáticamente los assets de frontend (JavaScript y CSS) cuando se hace push al repositorio.

## ¿Qué hace el workflow?

1. Detecta cambios en el código
2. Instala dependencias (`pnpm install`)
3. Compila los assets (`pnpm run build`)
4. Sube los archivos compilados al servidor de producción

## Importante

- **NO ejecutar** `npm run build` o `pnpm run build` en el servidor
- **NO subir** la carpeta `public/build/` al repositorio (está en `.gitignore`)
- Los assets se compilan automáticamente en cada push a `main`

## Ver el estado del build

Visita: https://github.com/tu-usuario/tu-repositorio/actions

## Archivos relacionados

- `.github/workflows/*.yml` - Configuración de GitHub Actions
- `public/build/` - Assets compilados (generados por Actions, no por ti)
