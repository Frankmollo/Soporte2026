## TUMOMITO — ERP + E‑Commerce (Laravel + MySQL)

Proyecto monolítico en **Laravel** (front Blade + back) con **MySQL**.

### Credenciales de prueba

- **Admin (ERP/BI/Usuarios)**: `frank@gmail.com` / `12345`
- **Cliente (Catálogo/Carrito/Checkout)**: puedes crear uno en `/registro`

Si en tu base de datos NO existen esos usuarios, créalos con Artisan (ver abajo).

### Requisitos

- PHP 8.2+
- Composer
- MySQL (o MariaDB)

### Instalación rápida

1) Instalar dependencias

```bash
composer install
```

2) Configurar `.env`

- Copia `.env.example` a `.env`
- Ajusta credenciales MySQL:
  - `DB_DATABASE=tumomito_bd`
  - `DB_USERNAME=...`
  - `DB_PASSWORD=...`

3) Generar key, migrar y levantar

```bash
php artisan key:generate
php artisan migrate
php artisan serve
```

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
