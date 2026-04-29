@extends('layouts.app')

@section('content')
<div class="header-section">
    <h1 class="gradient-text">Compras y Proveedores</h1>
</div>

<div class="cart-layout">
    <div class="glass-panel cart-items">
        <h3>Compras (últimos 30 días) — por semana</h3>
        <div id="chartCompras30" style="width:100%;min-height:300px"></div>
    </div>
    <div class="glass-panel cart-items">
        <h3>Top proveedores (30 días) — participación</h3>
        <div id="chartTopProv" style="width:100%;min-height:300px"></div>
    </div>
</div>

<div class="cart-layout">
    <div class="glass-panel cart-items">
        <h3>Registrar compra</h3>
        <form action="{{ route('erp.compras.store') }}" method="POST" class="checkout-form">
            @csrf
            <div class="form-group">
                <label>Proveedor</label>
                <select name="proveedor_id" class="qty-input" style="width:100%;height:44px">
                    @foreach($proveedores as $pr)
                        <option value="{{ $pr->id }}">{{ $pr->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Referencia</label>
                <input type="text" name="referencia" placeholder="Contenedor / factura proveedor">
            </div>

            @for($i=0; $i<3; $i++)
                <div class="glass-panel" style="padding:12px;margin-bottom:10px">
                    <div class="form-group">
                        <label>Producto {{ $i+1 }}</label>
                        <select name="producto_id[]" class="qty-input" style="width:100%;height:44px">
                            @foreach($productos as $p)
                                <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cantidad</label>
                        <input type="number" name="cantidad[]" value="1" min="1">
                    </div>
                    <div class="form-group">
                        <label>Costo unitario</label>
                        <input type="number" step="0.0001" name="costo_unitario[]" value="1.00" min="0">
                    </div>
                </div>
            @endfor
            <button class="btn-success btn-block" type="submit">Guardar compra</button>
        </form>
    </div>

    <div class="glass-panel checkout-panel">
        <h3>Nuevo proveedor</h3>
        <form action="{{ route('erp.proveedores.store') }}" method="POST" class="checkout-form">
            @csrf
            <div class="form-group"><label>Nombre</label><input type="text" name="nombre" required></div>
            <div class="form-group"><label>Contacto</label><input type="text" name="contacto"></div>
            <div class="form-group"><label>Teléfono</label><input type="text" name="telefono"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email"></div>
            <button class="btn-primary btn-block" type="submit">Crear proveedor</button>
        </form>
    </div>
</div>

<div class="cart-items glass-panel mt-4">
    <h3>Últimas compras</h3>
    <table class="cart-table">
        <thead><tr><th>ID</th><th>Proveedor</th><th>Total</th><th>Estado</th></tr></thead>
        <tbody>
        @forelse($compras as $c)
            <tr>
                <td>#{{ $c->id }}</td>
                <td>{{ $c->proveedor->nombre ?? '-' }}</td>
                <td>Bs. {{ number_format($c->total, 2) }}</td>
                <td>{{ $c->estado }}</td>
            </tr>
        @empty
            <tr><td colspan="4">Sin compras.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
  const comprasWeekLabels = @json($comprasWeekLabels ?? []);
  const comprasWeekSeries = @json($comprasWeekSeries ?? []);
  const topProvLabels = @json($topProvLabels ?? []);
  const topProvSeries = @json($topProvSeries ?? []);

  const base = {
    chart: { foreColor: '#f8fafc', toolbar: { show: false } },
    theme: { mode: 'dark' },
    tooltip: { theme: 'dark' },
    legend: { position: 'bottom' },
    stroke: { colors: ['rgba(255,255,255,0.08)'] },
  };

  if (comprasWeekLabels.length > 0) {
    new ApexCharts(document.querySelector('#chartCompras30'), {
      ...base,
      chart: { ...base.chart, type: 'donut', height: 300 },
      labels: comprasWeekLabels,
      series: comprasWeekSeries,
      colors: ['#8b5cf6', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#c084fc'],
      dataLabels: { enabled: true },
      plotOptions: { pie: { donut: { size: '55%' } } },
      tooltip: {
        theme: 'dark',
        y: { formatter: (v) => 'Bs. ' + Number(v).toFixed(2) }
      },
    }).render();
  }

  if (topProvLabels.length > 0) {
    new ApexCharts(document.querySelector('#chartTopProv'), {
      ...base,
      chart: { ...base.chart, type: 'donut', height: 300 },
      labels: topProvLabels,
      series: topProvSeries,
      colors: ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#22c55e', '#60a5fa', '#c084fc'],
      plotOptions: { pie: { donut: { size: '55%' } } },
      tooltip: {
        theme: 'dark',
        y: { formatter: (v) => 'Bs. ' + Number(v).toFixed(2) }
      },
    }).render();
  }
</script>
@endsection
