<div class="card mb-2 shadow-sm border-0" style="min-height: 260px;">
    <div class="card-header bg-light d-flex align-items-center">
        <i class="fas fa-server text-primary me-2"></i>
        <span class="fw-bold">{{ __('Product.puqProxmox.Control') }}</span>
    </div>
    <div class="card-body" id="status">
        <div class="row align-items-center">
            <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-4 text-center">
                <button id="status_btn" type="button" class="btn btn-secondary btn-lg rounded-circle mb-2"
                        style="width:80px; height:80px; font-size:1.2rem;">
                    <div class="spinner-border spinner-border-sm"></div>
                </button>
                <div class="text-muted" id="status_text">{{ __('Product.puqProxmox.loading...') }}<br>
                    <div class="spinner-border spinner-border-sm"></div>
                </div>
                <div class="small text-muted" id="uptime_text"></div>
            </div>
            <div class="col-12 col-sm-12 col-md-6 col-lg-8 col-xl-8 col-xxl-8">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i
                                class="fas fa-microchip me-1 text-secondary"></i>{{ __('Product.puqProxmox.CPU') }}</span>
                        <span class="small" id="cpu_usage"><div
                                class="spinner-border spinner-border-sm text-secondary"></div></span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div id="cpu_bar_used" class="progress-bar bg-danger" role="progressbar" style="width:0%"></div>
                        <div id="cpu_bar_free" class="progress-bar bg-success" role="progressbar"
                             style="width:100%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i
                                class="fas fa-memory me-1 text-secondary"></i>{{ __('Product.puqProxmox.Memory') }}</span>
                        <span class="small" id="mem_usage"><div
                                class="spinner-border spinner-border-sm text-secondary"></div></span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div id="mem_bar_used" class="progress-bar bg-danger" role="progressbar" style="width:0%"></div>
                        <div id="mem_bar_free" class="progress-bar bg-success" role="progressbar"
                             style="width:100%"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-hdd me-1 text-secondary"></i>{{ __('Product.puqProxmox.Disk') }}</span>
                        <span class="small" id="disk_usage"><div
                                class="spinner-border spinner-border-sm text-secondary"></div></span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div id="disk_bar_used" class="progress-bar bg-danger" role="progressbar"
                             style="width:0%"></div>
                        <div id="disk_bar_free" class="progress-bar bg-success" role="progressbar"
                             style="width:100%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button type="button" class="btn btn-primary me-2 mb-2" id="start">
                            <i class="fas fa-play me-1"></i> {{ __('Product.puqProxmox.Start') }}
                        </button>
                        <button type="button" class="btn btn-danger me-2 mb-2" id="stop">
                            <i class="fas fa-stop me-1"></i> {{ __('Product.puqProxmox.Stop') }}
                        </button>
                        <button type="button" class="btn btn-secondary me-2 mb-2" id="console">
                            <i class="fas fa-terminal me-1"></i> {{ __('Product.puqProxmox.Console') }}
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-warning fw-bold me-2 mb-2" id="reset_passwords">
                            <i class="fas fa-key me-2"></i> {{ __('Product.puqProxmox.Reset Passwords') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@section('js')
    @parent

    <script>
        $(document).ready(function () {
            let firstLoad = true;

            function formatBytes(bytes) {
                let units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];
                if (bytes === 0) return '0 B';
                let i = Math.floor(Math.log(bytes) / Math.log(1024));
                let value = bytes / Math.pow(1024, i);
                return value.toFixed(2) + ' ' + units[i];
            }

            function loadStatusData() {
                if (firstLoad) {
                    blockUI('status');
                }
                PUQajax("{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getLxcStatus']) }}", {}, 50, null, 'GET')
                    .then(function (response) {
                        let d = response.data;


                        if (d) {
                            let cpuPercent = (d.cpu * 100).toFixed(2);
                            $("#cpu_usage").html(cpuPercent + "% used");
                            $("#cpu_bar_used").css("width", cpuPercent + "%");
                            $("#cpu_bar_free").css("width", (100 - cpuPercent) + "%");

                            let memPercent = d.memory_max ? (d.memory_used / d.memory_max) * 100 : 0;
                            memPercent = isNaN(memPercent) ? 0 : memPercent.toFixed(2);
                            $("#mem_usage").html(memPercent + "% (" + formatBytes(d.memory_used) + " of " + formatBytes(d.memory_max) + ")");
                            $("#mem_bar_used").css("width", memPercent + "%");
                            $("#mem_bar_free").css("width", (100 - memPercent) + "%");

                            let diskPercent = d.disk_max ? (d.disk_used / d.disk_max) * 100 : 0;
                            diskPercent = isNaN(diskPercent) ? 0 : diskPercent.toFixed(2);
                            $("#disk_usage").html(diskPercent + "% (" + formatBytes(d.disk_used) + " of " + formatBytes(d.disk_max) + ")");
                            $("#disk_bar_used").css("width", diskPercent + "%");
                            $("#disk_bar_free").css("width", (100 - diskPercent) + "%");

                            $("#status_text").text(d.status);
                            $("#uptime_text").text("Uptime: " + d.uptime);


                            switch (d.status) {
                                case "running":
                                    $("#status_btn")
                                        .removeClass()
                                        .addClass("btn btn-success btn-lg rounded-circle mb-2")
                                        .text("{{ __('Product.puqProxmox.ON') }}");
                                    $("#start").prop("disabled", true);
                                    $("#stop, #console, #reset_passwords").prop("disabled", false);
                                    break;

                                case "stopped":
                                    $("#status_btn")
                                        .removeClass()
                                        .addClass("btn btn-danger btn-lg rounded-circle mb-2")
                                        .text("{{ __('Product.puqProxmox.OFF') }}");
                                    $("#start").prop("disabled", false);
                                    $("#stop, #console, #reset_passwords").prop("disabled", true);
                                    break;

                                case "backup":
                                    $("#status_text").text("backup");
                                    $("#status_btn")
                                        .removeClass()
                                        .addClass("btn btn-warning btn-lg rounded-circle mb-2")
                                        .html('<i class="fa fa-database fa-lg text-secondary"></i><br><i class="fa fa-spinner fa-spin fa-lg text-secondary"></i>');
                                    $("#start, #stop, #console, #reset_passwords").prop("disabled", true);
                                    break;

                                case "migrate":
                                    $("#status_text").text("migrate");
                                    $("#status_btn")
                                        .removeClass()
                                        .addClass("btn btn-info btn-lg rounded-circle mb-2")
                                        .html('<i class="fa fa-paper-plane fa-lg text-white"></i><br><i class="fa fa-spinner fa-spin fa-lg text-white"></i>');
                                    $("#start, #stop, #console, #reset_passwords").prop("disabled", true);
                                    break;

                                case "create":
                                    $("#status_text").text("create");
                                    $("#status_btn")
                                        .removeClass()
                                        .addClass("btn btn-success btn-lg rounded-circle mb-2")
                                        .html('<i class="fa fa-plus fa-lg text-white"></i><br><i class="fa fa-spinner fa-spin fa-lg text-white"></i>');
                                    $("#start, #stop, #console, #reset_passwords").prop("disabled", true);
                                    break;

                                case "unknown":
                                    $("#status_text").text("unknown");
                                    $("#uptime_text").text("");
                                    $("#start, #stop, #console, #reset_passwords").prop("disabled", true);
                                    break;

                                default:
                                    $("#status_btn")
                                        .removeClass()
                                        .addClass("btn btn-warning btn-lg rounded-circle mb-2")
                                        .text("{{ __('Product.puqProxmox.ERR') }}");
                                    $("#start, #stop, #console, #reset_passwords").prop("disabled", true);
                                    break;
                            }

                            if (!d.status) {
                                $("#status_btn")
                                    .removeClass()
                                    .addClass("btn bg-warning btn-lg rounded-circle mb-2");
                                $("#status_text").html("<span class='badge bg-warning text-dark'>deploying</span>");
                                $("#uptime_text").text("");
                                $("#start, #stop, #console, #reset_passwords").prop("disabled", true);
                            }

                        }
                        if (firstLoad) {
                            unblockUI('status');
                            firstLoad = false;
                        }
                    })
                    .catch(function () {
                        $("#status_text").text("error");
                        $("#status_btn").removeClass().addClass("btn btn-warning btn-lg rounded-circle mb-2").text("{{ __('Product.puqProxmox.ERR') }}");
                        if (firstLoad) {
                            unblockUI('status');
                            firstLoad = false;
                        }
                    });
            }

            loadStatusData();
            setInterval(loadStatusData, 3000);

            $('#start').on('click', function () {

                blockUI('status');
                PUQajax('{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getLxcStart']) }}', {}, 1000, $(this), 'GET')
                    .then(function (response) {
                        unblockUI('status');
                        loadStatusData();
                    })
                    .catch(function (error) {
                        unblockUI('status');
                        console.error('Error loading form data:', error);
                    });
            });

            $('#stop').on('click', function () {

                blockUI('status');
                PUQajax('{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getLxcStop']) }}', {}, 1000, $(this), 'GET')
                    .then(function (response) {
                        unblockUI('status');
                        loadStatusData();
                    })
                    .catch(function (error) {
                        unblockUI('status');
                        console.error('Error loading form data:', error);
                    });
            });

            $('#console').on('click', function () {
                blockUI('status');
                PUQajax('{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getLxcConsole']) }}', {}, 1000, $(this), 'GET')
                    .then(function (response) {
                        unblockUI('status');

                        if (response.data) {
                            window.open(response.data, 'consoleWindow', 'width=800,height=600');
                        }

                    })
                    .catch(function (error) {
                        unblockUI('status');
                        console.error(error);
                    });
            });

            $('#reset_passwords').on('click', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{ __('Product.puqProxmox.Are you sure you want to Reset Passwords?') }}')) {
                    blockUI('status');
                    PUQajax('{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getLxcResetPasswords']) }}', {}, 1000, $(this), 'GET')
                        .then(function (response) {
                            unblockUI('status');
                            $('#show_password_once-block').removeClass('d-none');
                        })
                        .catch(function (error) {
                            unblockUI('status');
                            console.error('Error loading form data:', error);
                        });
                }
            });



        });
    </script>
@endsection
