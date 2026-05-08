#!/usr/bin/env sh
set -eu

# Sin .env en la imagen, Render debe definir APP_KEY. Si falta, Laravel devuelve 500 en
# rutas web (/, /ping) mientras que /up sigue en 200 — generamos una clave estable por proceso.
if [ -z "${APP_KEY:-}" ]; then
  export APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
  echo "tumomito: APP_KEY ausente; generada en arranque. Para no invalidar cookies al reiniciar, definí APP_KEY en Render (php artisan key:generate --show)." >&2
fi

# Render/Supabase: asegurar permisos (por si el volumen/FS cambia)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force
fi

if [ "${APP_ENV:-}" = "production" ]; then
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
fi

exec "$@"
