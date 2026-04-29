@extends('layouts.app')

@section('content')
<div class="header-section">
    <h1 class="gradient-text">BI: Ventas</h1>
    <p>Ventas por día, semana o mes.</p>
</div>

<div class="glass-panel cart-items">
    <form method="GET" action="{{ route('erp.bi.ventas') }}" class="checkout-form" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:end">
        <div class="form-group">
            <label>Desde</label>
            <input type="date" name="desde" value="{{ $desde }}">
        </div>
        <div class="form-group">
            <label>Hasta</label>
            <input type="date" name="hasta" value="{{ $hasta }}">
        </div>
        <div class="form-group">
            <label>Agrupación</label>
            <select name="agrupacion" class="qty-input" style="width:100%;height:44px">
                <option value="dia" @selected($agrupacion==='dia')>Día</option>
                <option value="semana" @selected($agrupacion==='semana')>Semana</option>
                <option value="mes" @selected($agrupacion==='mes')>Mes</option>
            </select>
        </div>
        <button class="btn-primary" type="submit" style="height:44px">Actualizar</button>
    </form>
</div>

<div class="products-grid mt-4">
    <div class="glass-panel product-card">
        <h4>Ventas</h4>
        <p class="price">Bs. {{ number_format($kpis['ventas'] ?? 0, 2) }}</p>
    </div>
    <div class="glass-panel product-card">
        <h4>Pedidos</h4>
        <p class="price">{{ $kpis['pedidos'] ?? 0 }}</p>
    </div>
    <div class="glass-panel product-card">
        <h4>Ticket promedio</h4>
        <p class="price">Bs. {{ number_format($kpis['ticket_promedio'] ?? 0, 2) }}</p>
    </div>
</div>

<div class="glass-panel cart-items mt-4">
    <h3>Gráfico de ventas</h3>
    <div id="chartVentas" style="width:100%;min-height:360px"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
  const labels = @json($labels);
  const series = @json($series);

  const options = {
    chart: { type: 'area', height: 360, foreColor: '#f8fafc', toolbar: { show: false } },
    theme: { mode: 'dark' },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 3 },
    series: [{ name: 'Ventas', data: series }],
    xaxis: { categories: labels, labels: { rotate: -45 } },
    yaxis: { labels: { formatter: (v) => 'Bs. ' + (Math.round(v*100)/100).toFixed(2) } },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } },
    colors: ['#3b82f6'],
    grid: { borderColor: 'rgba(255,255,255,0.08)' },
    tooltip: { theme: 'dark' },
  };

  new ApexCharts(document.querySelector("#chartVentas"), options).render();
</script>
@endsection

