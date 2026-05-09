<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ErpCategoriasController extends Controller
{
    public function index(Request $request): View
    {
        if (! Schema::hasTable('categorias')) {
            abort(503, 'Tabla categorías no disponible.');
        }

        $q = trim((string) $request->query('q', ''));

        $query = Categoria::query()->withCount('productos')->orderBy('nombre');

        if ($q !== '') {
            $query->where('nombre', 'like', "%{$q}%");
        }

        $categorias = $query->paginate(20)->withQueryString();

        return view('erp.categorias.index', compact('categorias', 'q'));
    }

    public function create(): View
    {
        if (! Schema::hasTable('categorias')) {
            abort(503);
        }

        return view('erp.categorias.form', [
            'categoria' => null,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('categorias')) {
            abort(503);
        }

        $nombre = $this->validatedNombre($request, null);

        Categoria::query()->create(['nombre' => $nombre]);

        return redirect()->route('erp.categorias.index')->with('success', 'Categoría creada.');
    }

    public function edit(int $id): View
    {
        if (! Schema::hasTable('categorias')) {
            abort(503);
        }

        $categoria = Categoria::query()->findOrFail($id);

        return view('erp.categorias.form', [
            'categoria' => $categoria,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        if (! Schema::hasTable('categorias')) {
            abort(503);
        }

        $categoria = Categoria::query()->findOrFail($id);
        $nombre = $this->validatedNombre($request, $categoria->id);

        $categoria->nombre = $nombre;
        $categoria->save();

        return redirect()->route('erp.categorias.index')->with('success', 'Categoría actualizada.');
    }

    public function destroy(int $id): RedirectResponse
    {
        if (! Schema::hasTable('categorias')) {
            abort(503);
        }

        $categoria = Categoria::query()->findOrFail($id);
        $conProductos = Schema::hasTable('productos')
            ? $categoria->productos()->count()
            : 0;

        $categoria->delete();

        $msg = 'Categoría eliminada.';
        if ($conProductos > 0) {
            $msg .= " Los {$conProductos} productos vinculados quedaron sin categoría (según política de la base de datos).";
        }

        return redirect()->route('erp.categorias.index')->with('success', $msg);
    }

    private function validatedNombre(Request $request, ?int $exceptId): string
    {
        $unique = Rule::unique('categorias', 'nombre');
        if ($exceptId !== null) {
            $unique = $unique->ignore($exceptId);
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'min:2', 'max:255', $unique],
        ]);

        return trim($data['nombre']);
    }
}
