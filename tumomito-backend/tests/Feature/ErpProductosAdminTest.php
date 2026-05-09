<?php

namespace Tests\Feature;

use App\Models\Categoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpProductosAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_erp_productos(): void
    {
        $this->get('/erp/productos')->assertRedirect(route('auth.login.form'));
    }

    public function test_usuario_no_admin_redirige_desde_erp_productos(): void
    {
        $this->withSession([
            'tumomito_user_id' => 1,
            'tumomito_user_name' => 'Cliente',
            'tumomito_user_role' => 'cliente',
        ])->get('/erp/productos')->assertRedirect(route('store.index'));
    }

    public function test_admin_ve_pantalla_de_productos_por_categoria(): void
    {
        Categoria::query()->create(['nombre' => 'Demo']);

        $response = $this->withSession([
            'tumomito_user_id' => 1,
            'tumomito_user_name' => 'Admin',
            'tumomito_user_role' => 'admin',
        ])->get('/erp/productos');

        $response->assertOk();
        $response->assertSee('Productos por categoría', false);
    }
}
