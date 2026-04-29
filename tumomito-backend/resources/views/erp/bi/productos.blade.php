@extends('layouts.app')

@section('content')
<div class="header-section">
    <h1 class="gradient-text">BI: Productos</h1>
    <p>Más vendidos vs menos vendidos (según unidades) en el rango.</p>
</div>

<div class="glass-panel cart-items">
    <form method="GET" action="{{ route('erp.bi.productos') }}" class="checkout-form" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;align-items:end;max-width:900px">
        <div class="form-group">
            <label>Desde</label>
            <input type="date" name="desde" value="{{ $desde }}">
        </div>
        <div class="form-group">
            <label>Hasta</label>
            <input type="date" name="hasta" value="{{ $hasta }}">
        </div>
        <button class="btn-primary" type="submit" style="height:44px">Actualizar</button>
    </form>
</div>

<div class="cart-layout mt-4">
    <div class="glass-panel cart-items">
        <h3>Top 10 más vendidos</h3>
        <div id="chartTop" style="width:100%;min-height:340px"></div>
        <div id="chartTopMsg" class="text-muted mt-3" style="display:none"></div>
        @if(isset($hayVentas) && !$hayVentas)
            <p class="text-muted mt-3">Aún no hay pedidos en el rango seleccionado. Registra ventas (checkout) para ver “más vendidos”.</p>
        @endif
    </div>
    <div class="glass-panel cart-items">
        <h3>Top 10 menos vendidos</h3>
        <div id="chartBottom" style="width:100%;min-height:340px"></div>
        <div id="chartBottomMsg" class="text-muted mt-3" style="display:none"></div>
        <p class="text-muted mt-3">Incluye productos con 0 ventas en el rango.</p>
    </div>
</div>

<div class="cart-items glass-panel mt-4">
    <h3>Detalle</h3>
    <table class="cart-table">
        <thead><tr><th>Producto</th><th>Unidades</th><th>Ingresos</th></tr></thead>
        <tbody>
        @foreach($top as $r)
            <tr>
                <td>{{ $r->nombre }}</td>
                <td>{{ $r->unidades }}</td>
                <td>Bs. {{ number_format($r->ingresos, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    try {
      const top = @json($top);
      const bottom = @json($bottom);

      const safeName = (r) => (r && r.nombre ? String(r.nombre) : '(sin nombre)');

      function toBarData(rows) {
        const arr = Array.isArray(rows) ? rows : [];
        return {
          labels: arr.map(r => {
            const n = safeName(r);
            return n.length > 26 ? (n.slice(0, 26) + '…') : n;
          }),
          values: arr.map(r => Number(r && r.unidades ? r.unidades : 0)),
        };
      }

      const topData = toBarData(top);
      const bottomData = toBarData(bottom);

      const baseBar = (title, labels, values, color) => ({
        chart: { type: 'bar', height: 340, foreColor: '#f8fafc', toolbar: { show: false } },
        theme: { mode: 'dark' },
        series: [{ name: title, data: values }],
        xaxis: { categories: labels, labels: { rotate: -35 } },
        plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
        grid: { borderColor: 'rgba(255,255,255,0.08)' },
        colors: [color],
        tooltip: { theme: 'dark' },
      });

      const topEl = document.querySelector('#chartTop');
      const botEl = document.querySelector('#chartBottom');

      if (!topEl || !botEl || typeof ApexCharts === 'undefined') {
        const msg = 'No se pudo cargar ApexCharts o el contenedor del gráfico.';
        const m1 = document.querySelector('#chartTopMsg');
        const m2 = document.querySelector('#chartBottomMsg');
        if (m1) { m1.style.display = 'block'; m1.textContent = msg; }
        if (m2) { m2.style.display = 'block'; m2.textContent = msg; }
        return;
      }

      if (topData.labels.length > 0) {
        new ApexCharts(topEl, baseBar('Unidades', topData.labels, topData.values, '#10b981')).render();
      } else {
        const m = document.querySelector('#chartTopMsg');
        if (m) { m.style.display = 'block'; m.textContent = 'Sin datos para “más vendidos” en este rango.'; }
      }

      if (bottomData.labels.length > 0) {
        new ApexCharts(botEl, baseBar('Unidades', bottomData.labels, bottomData.values, '#ef4444')).render();
      } else {
        const m = document.querySelector('#chartBottomMsg');
        if (m) { m.style.display = 'block'; m.textContent = 'Sin datos para “menos vendidos” en este rango.'; }
      }
    } catch (e) {
      const msg = 'Error al renderizar gráficos. Revisa la consola del navegador.';
      const m1 = document.querySelector('#chartTopMsg');
      const m2 = document.querySelector('#chartBottomMsg');
      if (m1) { m1.style.display = 'block'; m1.textContent = msg; }
      if (m2) { m2.style.display = 'block'; m2.textContent = msg; }
    }
  });
</script>
@endsection

