<style>
    .stats-box {
        min-width: 220px;
    }
</style>

<div class="card shadow-sm border-0">
    <div class="card-body" id="info">
        <div class="d-flex flex-wrap text-start">

            <!-- CPU -->
            <div class="p-2 d-flex flex-column align-items-center flex-grow-1 flex-shrink-0
                border border-primary rounded me-2 mb-2 stats-box">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-microchip fs-5 text-primary me-2"></i>
                    <div class="fw-bold fs-6" id="cores-top">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    </div>
                </div>
                <div class="progress w-100 mb-1" style="height: 22px;">
                    <div id="cores-progress-used" class="progress-bar bg-danger" role="progressbar" style="width:0%">0%</div>
                    <div id="cores-progress-free" class="progress-bar bg-success" role="progressbar" style="width:0%">0%</div>
                </div>
                <div class="small text-muted text-center" id="cores-detail">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                </div>
            </div>

            <!-- RAM -->
            <div class="p-2 d-flex flex-column align-items-center flex-grow-1 flex-shrink-0
                border border-success rounded me-2 mb-2 stats-box">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-memory fs-5 text-success me-2"></i>
                    <div class="fw-bold fs-6" id="ram-top">
                        <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                    </div>
                </div>
                <div class="progress w-100 mb-1" style="height: 22px;">
                    <div id="ram-progress-used" class="progress-bar bg-danger" role="progressbar" style="width:0%">0%</div>
                    <div id="ram-progress-free" class="progress-bar bg-success" role="progressbar" style="width:0%">0%</div>
                </div>
                <div class="small text-muted text-center" id="ram-detail">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                </div>
            </div>

            <!-- Disk -->
            <div class="p-2 d-flex flex-column align-items-center flex-grow-1 flex-shrink-0
                border border-warning rounded me-2 mb-2 stats-box">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-hdd fs-5 text-warning me-2"></i>
                    <div class="fw-bold fs-6" id="disk-top">
                        <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                    </div>
                </div>
                <div class="progress w-100 mb-1" style="height: 22px;">
                    <div id="disk-progress-used" class="progress-bar bg-danger" role="progressbar" style="width:0%">0%</div>
                    <div id="disk-progress-free" class="progress-bar bg-success" role="progressbar" style="width:0%">0%</div>
                </div>
                <div class="small text-muted text-center" id="disk-detail">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                </div>
            </div>

            <!-- Backups -->
            <div class="p-2 d-flex flex-column align-items-center flex-grow-1 flex-shrink-0
                border border-purple rounded me-2 mb-2 stats-box">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-database fs-5 text-purple me-2"></i>
                    <div class="fw-bold fs-6" id="backups-top">
                        <div class="spinner-border spinner-border-sm text-purple" role="status"></div>
                    </div>
                </div>
                <div class="small text-muted text-center" id="backups-detail"></div>
            </div>

            <!-- Location -->
            <div class="p-2 d-flex flex-column align-items-center flex-grow-1 flex-shrink-0
                border border-info rounded me-2 mb-2 stats-box">
                <div class="d-flex align-items-center mb-2">
                    <img id="location-icon" src="" class="me-2" style="width:24px; height:24px; display:none;" alt="">
                    <div class="fw-bold fs-6" id="location-text">
                        <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                    </div>
                </div>
                <div class="small text-muted text-center" id="location-detail"></div>
            </div>

        </div>
    </div>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function formatBytesToGB1000(bytes) {
                return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GiB';
            }

            function loadInfoData() {
                PUQajax("{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getAppInfo']) }}", {}, 50, null, 'GET')
                    .then(function (response) {
                        var data = response.data;

                        // CPU
                        $('#cores-top').text(`${data.cpu_used_percent.toFixed(1)}% / ${data.cores} cores`);
                        $('#cores-progress-used').css('width', data.cpu_used_percent + '%').text(`${data.cpu_used_percent.toFixed(1)}%`);
                        $('#cores-progress-free').css('width', (100 - data.cpu_used_percent).toFixed(1) + '%').text(`${(100 - data.cpu_used_percent).toFixed(1)}%`);
                        $('#cores-detail').text(`{{ __('Product.puqProxmox.CPU cores total') }}: ${data.cores}`);

                        // RAM
                        var ramUsedGB = formatBytesToGB1000(data.memory_used);
                        var ramFreeGB = formatBytesToGB1000(data.memory_free);
                        var ramPercent = data.memory_used_percent.toFixed(1);
                        $('#ram-top').text(`${ramPercent}% / ${data.ram}`);
                        $('#ram-progress-used').css('width', ramPercent + '%').text(`${ramPercent}%`);
                        $('#ram-progress-free').css('width', (100 - ramPercent).toFixed(1) + '%').text(`${(100 - ramPercent).toFixed(1)}%`);
                        $('#ram-detail').text(`{{ __('Product.puqProxmox.RAM used/free') }}: ${ramUsedGB} / ${ramFreeGB}`);

                        // Disk
                        var diskUsedGB = formatBytesToGB1000(data.disk_used);
                        var diskFreeGB = formatBytesToGB1000(data.disk_free);
                        var diskPercent = data.disk_used_percent.toFixed(1);
                        $('#disk-top').text(`${diskPercent}% / ${data.disk}`);
                        $('#disk-progress-used').css('width', diskPercent + '%').text(`${diskPercent}%`);
                        $('#disk-progress-free').css('width', (100 - diskPercent).toFixed(1) + '%').text(`${(100 - diskPercent).toFixed(1)}%`);
                        $('#disk-detail').text(`{{ __('Product.puqProxmox.Disk used/free') }}: ${diskUsedGB} / ${diskFreeGB}`);

                        // Backups
                        $('#backups-top').text(`${data.backups} {{ __('Product.puqProxmox.Backups') }}`);
                        $('#backups-detail').text(`${data.backups} {{ __('Product.puqProxmox.Backups total') }}`);

                        // Location
                        $('#location-icon').attr('src', data.location_icon_url).show();
                        $('#location-text').text(`${data.location}`);
                        $('#location-detail').text(data.location_data_center);

                    })
                    .catch(function () {
                        // обработка ошибок
                    });
            }

            loadInfoData();
            setInterval(loadInfoData, 5000);
        });
    </script>
@endsection
