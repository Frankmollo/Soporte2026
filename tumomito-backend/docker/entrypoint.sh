#!/usr/bin/env sh
set -eu

# APP_KEY debe ser solo el valor tipo base64:... (salida de `php artisan key:generate --show`).
# Si en Render pegás el comando entero, Dotenv revienta con "unexpected whitespace".
KEY_OK_PATTERN='^base64:[A-Za-z0-9+/=]+$'
if [ -z "${APP_KEY:-}" ] || ! printf '%s' "$APP_KEY" | grep -qsE "$KEY_OK_PATTERN"; then
  export APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
  echo "tumomito: APP_KEY ausente o inválido (en Render pegá solo el valor base64:… de key:generate --show, no el comando). Clave generada en arranque." >&2
fi

# Composer puede haber generado .env con APP_KEY= vacío o basura; Dotenv pisaba la export y dejaba 500.
ENV_FILE=/var/www/html/.env
if [ ! -f "$ENV_FILE" ] || ! grep -qsE '^APP_KEY=base64:[A-Za-z0-9+/=]+$' "$ENV_FILE" 2>/dev/null; then
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

# Sesión FILE y cachés: sin estos dirs Apache suele responder 500 con cuerpo vacío.
mkdir -p /var/www/html/storage/framework/sessions \
  /var/www/html/storage/framework/views \
  /var/www/html/storage/framework/cache/data \
  /var/www/html/storage/logs \
  /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  if ! php artisan migrate --force; then
    echo "tumomito: migrate --force falló; Apache arranca igual. Revisá DB_* en Render o desactivá con RUN_MIGRATIONS=false." >&2
  fi
fi

# En Docker/PaaS no ejecutar config:cache en arranque: si algo falla queda config viejo y env()
# puede no coincidir con las vars del proceso; Laravel lee .env vars por petición sin este paso.
exec "$@"
