@extends('layouts.app')

@section('content')
<div class="header-section">
    <h1 class="gradient-text">Stock (Almacén)</h1>
    <p>Listado de productos con su stock actual. Usa búsqueda y ordenamiento.</p>
</div>

<div class="glass-panel cart-items">
    <form method="GET" action="{{ route('erp.stock') }}" class="checkout-form" style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px;align-items:end">
        <div class="form-group">
            <label>Buscar (nombre o código)</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="Ej: lampara / 3002">
        </div>
        <div class="form-group">
            <label>Orden</label>
            <select name="orden" class="qty-input" style="width:100%;height:44px">
                <option value="stock_asc" @selected($orden==='stock_asc')>Stock ascendente</option>
                <option value="stock_desc" @selected($orden==='stock_desc')>Stock descendente</option>
                <option value="nombre" @selected($orden==='nombre')>Nombre</option>
            </select>
        </div>
        <input type="hidden" name="top" value="{{ $topN ?? 15 }}">
        <button class="btn-primary" type="submit" style="height:44px">Aplicar</button>
    </form>

    <div class="mt-3">
        <a class="btn-primary" href="{{ route('erp.stock_bajo', ['umbral' => 20]) }}">Ver stock &lt; 20</a>
        <a class="btn-primary" href="{{ route('erp.inventario') }}">Ver movimientos</a>
    </div>
</div>

<div class="cart-items glass-panel mt-4">
    <h3>Top productos por stock</h3>
    <div class="text-muted" style="margin-bottom:10px">
        Mostrando Top {{ $topN ?? 15 }}. 
        <a class="btn-primary" style="padding:0.35rem 0.6rem" href="{{ route('erp.stock', array_merge(request()->query(), ['top' => 10])) }}">Top 10</a>
        <a class="btn-primary" style="padding:0.35rem 0.6rem" href="{{ route('erp.stock', array_merge(request()->query(), ['top' => 15])) }}">Top 15</a>
        <a class="btn-primary" style="padding:0.35rem 0.6rem" href="{{ route('erp.stock', array_merge(request()->query(), ['top' => 25])) }}">Top 25</a>
    </div>
    <div id="chartTopStockProd" style="width:100%;min-height:340px"></div>
</div>

<div class="cart-items glass-panel mt-4">
    <h3>Distribución de stock (cantidad de productos)</h3>
    <div id="chartStockBins" style="width:100%;min-height:320px"></div>
</div>

<div class="cart-items glass-panel mt-4">
    <h3>Productos</h3>

    @if($productos instanceof \Illuminate\Support\Collection)
        <p>No hay base de productos cargada.</p>
    @else
        <table class="cart-table">
            <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Stock</th>
                <th>Min</th>
                <th>Max</th>
                <th>Método</th>
            </tr>
            </thead>
            <tbody>
            @forelse($productos as $p)
                <tr>
                    <td>{{ $p->codigo }}</td>
                    <td>{{ $p->nombre }}</td>
                    <td>{{ $p->categoria?->nombre ?? '-' }}</td>
                    <td><strong>{{ $p->stock }}</strong></td>
                    <td>{{ $p->stock_minimo ?? '-' }}</td>
                    <td>{{ $p->stock_maximo ?? '-' }}</td>
                    <td>{{ $p->metodo_valoracion ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Sin resultados.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div class="pagination-wrapper glass-panel">
            {{ $productos->links() }}
        </div>
    @endif
</div>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
  const binsLabels = @json($stockBinsLabels ?? []);
  const binsSeries = @json($stockBinsSeries ?? []);
  const topProdLabels = @json($topProdLabels ?? []);
  const topProdSeries = @json($topProdSeries ?? []);

  if (typeof ApexCharts !== 'undefined') {
    const binsEl = document.querySelector('#chartStockBins');
    if (binsEl && binsLabels.length > 0) {
      new ApexCharts(binsEl, {
        chart: { type: 'bar', height: 320, foreColor: '#f8fafc', toolbar: { show: false } },
        theme: { mode: 'dark' },
        grid: { borderColor: 'rgba(255,255,255,0.08)' },
        tooltip: { theme: 'dark' },
        series: [{ name: 'Productos', data: binsSeries }],
        xaxis: { categories: binsLabels },
        plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
        colors: ['#3b82f6'],
      }).render();
    }

    const topEl = document.querySelector('#chartTopStockProd');
    if (topEl && topProdLabels.length > 0) {
      new ApexCharts(topEl, {
        chart: { type: 'bar', height: 340, foreColor: '#f8fafc', toolbar: { show: false } },
        theme: { mode: 'dark' },
        grid: { borderColor: 'rgba(255,255,255,0.08)' },
        tooltip: { theme: 'dark' },
        series: [{ name: 'Stock', data: topProdSeries }],
        xaxis: { categories: topProdLabels, labels: { rotate: -35 } },
        plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
        colors: ['#10b981'],
      }).render();
    }
  }
</script>
@endsection

