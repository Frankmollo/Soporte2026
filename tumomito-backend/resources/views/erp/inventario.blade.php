@extends('layouts.app')

@section('content')
<div class="header-section">
    <h1 class="gradient-text">Movimientos de Inventario</h1>
</div>

<div class="glass-panel cart-items">
    <h3>Entradas vs salidas (últimos 30 días)</h3>
    <p class="minor-label">
        Solo aparecen registros de <strong>compras ERP</strong>, <strong>checkout web</strong> y ventas creadas con
        <code>tumomito:generar-ventas-demo</code>. Si solo cargaste productos sin esas operaciones, verás el gráfico en cero.
    </p>
    <div id="chartInv30" style="width:100%;min-height:320px"></div>
</div>

<div class="cart-items glass-panel">
    <table class="cart-table">
        <thead>
        <tr>
            <th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cant.</th><th>Stock ant.</th><th>Stock nuevo</th><th>Ref.</th>
        </tr>
        </thead>
        <tbody>
        @forelse($movimientos as $m)
            <tr>
                <td>{{ $m->fecha }}</td>
                <td>{{ $m->producto->nombre ?? ('#'.$m->producto_id) }}</td>
                <td>{{ strtoupper($m->tipo) }}</td>
                <td>{{ $m->cantidad }}</td>
                <td>{{ $m->stock_anterior }}</td>
                <td>{{ $m->stock_nuevo }}</td>
                <td>{{ $m->referencia_tipo }} #{{ $m->referencia_id }}</td>
            </tr>
        @empty
            <tr><td colspan="7">Sin movimientos.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
  const invLabels = @json($invLabels ?? []);
  const invEntradas = @json($invEntradas ?? []);
  const invSalidas = @json($invSalidas ?? []);

  new ApexCharts(document.querySelector('#chartInv30'), {
    chart: { type: 'bar', height: 320, stacked: true, foreColor: '#f8fafc', toolbar: { show: false } },
    theme: { mode: 'dark' },
    grid: { borderColor: 'rgba(255,255,255,0.08)' },
    tooltip: { theme: 'dark' },
    series: [
      { name: 'Entradas', data: invEntradas },
      { name: 'Salidas', data: invSalidas },
    ],
    xaxis: { categories: invLabels, labels: { rotate: -45 } },
    plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
    colors: ['#10b981', '#ef4444'],
  }).render();
</script>
@endsection
