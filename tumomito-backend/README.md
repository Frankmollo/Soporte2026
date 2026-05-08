## TUMOMITO — ERP + E‑Commerce (Laravel + Postgres)

Proyecto monolítico en **Laravel** (front Blade + back) con **Postgres** (ideal para **Supabase**).

### Credenciales de prueba

- **Admin (ERP/BI/Usuarios)**: `frank@gmail.com` / `12345`
- **Cliente (Catálogo/Carrito/Checkout)**: puedes crear uno en `/registro`

Si en tu base de datos NO existen esos usuarios, créalos con Artisan (ver abajo).

### Requisitos

- PHP 8.2+
- Composer
- Node 20+ (para Vite)
- Postgres (local) o Supabase (producción)

### Instalación rápida

1) Instalar dependencias

```bash
composer install
```

2) Configurar `.env` (Supabase / Postgres)

- Copia `.env.example` a `.env`
- Ajusta credenciales Postgres (Supabase):
  - `DB_CONNECTION=pgsql`
  - `DB_HOST=...`
  - `DB_PORT=5432`
  - `DB_DATABASE=...`
  - `DB_USERNAME=...`
  - `DB_PASSWORD=...`
  - `DB_SSLMODE=require`

3) Generar key, migrar y levantar

```bash
php artisan key:generate
php artisan migrate
php artisan serve
```

### Deploy en Render + Supabase

**Si ves HTTP 500 tras un deploy:** revisá **Logs** en Render; poné **`APP_DEBUG=true`** solo unos minutos si hace falta. Para saber si Apache sirve archivos estáticos sin Laravel: **`GET /static-health.txt`** debe responder `ok`.

1) Crear base de datos en Supabase

- Crea un proyecto en [Supabase](https://supabase.com/) y copia los datos de conexión (host, db, user, password).
- Asegúrate de usar SSL (`DB_SSLMODE=require`).
- Para el **pooler transacción** de Supabase (host `*.pooler.supabase.com`, puerto **6543**): usuario tipo `postgres.<project_ref>` y base **`postgres`**. Mantén **`DB_EMULATE_PREPARES=true`** (evita errores raros con PgBouncer). En Render conviene **`SESSION_DRIVER=file`** y **`CACHE_STORE=file`** con ese pooler.

2) Crear Web Service en Render

- En [Render Dashboard](https://dashboard.render.com/), crea un **Web Service** desde tu repo.
- Render detectará `render.yaml` y construirá con el `Dockerfile`.

3) Variables de entorno en Render (mínimas)

- **Obligatorias**:
  - `APP_KEY` (**recomendado** generar una y pegarla con `php artisan key:generate --show`). Sin esto el contenedor genera una al arrancar, pero cada reinicio invalida sesiones firmadas hasta que la fijes en el dashboard.
  - `APP_URL` (tu URL de Render)
  - `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- **Recomendadas**:
  - `DB_SSLMODE=require`
  - `DB_EMULATE_PREPARES=true` si usás pooler (6543)
  - `SESSION_DRIVER=file` y `CACHE_STORE=file` (recomendado con pooler; el proyecto ya usa **file/sync por defecto** si no ponés esas vars)
  - `LOG_CHANNEL=stderr` (para ver errores PHP/Laravel en la pestaña **Logs** de Render)
  - `RUN_MIGRATIONS=true` (por defecto): si Postgres falla, el contenedor igual levanta y verás el aviso **tumomito: migrate** en **Logs**.

Con eso, en cada deploy el contenedor ejecuta `php artisan migrate --force` automáticamente.

En una base **PostgreSQL vacía**, la migración `2026_04_29_036000_create_tumomito_core_tables` crea las tablas del núcleo (`categorias`, `usuarios`, `productos`, `pedidos`, `detalle_pedido`). Después ejecutá los comandos de usuarios/demo de abajo o cargá tus datos.

### Crear usuarios de prueba (siempre funciona en una BD nueva)

```bash
php artisan tumomito:crear-usuario frank@gmail.com 12345 --nombre="Frank Admin" --rol=admin
php artisan tumomito:crear-usuario cliente@gmail.com 12345 --nombre="Cliente" --rol=cliente
```

### Accesos

- **Catálogo**: `/`
- **Login**: `/login`
- **Registro**: `/registro`
- **Carrito**: `/carrito` (requiere login)
- **ERP**: `/erp/dashboard` (solo admin)
- **Usuarios (ERP)**: `/erp/usuarios` (solo admin)

### Nota de seguridad (prototipo)

Las contraseñas se guardan **en texto plano** en la tabla `usuarios` (modo demo). Para producción, se debe migrar a hash (`bcrypt`) y usar el sistema estándar de auth de Laravel.
