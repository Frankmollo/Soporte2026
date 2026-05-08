#!/usr/bin/env sh
set -eu

# Render sin .env: si falta APP_KEY, Laravel 500 en rutas web (/). Generamos una en arranque.
if [ -z "${APP_KEY:-}" ]; then
  export APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
  echo "tumomito: APP_KEY ausente; generada en arranque (definí APP_KEY en Render para clave fija)." >&2
fi

# Composer puede haber generado .env con APP_KEY= vacío; Dotenv pisaba la export y dejaba 500.
ENV_FILE=/var/www/html/.env
if [ ! -f "$ENV_FILE" ] || ! grep -qsE '^APP_KEY=.+' "$ENV_FILE" 2>/dev/null; then
  tmp="${ENV_FILE}.tmp.$$"
  if [ -f "$ENV_FILE" ]; then
    grep -v '^APP_KEY=' "$ENV_FILE" >"$tmp" 2>/dev/null || : >"$tmp"
    mv "$tmp" "$ENV_FILE"
  fi
  printf 'APP_KEY=%s\n' "$APP_KEY" >>"$ENV_FILE"
fi

if [ -f "$ENV_FILE" ]; then
  chown www-data:www-data "$ENV_FILE" 2>/dev/null || true
  chmod 0644 "$ENV_FILE" 2>/dev/null || true
fi

# Render/Supabase: asegurar permisos (por si el volumen/FS cambia)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  if ! php artisan migrate --force; then
    echo "tumomito: migrate --force falló; Apache arranca igual. Revisá DB_* en Render o desactivá con RUN_MIGRATIONS=false." >&2
  fi
fi

if [ "${APP_ENV:-}" = "production" ]; then
  php artisan config:clear >/dev/null 2>&1 || true
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
fi

exec "$@"
