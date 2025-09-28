@section('content')
    @parent
    <div class="container px-0" id="graphs">
        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-sm btn-outline-primary me-1 period-btn" data-period="hour">{{ __('Product.puqProxmox.Hour') }}</button>
            <button class="btn btn-sm btn-outline-primary me-1 period-btn" data-period="day">{{ __('Product.puqProxmox.Day') }}</button>
            <button class="btn btn-sm btn-outline-primary me-1 period-btn" data-period="week">{{ __('Product.puqProxmox.Week') }}</button>
            <button class="btn btn-sm btn-outline-primary me-1 period-btn" data-period="month">{{ __('Product.puqProxmox.Month') }}</button>
            <button class="btn btn-sm btn-outline-primary period-btn" data-period="year">{{ __('Product.puqProxmox.Year') }}</button>
        </div>
        <div class="row">
            <div class="col-12 mb-3">
                <div class="card card-outline card-primary">
                    <div class="card-body">
                        <canvas id="cpuChart" style="width:100%; height:220px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12 mb-3">
                <div class="card card-outline card-success">
                    <div class="card-body">
                        <canvas id="memoryChart" style="width:100%; height:220px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12 mb-3">
                <div class="card card-outline card-warning">
                    <div class="card-body">
                        <canvas id="networkChart" style="width:100%; height:220px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12 mb-3">
                <div class="card card-outline card-danger">
                    <div class="card-body">
                        <canvas id="diskChart" style="width:100%; height:220px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        let charts = {};

        function formatLabels(data, period) {
            const locale = '{{ app()->getLocale() }}';
            return data.map(d => {
                const date = new Date(d.time * 1000);
                if (period === 'hour') return new Intl.DateTimeFormat(locale, {hour: '2-digit', minute:'2-digit'}).format(date);
                if (period === 'day') return new Intl.DateTimeFormat(locale, {hour: '2-digit'}).format(date);
                if (period === 'week' || period === 'month') return new Intl.DateTimeFormat(locale, {day: 'numeric', month: 'short'}).format(date);
                if (period === 'year') return new Intl.DateTimeFormat(locale, {month: 'short', year: 'numeric'}).format(date);
                return new Intl.DateTimeFormat(locale).format(date);
            });
        }

        function createChart(ctx, title, datasets, labels) {
            if (charts[ctx.id]) charts[ctx.id].destroy();

            charts[ctx.id] = new Chart(ctx, {
                type: 'line',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: title,
                            font: { size: 16, weight: 'bold' },
                            padding: { bottom: 10 }
                        },
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function loadData(period = 'hour') {
            blockUI('graphs');
            fetch("{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getLxcRrdData']) }}?timeframe=" + period)
                .then(r => r.json())
                .then(json => {
                    const data = json.data || [];
                    const labels = formatLabels(data, period);

                    createChart(document.getElementById('cpuChart'), '{{ __("Product.puqProxmox.CPU") }} (%)', [
                        {label: '{{ __("Product.puqProxmox.CPU") }} %', data: data.map(d => (d.cpu ? (d.cpu*100).toFixed(2) : 0)), borderColor: 'rgba(54,162,235,1)', backgroundColor: 'rgba(54,162,235,0.2)', fill:true, tension:0.3}
                    ], labels);

                    createChart(document.getElementById('memoryChart'), '{{ __("Product.puqProxmox.Memory") }} (GB)', [
                        {label: '{{ __("Product.puqProxmox.Used") }}', data: data.map(d => (d.mem ? (d.mem/1024/1024/1024).toFixed(2):0)), borderColor: 'rgba(75,192,192,1)', backgroundColor:'rgba(75,192,192,0.2)', fill:true, tension:0.3},
                        {label: '{{ __("Product.puqProxmox.Total") }}', data: data.map(d => (d.maxmem ? (d.maxmem/1024/1024/1024).toFixed(2):0)), borderColor:'rgba(153,102,255,1)', backgroundColor:'rgba(153,102,255,0.2)', fill:true, tension:0.3}
                    ], labels);

                    createChart(document.getElementById('networkChart'), '{{ __("Product.puqProxmox.Network") }} (KB/s)', [
                        {label:'{{ __("Product.puqProxmox.Incoming") }} KB/s', data:data.map(d => (d.netin?d.netin.toFixed(2):0)), borderColor:'rgba(255,159,64,1)', backgroundColor:'rgba(255,159,64,0.2)', fill:true, tension:0.3},
                        {label:'{{ __("Product.puqProxmox.Outgoing") }} KB/s', data:data.map(d => (d.netout?d.netout.toFixed(2):0)), borderColor:'rgba(255,205,86,1)', backgroundColor:'rgba(255,205,86,0.2)', fill:true, tension:0.3}
                    ], labels);

                    createChart(document.getElementById('diskChart'), '{{ __("Product.puqProxmox.DiskIO") }} (KB/s)', [
                        {label:'{{ __("Product.puqProxmox.Write") }} KB/s', data:data.map(d => (d.diskwrite?d.diskwrite.toFixed(2):0)), borderColor:'rgba(255,99,132,1)', backgroundColor:'rgba(255,99,132,0.2)', fill:true, tension:0.3},
                        {label:'{{ __("Product.puqProxmox.Read") }} KB/s', data:data.map(d => (d.diskread?d.diskread.toFixed(2):0)), borderColor:'rgba(54,162,235,1)', backgroundColor:'rgba(54,162,235,0.2)', fill:true, tension:0.3}
                    ], labels);

                    unblockUI('graphs');
                });
        }

        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', () => loadData(btn.dataset.period));
        });

        document.addEventListener('DOMContentLoaded', () => loadData());
    </script>
@endsection
