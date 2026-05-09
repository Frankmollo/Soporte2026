<?php

namespace Tests\Feature;

use App\Models\Categoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpCategoriasAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_erp_categorias(): void
    {
        $this->get('/erp/categorias')->assertRedirect(route('auth.login.form'));
    }

    public function test_cliente_redirige_desde_erp_categorias(): void
    {
        $this->withSession([
            'tumomito_user_id' => 1,
            'tumomito_user_name' => 'Cliente',
            'tumomito_user_role' => 'cliente',
        ])->get('/erp/categorias')->assertRedirect(route('store.index'));
    }

    public function test_admin_puede_ver_y_crear_categoria(): void
    {
        $session = [
            'tumomito_user_id' => 1,
            'tumomito_user_name' => 'Admin',
            'tumomito_user_role' => 'admin',
        ];

        $this->withSession($session)->get('/erp/categorias')->assertOk()->assertSee('Categorías', false);

        $this->withSession($session)
            ->post('/erp/categorias', ['nombre' => 'Herramientas'])
            ->assertRedirect(route('erp.categorias.index'));

        $this->assertDatabaseHas('categorias', ['nombre' => 'Herramientas']);
    }

    public function test_admin_no_duplica_nombre(): void
    {
        Categoria::query()->create(['nombre' => 'Única']);

        $this->withSession([
            'tumomito_user_id' => 1,
            'tumomito_user_name' => 'Admin',
            'tumomito_user_role' => 'admin',
        ])->post('/erp/categorias', ['nombre' => 'Única'])
            ->assertSessionHasErrors('nombre');

        $this->assertSame(1, Categoria::query()->where('nombre', 'Única')->count());
    }
}
