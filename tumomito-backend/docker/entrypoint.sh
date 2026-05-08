#!/usr/bin/env sh
set -eu

# Sin .env en la imagen, Render debe definir APP_KEY. Si falta, Laravel devuelve 500 en
# rutas web (/, /ping) mientras que /up sigue en 200 — generamos una clave estable por proceso.
if [ -z "${APP_KEY:-}" ]; then
  export APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
  echo "tumomito: APP_KEY ausente; generada en arranque. Para no invalidar cookies al reiniciar, definí APP_KEY en Render (php artisan key:generate --show)." >&2
fi

# Asegurar que exista línea válida APP_KEY en .env: un .env con APP_KEY= vacío (p. ej. de build Composer) sobrescribe
# la variable de proceso al cargarse con Dotenv y deja Laravel sin clave pese al export anterior.
ENV_FILE=/var/www/html/.env
if [ ! -f "$ENV_FILE" ] || ! grep -qsE '^APP_KEY=.+' "$ENV_FILE" 2>/dev/null; then
  tmp="${ENV_FILE}.tmp.$$"
  if [ -f "$ENV_FILE" ]; then
    grep -v '^APP_KEY=' "$ENV_FILE" >"$tmp" 2>/dev/null || : >"$tmp"
    mv "$tmp" "$ENV_FILE"
  fi
  printf 'APP_KEY=%s\n' "$APP_KEY" >>"$ENV_FILE"
  chmod 0640 "$ENV_FILE" 2>/dev/null || chmod 0644 "$ENV_FILE"
fi

# Render/Supabase: asegurar permisos (por si el volumen/FS cambia)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force
fi

if [ "${APP_ENV:-}" = "production" ]; then
  php artisan config:clear >/dev/null 2>&1 || true
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
fi

exec "$@"
