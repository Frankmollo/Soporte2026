<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TUMOMITO | Tienda Virtual Premium</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('build/.vite/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <div class="blob-bg"></div>
    <div class="blob-bg blob-2"></div>
    
    <nav class="glass-nav">
        <div class="nav-brand">
            <a href="{{ route('store.index') }}">
                <i class="fa-solid fa-gem"></i> TUMOMITO
            </a>
        </div>
        <div class="nav-links">
            <a href="{{ route('store.index') }}"><i class="fa-solid fa-box-open"></i> Catálogo</a>
            @if(session('tumomito_user_id'))
            <a href="{{ route('cart.index') }}" class="cart-btn">
                <i class="fa-solid fa-cart-shopping"></i> Carrito
            </a>
            @if(session('tumomito_user_role') === 'admin')
                <a href="{{ route('erp.dashboard') }}"><i class="fa-solid fa-chart-line"></i> ERP</a>
            @endif
            <span class="user-chip"><i class="fa-solid fa-user"></i> {{ session('tumomito_user_name', 'Usuario') }}</span>
            <form action="{{ route('auth.logout') }}" method="POST" style="display:inline">
                @csrf
                <button type="submit" class="btn-primary" style="padding:0.45rem 0.8rem">Salir</button>
            </form>
            @else
            <a href="{{ route('auth.login.form') }}" class="cart-btn"><i class="fa-solid fa-right-to-bracket"></i> Ingresar</a>
            <a href="{{ route('auth.register.form') }}" class="cart-btn"><i class="fa-solid fa-user-plus"></i> Registrarse</a>
            @endif
        </div>
    </nav>

    <main class="container">
        <div class="app-shell">
            @if(session('tumomito_user_id'))
            <aside class="sidebar-left glass-panel">
                <div class="sidebar-title">
                    <span class="gradient-text">Menú</span>
                </div>

                <div class="sidebar-section" data-accordion>
                    <button type="button" class="sidebar-section-toggle" data-acc-toggle="tienda">
                        <span class="sidebar-section-title">Tienda</span>
                        <i class="fa-solid fa-chevron-down acc-icon" aria-hidden="true"></i>
                    </button>
                    <div class="sidebar-section-body" data-acc-body="tienda">
                        <a class="sidebar-link {{ request()->routeIs('store.index') ? 'active' : '' }}" href="{{ route('store.index') }}">
                            <i class="fa-solid fa-box-open"></i> Catálogo
                        </a>
                        <a class="sidebar-link {{ request()->routeIs('cart.*') ? 'active' : '' }}" href="{{ route('cart.index') }}">
                            <i class="fa-solid fa-cart-shopping"></i> Carrito
                        </a>
                    </div>
                </div>

                @if(session('tumomito_user_role') === 'admin')
                    <div class="sidebar-section" data-accordion>
                        <button type="button" class="sidebar-section-toggle" data-acc-toggle="erp">
                            <span class="sidebar-section-title">ERP</span>
                            <i class="fa-solid fa-chevron-down acc-icon" aria-hidden="true"></i>
                        </button>
                        <div class="sidebar-section-body" data-acc-body="erp">
                            <a class="sidebar-link {{ request()->routeIs('erp.dashboard') ? 'active' : '' }}" href="{{ route('erp.dashboard') }}">
                                <i class="fa-solid fa-chart-line"></i> Dashboard
                            </a>
                            <a class="sidebar-link {{ request()->routeIs('erp.usuarios.*') ? 'active' : '' }}" href="{{ route('erp.usuarios.index') }}">
                                <i class="fa-solid fa-users-gear"></i> Usuarios
                            </a>
                            <a class="sidebar-link {{ request()->routeIs('erp.productos.*') ? 'active' : '' }}" href="{{ route('erp.productos.index') }}">
                                <i class="fa-solid fa-tags"></i> Catálogo (por categoría)
                            </a>
                            <a class="sidebar-link {{ request()->routeIs('erp.compras*') ? 'active' : '' }}" href="{{ route('erp.compras') }}">
                                <i class="fa-solid fa-truck-ramp-box"></i> Compras
                            </a>
                            <a class="sidebar-link {{ request()->routeIs('erp.stock') ? 'active' : '' }}" href="{{ route('erp.stock') }}">
                                <i class="fa-solid fa-warehouse"></i> Stock
                            </a>
                            <a class="sidebar-link {{ request()->routeIs('erp.stock_bajo') ? 'active' : '' }}" href="{{ route('erp.stock_bajo', ['umbral' => 20]) }}">
                                <i class="fa-solid fa-triangle-exclamation"></i> Stock bajo
                            </a>
                            <a class="sidebar-link {{ request()->routeIs('erp.inventario') ? 'active' : '' }}" href="{{ route('erp.inventario') }}">
                                <i class="fa-solid fa-right-left"></i> Movimientos
                            </a>
                            <a class="sidebar-link {{ request()->routeIs('erp.marketing*') ? 'active' : '' }}" href="{{ route('erp.marketing') }}">
                                <i class="fa-brands fa-tiktok"></i> Marketing
                            </a>
                        </div>
                    </div>

                    <div class="sidebar-section" data-accordion>
                        <button type="button" class="sidebar-section-toggle" data-acc-toggle="bi">
                            <span class="sidebar-section-title">BI</span>
                            <i class="fa-solid fa-chevron-down acc-icon" aria-hidden="true"></i>
                        </button>
                        <div class="sidebar-section-body" data-acc-body="bi">
                            <a class="sidebar-link {{ request()->routeIs('erp.bi.ventas') ? 'active' : '' }}" href="{{ route('erp.bi.ventas') }}">
                                <i class="fa-solid fa-chart-area"></i> Ventas
                            </a>
                            <a class="sidebar-link {{ request()->routeIs('erp.bi.productos') ? 'active' : '' }}" href="{{ route('erp.bi.productos') }}">
                                <i class="fa-solid fa-ranking-star"></i> Productos
                            </a>
                        </div>
                    </div>
                @endif

                <div class="sidebar-section">
                    <div class="sidebar-section-title">Cuenta</div>
                    <span class="sidebar-link" style="cursor:default;opacity:0.9">
                        <i class="fa-solid fa-user"></i> {{ session('tumomito_user_name', 'Usuario') }}
                    </span>
                    <form action="{{ route('auth.logout') }}" method="POST" style="margin-top:0.5rem">
                        @csrf
                        <button type="submit" class="btn-primary btn-block" style="width:100%">Cerrar sesión</button>
                    </form>
                </div>
            </aside>
            @endif

            <section class="app-content">
                @if(session('success'))
                    <div class="alert alert-success glass-panel">
                        <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-error glass-panel">
                        <i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </section>
        </div>
    </main>

    <footer class="glass-footer">
        <p>&copy; 2026 TUMOMITO Importaciones. Diseño Premium.</p>
    </footer>

    <script>
      (function () {
        const key = 'tumomito_sidebar_sections_v1';
        const defaults = { tienda: true, erp: true, bi: true };

        function loadState() {
          try {
            const raw = localStorage.getItem(key);
            if (!raw) return { ...defaults };
            return { ...defaults, ...JSON.parse(raw) };
          } catch (_) {
            return { ...defaults };
          }
        }

        function saveState(state) {
          try { localStorage.setItem(key, JSON.stringify(state)); } catch (_) {}
        }

        const state = loadState();

        document.querySelectorAll('[data-acc-toggle]').forEach((btn) => {
          const id = btn.getAttribute('data-acc-toggle');
          const body = document.querySelector('[data-acc-body=\"' + id + '\"]');
          if (!body) return;

          // Si la sección contiene el link activo, se fuerza abierta
          if (body.querySelector('.sidebar-link.active')) {
            state[id] = true;
            saveState(state);
          }

          const apply = () => {
            const open = !!state[id];
            body.style.display = open ? 'block' : 'none';
            btn.classList.toggle('is-open', open);
          };

          apply();

          btn.addEventListener('click', () => {
            state[id] = !state[id];
            saveState(state);
            apply();
          });
        });
      })();
    </script>
</body>
</html>
