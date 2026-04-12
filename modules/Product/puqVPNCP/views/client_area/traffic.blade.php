@section('content')
    @parent
    <div class="container px-0">
        <div class="card shadow-sm mb-2">
            <div class="card-body">

                <div class="row mb-3">
                    <div class="col-auto">
                        <label class="form-label fw-bold">{{ __('Product.puqVPNCP.Year') }}</label>
                        <select id="traffic-year" class="form-select" style="width: auto;">
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label fw-bold">{{ __('Product.puqVPNCP.Month') }}</label>
                        <select id="traffic-month" class="form-select" style="width: auto;">
                            <option value="01">January</option>
                            <option value="02">February</option>
                            <option value="03">March</option>
                            <option value="04">April</option>
                            <option value="05">May</option>
                            <option value="06">June</option>
                            <option value="07">July</option>
                            <option value="08">August</option>
                            <option value="09">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <div class="col-auto d-flex align-items-end">
                        <button class="btn btn-primary" id="load-traffic">
                            <i class="fas fa-chart-line me-1"></i>{{ __('Product.puqVPNCP.Load') }}
                        </button>
                    </div>
                </div>

                <div id="traffic-loading" class="text-center d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>

                <canvas id="trafficChart" height="100"></canvas>

                <div id="traffic-totals" class="mt-3 d-none">
                    <div class="row">
                        <div class="col-6 col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6>{{ __('Product.puqVPNCP.Total Download') }}</h6>
                                    <span id="total-download" class="fw-bold text-primary fs-5">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6>{{ __('Product.puqVPNCP.Total Upload') }}</h6>
                                    <span id="total-upload" class="fw-bold text-success fs-5">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        var trafficChart = null;

        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)), 10);
            return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + sizes[i];
        }

        function loadTraffic() {
            const year = $('#traffic-year').val();
            const month = $('#traffic-month').val();

            $('#traffic-loading').removeClass('d-none');
            $('#traffic-totals').addClass('d-none');

            $.ajax({
                url: "{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'traffic']) }}",
                method: "GET",
                data: {year: year, month: month},
                success: function (response) {
                    $('#traffic-loading').addClass('d-none');

                    if (response.status !== 'success' || !response.data) return;

                    const data = Array.isArray(response.data) ? response.data : Object.values(response.data);
                    const labels = data.map((d, i) => d.day || d.date || (i + 1));
                    const downloads = data.map(d => d.traffic_download || d.download || d.rx || 0);
                    const uploads = data.map(d => d.traffic_upload || d.upload || d.tx || 0);

                    const totalDl = downloads.reduce((a, b) => a + b, 0);
                    const totalUl = uploads.reduce((a, b) => a + b, 0);

                    $('#total-download').text(formatBytes(totalDl));
                    $('#total-upload').text(formatBytes(totalUl));
                    $('#traffic-totals').removeClass('d-none');

                    if (trafficChart) trafficChart.destroy();

                    const ctx = document.getElementById('trafficChart').getContext('2d');
                    trafficChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: '{{ __("Product.puqVPNCP.Download") }}',
                                    data: downloads,
                                    borderColor: 'rgb(54, 162, 235)',
                                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                                    fill: true,
                                    tension: 0.3
                                },
                                {
                                    label: '{{ __("Product.puqVPNCP.Upload") }}',
                                    data: uploads,
                                    borderColor: 'rgb(75, 192, 192)',
                                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                                    fill: true,
                                    tension: 0.3
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function (value) {
                                            return formatBytes(value);
                                        }
                                    }
                                }
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function (ctx) {
                                            return ctx.dataset.label + ': ' + formatBytes(ctx.raw);
                                        }
                                    }
                                }
                            }
                        }
                    });
                },
                error: function () {
                    $('#traffic-loading').addClass('d-none');
                }
            });
        }

        $(document).ready(function () {
            // Populate year selector
            const currentYear = new Date().getFullYear();
            const currentMonth = String(new Date().getMonth() + 1).padStart(2, '0');

            for (let y = currentYear; y >= currentYear - 3; y--) {
                $('#traffic-year').append(`<option value="${y}">${y}</option>`);
            }
            $('#traffic-month').val(currentMonth);

            // Load available periods
            $.ajax({
                url: "{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'trafficAvailable']) }}",
                method: "GET",
                success: function (response) {
                    // If data available, we already have the selectors
                }
            });

            $('#load-traffic').on('click', loadTraffic);
            loadTraffic();
        });
    </script>
@endsection
