# Propuesta de Solución Empresarial y Transformación Digital: TUMOMITO

## 1. Resumen Ejecutivo y Diagnóstico

La empresa importadora TUMOMITO atraviesa actualmente una desaceleración en sus ventas. Tras el análisis de sus procesos, se determinó que la principal causa de este estancamiento es el uso de métodos de gestión obsoletos (dependencia exclusiva de planillas Excel como `Cotizacion 2021.xlsx`) y la ausencia de una infraestructura digital moderna.

Esta falta de automatización no solo limita el crecimiento comercial de la empresa, sino que dificulta el control del inventario y la captación de nuevos clientes en la era digital.

## 2. Objetivos de la Propuesta

- Incrementar las ventas mediante la apertura de nuevos canales digitales disponibles 24/7.
- Automatizar la gestión operativa, abandonando el registro manual en hojas de cálculo.
- Optimizar el control de inventario, evitando mermas por productos obsoletos y protegiendo el margen de ganancia en cada importación.
- Posicionar la marca de manera sólida en redes sociales clave para captar tanto a minoristas (B2B) como a clientes finales (B2C).

## 3. Solución Tecnológica Integral

La modernización de TUMOMITO se abordará mediante la implementación de un ecosistema tecnológico de tres pilares:

### 3.1. Automatización con Sistema ERP (Enterprise Resource Planning)

Se implementará un software ERP (en la práctica, una plataforma unificada desarrollada sobre el framework **Laravel**) que concentrará las áreas de la empresa:

- **Compras:** Base para proyecciones de importación según histórico de ventas (integrable con módulos de reporting).
- **Ventas:** Facturación rápida, seguimiento de cuentas por cobrar y emisión de recibos.
- **Almacén:** Control preciso de ingresos y salidas físicas de la mercadería.

Laravel centraliza rutas web, vistas, ORM **Eloquent**, migraciones, validación, colas y autenticación (sesiones web estándar; **Sanctum** solo si más adelante se expone API para integraciones externas).

### 3.2. Gestión Estratégica de Inventario (Técnicas PEPS y UEPS)

Dentro del módulo de almacén, se estructurará el inventario por **lotes de importación** (fecha y costo de llegada) para aplicar:

- **PEPS:** El sistema asignará para la venta la mercadería de los lotes más antiguos, reduciendo obsolescencia en productos sensibles (tecnología o vencimiento).
- **UEPS:** Para productos con costos de importación crecientes, se priorizará el costeo según el último ingreso, protegiendo la coherencia entre costo de reposición y política de precios.

Esta lógica se implementará en la capa de negocio Laravel (servicios o acciones dedicadas) sobre tablas relacionales en MySQL, garantizando **trazabilidad** de cada salida ligada a los lotes utilizados.

### 3.3. Tienda Virtual (E-Commerce B2B y B2C)

Se desarrollará una tienda virtual de alto rendimiento, conectada en tiempo real al backend para mostrar el **stock actualizado** (lectura desde la base de datos tras cada operación válida).

#### A. Arquitectura: aplicación única Laravel (front y back)

**Toda la solución vivirá en un solo proyecto Laravel:** la interfaz de usuario no es una aplicación separada (Angular, React standalone, etc.), sino parte del mismo monolito.

| Componente | Tecnología | Función |
|--------------|------------|---------|
| **Presentación (frontend)** | **Laravel** | Vistas **Blade** (`resources/views`), layouts, formularios y componentes; estilos y JavaScript con **Vite** (asset bundling nativo de Laravel). Opcionalmente **Livewire** o **Alpine.js** para interactividad (carrito, feedback) sin salir del ecosistema PHP. |
| **Lógica y HTTP (backend)** | **Laravel (PHP 8.2+)** | Rutas `routes/web.php`, controladores, requests, políticas, reglas de negocio (carrito, checkout, facturación, PEPS/UEPS). Las rutas `routes/api.php` pueden usarse solo si se publican endpoints JSON para integraciones; la tienda principal se sirve por web. |
| **Persistencia** | **MySQL 8** *(MariaDB / XAMPP compatible)* | Datos transaccionales con **InnoDB**; una sola base de datos consultada por Eloquent desde los mismos controladores que renderizan las vistas. |

Ventajas de este enfoque: un solo repositorio y despliegue, sesiones y autenticación web coherentes, validación y mensajes de error en el mismo flujo, y mantenimiento acorde a un equipo que domina **Laravel de punta a punta**.

#### B. Modelo de datos y uso del Excel actual

El archivo `Cotizacion 2021.xlsx` **no se desecha de inmediato**; actúa como **puente**:

- **ETL (Extract–Transform–Load):** Los datos de las hojas (marcas, códigos, precios) se limpian y se importan de forma masiva a tablas MySQL (scripts o seeders Laravel, o procesos en Python que generen SQL/CSV coherente con el esquema).
- **Tablas fundamentales** (alineadas al dominio del proyecto):

  - `usuarios` (id, nombre, email, contraseña_hash, dirección, tipo de cliente donde aplique B2B/B2C).
  - `categorias` (id, nombre).
  - `productos` (id, código, nombre, precio, stock, `categoria_id`, criterio PEPS/UEPS si se modela por producto).
  - `lotes_importacion` (cuando se implemente inventario por lote: producto, cantidades, fecha de ingreso, costo).
  - `pedidos` (id, `usuario_id`, fecha, estado, `total_calculado`).
  - `detalle_pedido` (id, `pedido_id`, `producto_id`, `cantidad_comprada`, precio unitario, subtotal).
  - `facturas` / `carrito` según el alcance operativo acordado.

MySQL centraliza el catálogo y las operaciones; **Laravel** actúa como **única aplicación** (vistas + controladores + modelos) que accede a los datos.

## 4. Estrategia de Marketing Digital

Para garantizar tráfico y ventas al canal digital:

- **Facebook (retargeting y B2B):** Catálogo dinámico enlazado con Facebook Ads; retargeting a visitantes sin compra; segmentación a dueños de negocios mayoristas.
- **TikTok (branding):** Contenido corto orgánico (unboxing de contenedores, recorridos por almacén, productos en tendencia) para modernizar la imagen de TUMOMITO.

## 5. Conclusión y Resultados Esperados

La implementación de esta propuesta (**Laravel íntegro: interfaz + lógica de negocio + MySQL**) transformará a TUMOMITO en una organización moderna y orientada a datos (**data-driven**), con un stack unificado y fácil de desplegar.

**Resultados esperados a corto y mediano plazo:**

- **Crecimiento en ventas:** Canal digital disponible todo el día y alcance mediante redes sociales.
- **Control total del inventario:** Reducción de mermas por obsolescencia mediante PEPS y trazabilidad por lotes.
- **Eficiencia operativa:** Menos carga manual en Excel; más tiempo para servicio al cliente y estrategia comercial.

---

## 6. Estado de implementación (técnico) — alineación con esta propuesta

| Elemento de la propuesta | Estado |
|---------------------------|--------|
| Aplicación monolítica Laravel (front + back) | **Hecho:** vistas Blade (`store/*`), rutas web, `@vite` en layout + estilos en `public/css/style.css`. |
| APIs JSON opcionales | **Hecho:** endpoints en `routes/api.php` (catálogo, carrito, checkout) compatibles con integraciones futuras. |
| MySQL + Eloquent sobre tablas de negocio | **Hecho:** modelos ligados al esquema importado (`productos`, `pedidos`, etc.) y configuración `.env` MySQL. |
| Puente desde Excel (`Cotizacion 2021.xlsx`) | **Hecho en el proyecto raíz:** scripts Python ETL (`extractor.py`, `create_database.py`, `export_mysql.py`). |
| Inventario PEPS / UEPS con lotes y trazabilidad | **Hecho:** migración `add_lotes_inventory_peps_ueps`; tablas `lotes_importacion` y `detalle_pedido_lotes`; columna `productos.metodo_valoracion` (`PEPS` por defecto, `UEPS` editable); servicio `InventarioPorLotesService` integrado en `CheckoutService` (fallback sin tablas = descuento de stock simple). |
| Checkout con stock válido | **Hecho:** servicio único `CheckoutService` con transacciones y bloqueos. |
| Carga inicial de lotes tras migraciones | **Hecho:** comando `php artisan tumomito:inicializar-lotes-desde-stock` (opción `--dry-run`). Sin lotes previos, el checkout también crea un lote único desde el stock vigente. |
| Catálogo con indicativo de método (PEPS/UEPS) | **Hecho:** badge opcional en el catálogo cuando la columna existe. |
| **Compras** (proyecciones por histórico) | *Pendiente / roadmap*: no automatizado en código; integrable con reporting o módulos futuros. |
| **Ventas**: cuentas por cobrar, cartera completa | *Parcial*: factura y pedido; cobranzas avanzadas no modeladas aún. |
| **Marketing** Facebook Ads / TikTok | *Ejecutorio comercial*: lineamientos descritos arriba; la pauta depende del equipo no del repositorio. |

**Pasos recomendados al desplegar o actualizar:**

1. `cd tumomito-backend && composer install && npm install`
2. Asegurarse de que MySQL tenga cargada la base de negocio y aplicar **`php artisan migrate`** (añade PEPS/UEPS y tablas de lotes sin borrar datos existentes de `productos`).  
3. Ejecutar **`php artisan tumomito:inicializar-lotes-desde-stock`** una vez tras la migración si desea tener todos los stocks reflejados en lotes (opcional porque el checkout ya crea lotes cuando faltan).  
4. `npm run dev` o `npm run build` para Vite cuando use assets compilados.

*Documento alineado a implementación en `tumomito-backend/`: **Laravel para interfaz y servidor**, **MySQL** como motor de datos, ETL desde Excel vía scripts o seeders.*
