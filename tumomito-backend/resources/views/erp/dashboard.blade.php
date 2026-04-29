@extends('layouts.app')

@section('content')
<div class="header-section">
    <h1 class="gradient-text">Panel ERP</h1>
    <p>Compras, almacén, ventas y marketing en una sola vista.</p>
</div>

<div class="products-grid">
    <div class="glass-panel product-card">
        <h4><i class="fa-solid fa-sack-dollar"></i> Ventas hoy</h4>
        <p class="price">Bs. {{ number_format($ventasHoy, 2) }}</p>
        <p class="text-muted">Suma total de pedidos del día.</p>
    </div>
    <div class="glass-panel product-card">
        <h4><i class="fa-solid fa-truck-fast"></i> Pedidos pendientes</h4>
        <p class="price">{{ $pendientes }}</p>
        <p class="text-muted">Pendientes de logística/atención.</p>
    </div>
    <div class="glass-panel product-card">
        <h4><i class="fa-solid fa-triangle-exclamation"></i> Stock bajo</h4>
        <p class="price">{{ $stockBajo }}</p>
        <p class="text-muted">Productos por debajo del mínimo.</p>
        <a class="btn-primary" style="margin-top:10px;display:inline-block" href="{{ route('erp.stock_bajo', ['umbral' => 20]) }}">
            Ver stock &lt; 20
        </a>
    </div>
</div>

<div class="cart-layout mt-4">
    <div class="glass-panel cart-items">
        <h3>Ventas (últimos 14 días)</h3>
        <div id="chartVentas14" style="width:100%;min-height:320px"></div>
    </div>
    <div class="glass-panel cart-items">
        <h3>Movimientos inventario (14 días)</h3>
        <div id="chartMov14" style="width:100%;min-height:320px"></div>
    </div>
</div>

<div class="cart-items glass-panel mt-4">
    <h3>Últimos pedidos</h3>
    <table class="cart-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Canal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ultimosPedidos as $p)
                <tr>
                    <td>#{{ $p->id }}</td>
                    <td>{{ $p->usuario_id }}</td>
                    <td>Bs. {{ number_format($p->total, 2) }}</td>
                    <td>{{ $p->estado }}</td>
                    <td>{{ $p->canal_venta ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sin pedidos recientes.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
  const ventasLabels = @json($ventasLabels ?? []);
  const ventasSeries = @json($ventasSeries ?? []);
  const movLabels = @json($movLabels ?? []);
  const movEntradas = @json($movEntradas ?? []);
  const movSalidas = @json($movSalidas ?? []);

  const base = {
    chart: { foreColor: '#f8fafc', toolbar: { show: false } },
    theme: { mode: 'dark' },
    grid: { borderColor: 'rgba(255,255,255,0.08)' },
    tooltip: { theme: 'dark' },
  };

  new ApexCharts(document.querySelector('#chartVentas14'), {
    ...base,
    chart: { ...base.chart, type: 'area', height: 320 },
    series: [{ name: 'Ventas', data: ventasSeries }],
    xaxis: { categories: ventasLabels, labels: { rotate: -45 } },
    stroke: { curve: 'smooth', width: 3 },
    colors: ['#3b82f6'],
    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
    yaxis: { labels: { formatter: (v) => 'Bs. ' + Number(v).toFixed(2) } },
  }).render();

  new ApexCharts(document.querySelector('#chartMov14'), {
    ...base,
    chart: { ...base.chart, type: 'bar', height: 320, stacked: true },
    series: [
      { name: 'Entradas', data: movEntradas },
      { name: 'Salidas', data: movSalidas },
    ],
    xaxis: { categories: movLabels, labels: { rotate: -45 } },
    plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
    colors: ['#10b981', '#ef4444'],
  }).render();
</script>
@endsection
