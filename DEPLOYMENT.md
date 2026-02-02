# Guía de Deployment - Inscripcions Nocturna

## 🚀 Proceso de Deployment a Producción

### 1. Compilación de Assets

**IMPORTANTE:** Los assets (JavaScript, CSS) se compilan automáticamente mediante GitHub Actions.

- ✅ **NO ejecutar** `npm run build` en el servidor de producción
- ✅ **NO subir** la carpeta `public/build` al repositorio
- ✅ GitHub Actions se encarga de compilar y desplegar automáticamente

### 2. Desplegar a Producción

```bash
# En el servidor de producción
ssh appuectortosa@app.uectortosa.cat
cd /home/appuectortosa/www

# Obtener últimos cambios
git pull origin main

# Limpiar cachés de Laravel (IMPORTANTE: usar PHP 8.2)
/usr/local/bin/php82 artisan optimize:clear

# Ejecutar migraciones si hay
/usr/local/bin/php82 artisan migrate --force
```

#### Comando rápido desde local

```bash
# Limpiar cachés desde tu máquina local (sin SSH interactivo)
ssh appuectortosa@app.uectortosa.cat "cd /home/appuectortosa/www && /usr/local/bin/php82 artisan optimize:clear"
```

**Nota importante:** El servidor tiene PHP 7.0.33 por defecto, pero Laravel 12 requiere PHP 8.2+.
Siempre usa `/usr/local/bin/php82` para ejecutar comandos de Artisan.

### 3. Verificación

- GitHub Actions compilará y subirá los assets automáticamente
- Esperar a que el workflow termine (verificar en GitHub)
- Los cambios estarán disponibles en https://app.uectortosa.cat

## 🔐 Panel de Administración

- **URL:** https://app.uectortosa.cat/uec-admin/login
- El path `/admin` ya no existe (cambió a `/uec-admin` por seguridad)

## 📝 Notas Importantes

- **Estados de pago implementados:** pagado, pendiente, cancelado, invitado, devuelto, devolucion_parcial
- **Configuración de pagos:** Redsys en modo test (verificar `.env` en producción)
- **Base de datos:** MySQL en Docker localmente, servidor remoto en producción
