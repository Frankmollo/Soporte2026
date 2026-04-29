@extends('layouts.app')

@section('content')
<div class="header-section">
    <h1 class="gradient-text">Almacén: stock bajo</h1>
    <p>Productos con stock menor al umbral definido.</p>
</div>

<div class="glass-panel cart-items">
    <form method="GET" action="{{ route('erp.stock_bajo') }}" class="checkout-form" style="max-width:420px">
        <div class="form-group">
            <label>Umbral (mostrar stock &lt;)</label>
            <input type="number" name="umbral" min="0" value="{{ $umbral }}" required>
        </div>
        <button class="btn-primary btn-block" type="submit">Filtrar</button>
    </form>
</div>

<div class="cart-items glass-panel mt-4">
    <h3>Stock bajo por categoría</h3>
    <div id="chartStockBajoCat" style="width:100%;min-height:320px"></div>
</div>

<div class="cart-items glass-panel mt-4">
    <h3>Resultados</h3>
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
            </tr>
            </thead>
            <tbody>
            @forelse($productos as $p)
                <tr>
                    <td>{{ $p->codigo }}</td>
                    <td>{{ $p->nombre }}</td>
                    <td>{{ $p->categoria?->nombre ?? '-' }}</td>
                    <td><strong>{{ $p->stock }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="4">No hay productos con stock menor a {{ $umbral }}.</td></tr>
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
  const catLabels = @json($catLabels ?? []);
  const catSeries = @json($catSeries ?? []);

  if (catLabels.length > 0) {
    new ApexCharts(document.querySelector('#chartStockBajoCat'), {
      chart: { type: 'bar', height: 320, foreColor: '#f8fafc', toolbar: { show: false } },
      theme: { mode: 'dark' },
      grid: { borderColor: 'rgba(255,255,255,0.08)' },
      tooltip: { theme: 'dark' },
      series: [{ name: 'Productos', data: catSeries }],
      xaxis: { categories: catLabels, labels: { rotate: -35 } },
      plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
      colors: ['#ef4444'],
    }).render();
  }
</script>
@endsection
