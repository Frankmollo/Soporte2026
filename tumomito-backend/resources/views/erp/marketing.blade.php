@extends('layouts.app')

@section('content')
<div class="header-section">
    <h1 class="gradient-text">Marketing KPI (Facebook / TikTok)</h1>
</div>

<div class="cart-layout">
    <div class="glass-panel cart-items">
        <h3>Ingresos vs inversión (30 días)</h3>
        <div id="chartMkIngresos" style="width:100%;min-height:320px"></div>
    </div>
    <div class="glass-panel cart-items">
        <h3>ROAS (30 días)</h3>
        <div id="chartMkRoas" style="width:100%;min-height:320px"></div>
    </div>
</div>

<div class="cart-layout">
    <div class="glass-panel cart-items">
        <h3>Cargar KPI diario</h3>
        <form action="{{ route('erp.marketing.store') }}" method="POST" class="checkout-form">
            @csrf
            <div class="form-group"><label>Fecha</label><input type="date" name="fecha" required value="{{ now()->toDateString() }}"></div>
            <div class="form-group">
                <label>Canal</label>
                <select name="canal" class="qty-input" style="width:100%;height:44px">
                    <option value="facebook">facebook</option>
                    <option value="tiktok">tiktok</option>
                    <option value="general">general</option>
                </select>
            </div>
            <div class="form-group"><label>Inversión</label><input type="number" step="0.01" min="0" name="inversion" required></div>
            <div class="form-group"><label>Visitas</label><input type="number" min="0" name="visitas" required></div>
            <div class="form-group"><label>Leads</label><input type="number" min="0" name="leads" required></div>
            <div class="form-group"><label>Ventas</label><input type="number" min="0" name="ventas" required></div>
            <div class="form-group"><label>Ingresos</label><input type="number" step="0.01" min="0" name="ingresos" required></div>
            <div class="form-group"><label>Recompras</label><input type="number" min="0" name="recompras"></div>
            <div class="form-group"><label>Abandono carrito (0-1)</label><input type="number" step="0.0001" min="0" max="1" name="abandono_carrito"></div>
            <button class="btn-success btn-block" type="submit">Guardar KPI</button>
        </form>
    </div>
</div>

<div class="cart-items glass-panel mt-4">
    <h3>Últimos KPI</h3>
    <table class="cart-table">
        <thead><tr><th>Fecha</th><th>Canal</th><th>Inversión</th><th>Ingresos</th><th>ROAS</th><th>CAC</th><th>Conv.</th></tr></thead>
        <tbody>
        @forelse($rows as $r)
            <tr>
                <td>{{ $r->fecha }}</td>
                <td>{{ $r->canal }}</td>
                <td>Bs. {{ number_format($r->inversion, 2) }}</td>
                <td>Bs. {{ number_format($r->ingresos, 2) }}</td>
                <td>{{ $r->roas }}</td>
                <td>{{ $r->cac }}</td>
                <td>{{ $r->conversion_rate }}</td>
            </tr>
        @empty
            <tr><td colspan="7">Sin KPI registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
  const mLabels = @json($mLabels ?? []);
  const mIngresos = @json($mIngresos ?? []);
  const mInversion = @json($mInversion ?? []);
  const mRoas = @json($mRoas ?? []);

  const base = {
    chart: { foreColor: '#f8fafc', toolbar: { show: false } },
    theme: { mode: 'dark' },
    grid: { borderColor: 'rgba(255,255,255,0.08)' },
    tooltip: { theme: 'dark' },
  };

  new ApexCharts(document.querySelector('#chartMkIngresos'), {
    ...base,
    chart: { ...base.chart, type: 'line', height: 320 },
    series: [
      { name: 'Ingresos', data: mIngresos },
      { name: 'Inversión', data: mInversion },
    ],
    xaxis: { categories: mLabels, labels: { rotate: -45 } },
    stroke: { curve: 'smooth', width: 3 },
    colors: ['#10b981', '#3b82f6'],
    yaxis: { labels: { formatter: (v) => 'Bs. ' + Number(v).toFixed(2) } },
  }).render();

  new ApexCharts(document.querySelector('#chartMkRoas'), {
    ...base,
    chart: { ...base.chart, type: 'area', height: 320 },
    series: [{ name: 'ROAS', data: mRoas }],
    xaxis: { categories: mLabels, labels: { rotate: -45 } },
    stroke: { curve: 'smooth', width: 3 },
    colors: ['#8b5cf6'],
    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
  }).render();
</script>
