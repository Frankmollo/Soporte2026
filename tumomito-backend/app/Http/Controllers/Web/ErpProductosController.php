<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ErpProductosController extends Controller
{
    public function index(Request $request): View
    {
        if (! Schema::hasTable('productos') || ! Schema::hasTable('categorias')) {
            abort(503, 'Tablas de catálogo no disponibles.');
        }

        $q = trim((string) $request->query('q', ''));
        $categoriaId = $request->query('categoria_id');

        $categorias = Categoria::query()->orderBy('nombre')->get();

        $query = Producto::query()
            ->with('categoria')
            ->leftJoin('categorias', 'categorias.id', '=', 'productos.categoria_id')
            ->orderBy('categorias.nombre')
            ->orderBy('productos.nombre')
            ->select('productos.*');

        if ($categoriaId !== null && $categoriaId !== '' && $categoriaId !== '0') {
            $query->where('productos.categoria_id', (int) $categoriaId);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('productos.nombre', 'like', "%{$q}%")
                    ->orWhere('productos.codigo', 'like', "%{$q}%");
            });
        }

        $productos = $query->paginate(24)->withQueryString();

        return view('erp.productos.index', [
            'productos' => $productos,
            'categorias' => $categorias,
            'q' => $q,
            'categoria_id' => $categoriaId === null || $categoriaId === '' ? '' : (string) $categoriaId,
        ]);
    }

    public function create(): View
    {
        if (! Schema::hasTable('productos') || ! Schema::hasTable('categorias')) {
            abort(503, 'Tablas de catálogo no disponibles.');
        }

        return view('erp.productos.form', [
            'producto' => null,
            'categorias' => Categoria::query()->orderBy('nombre')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('productos')) {
            abort(503);
        }

        $data = $this->validateProducto($request, null);
        $payload = $this->payloadFromValidated($data);

        Producto::query()->create($payload);

        return redirect()->route('erp.productos.index')->with('success', 'Producto creado.');
    }

    public function edit(int $id): View
    {
        if (! Schema::hasTable('productos')) {
            abort(503);
        }

        $producto = Producto::query()->findOrFail($id);

        return view('erp.productos.form', [
            'producto' => $producto,
            'categorias' => Categoria::query()->orderBy('nombre')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        if (! Schema::hasTable('productos')) {
            abort(503);
        }

        $producto = Producto::query()->findOrFail($id);
        $data = $this->validateProducto($request, $producto->id);
        $payload = $this->payloadFromValidated($data);

        $producto->fill($payload);
        $producto->save();

        return redirect()->route('erp.productos.index')->with('success', 'Producto actualizado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProducto(Request $request, ?int $productoId): array
    {
        $rules = [
            'nombre' => 'required|string|min:2|max:255',
            'categoria_id' => 'required|integer|exists:categorias,id',
            'precio' => 'required|numeric|min:0',
            'precio_mayorista' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
            'stock_maximo' => 'nullable|integer|min:0',
            'metodo_valoracion' => 'nullable|string|in:PEPS,UEPS',
        ];

        if (Schema::hasColumn('productos', 'codigo')) {
            $rules['codigo'] = [
                'nullable',
                'string',
                'max:100',
                Rule::unique('productos', 'codigo')->ignore($productoId),
            ];
        }

        if (! Schema::hasColumn('productos', 'precio_mayorista')) {
            unset($rules['precio_mayorista']);
        }
        if (! Schema::hasColumn('productos', 'stock_minimo')) {
            unset($rules['stock_minimo']);
        }
        if (! Schema::hasColumn('productos', 'stock_maximo')) {
            unset($rules['stock_maximo']);
        }
        if (! Schema::hasColumn('productos', 'metodo_valoracion')) {
            unset($rules['metodo_valoracion']);
        }

        $data = $request->validate($rules);
        if (Schema::hasColumn('productos', 'codigo')) {
            $codigo = isset($data['codigo']) ? trim((string) $data['codigo']) : '';
            $data['codigo'] = $codigo === '' ? null : $codigo;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payloadFromValidated(array $data): array
    {
        $payload = [
            'nombre' => $data['nombre'],
            'categoria_id' => (int) $data['categoria_id'],
            'precio' => $data['precio'],
            'stock' => (int) $data['stock'],
        ];

        if (Schema::hasColumn('productos', 'codigo')) {
            $payload['codigo'] = $data['codigo'];
        }

        if (Schema::hasColumn('productos', 'precio_mayorista')) {
            $payload['precio_mayorista'] = array_key_exists('precio_mayorista', $data) && $data['precio_mayorista'] !== null && $data['precio_mayorista'] !== ''
                ? $data['precio_mayorista']
                : null;
        }

        if (Schema::hasColumn('productos', 'stock_minimo')) {
            $payload['stock_minimo'] = (int) ($data['stock_minimo'] ?? 0);
        }

        if (Schema::hasColumn('productos', 'stock_maximo')) {
            $payload['stock_maximo'] = (int) ($data['stock_maximo'] ?? 0);
        }

        if (Schema::hasColumn('productos', 'metodo_valoracion')) {
            $payload['metodo_valoracion'] = $data['metodo_valoracion'] ?? 'PEPS';
        }

        return $payload;
    }
}
