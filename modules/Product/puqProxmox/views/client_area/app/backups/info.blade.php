<div class="main-card card mb-2">
    <div class="card-body p-3" id="info_card">
        <div class="row text-center mb-3 align-items-center">
            <div class="col-md-4 mb-2 mb-md-0">
                <h5 class="fw-bold">
                    <i class="fa fa-database text-primary me-1"></i> {{__('Product.puqProxmox.Max Backups')}}:
                    <span id="max_backups">0</span>
                </h5>
            </div>
            <div class="col-md-4 mb-2 mb-md-0">
                <h5 class="fw-bold">
                    <i class="fa fa-hdd text-success me-1"></i> {{__('Product.puqProxmox.Used Backups')}}:
                    <span id="used_backups">0</span>
                </h5>
            </div>
            <div class="col-md-4">
                <h5 class="fw-bold">
                    <i class="fa fa-info-circle me-1" id="status_icon"></i>
                    <span id="status_text">{{__('Product.puqProxmox.Status')}}: -</span>
                </h5>
            </div>
        </div>

        <div class="mb-2">
            <form id="backup_now_form">
                <label for="note" class="form-label small fw-bold">
                    <i class="fa fa-pencil-alt me-1"></i> {{__('Product.puqProxmox.Backup Note')}}
                </label>
                <input id="note" type="text" name="note" class="form-control form-control-sm">
            </form>
        </div>


        <div class="text-center">
            <button type="submit" class="btn-icon btn-2x btn btn-success restore-btn" id="backup_now">
                <i class="fa fa-clone me-1"></i> {{__('Product.puqProxmox.Backup Now')}}
            </button>
        </div>
    </div>
</div>

@section('js')
    @parent
    <script>
        function loadInfoData() {
            blockUI('info_card');
            PUQajax("{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getAppBackupsInfo']) }}", {}, 50, null, 'GET')
                .then(function (response) {
                    let data = response.data;

                    let max = data.max_backups;
                    let used = data.used_backups;
                    let status = data.status;

                    $('#max_backups').text(max);
                    $('#used_backups').text(used);

                    let $statusText = $('#status_text');
                    let $statusIcon = $('#status_icon');
                    let $note = $('#note');
                    let $button = $('#backup_now');

                    let disable = false;
                    let reason = '';

                    if (status !== 'running' && status !== 'stopped') {
                        disable = true;
                        reason = status;
                    } else if (used >= max) {
                        disable = true;
                        reason = '{{__('Product.puqProxmox.Max backups reached')}}';
                    }

                    if (status === 'running') {
                        $statusText.text('Status: Running');
                        $statusIcon.removeClass().addClass('fa fa-play-circle text-success me-1');
                    } else if (status === 'stopped') {
                        $statusText.text('Status: Stopped');
                        $statusIcon.removeClass().addClass('fa fa-stop-circle text-warning me-1');
                    } else if (status === 'backup') {
                        $statusText.text('Status: Backup');
                        $statusIcon.removeClass().addClass('fa fa-spinner fa-spin text-primary me-1');
                    } else if (status === 'create') {
                        $statusText.text('Status: Create/Restore');
                        $statusIcon.removeClass().addClass('fa fa-spinner fa-spin text-primary me-1');
                    } else {
                        $statusText.text('Status: ' + status);
                        $statusIcon.removeClass().addClass('fa fa-exclamation-circle text-secondary me-1');
                    }

                    if (disable) {
                        $note.prop('disabled', true).val('');
                        $button.prop('disabled', true);
                        $note.attr('placeholder', reason);
                    } else {
                        $note.prop('disabled', false).val('');
                        $button.prop('disabled', false);
                        $note.attr('placeholder', '');
                    }

                    unblockUI('info_card');
                });
        }

        $(document).ready(function () {
            $('#backup_now').on('click', function (event) {
                event.preventDefault();
                if ($('#backup_now_form').length) {
                    var $form = $('#backup_now_form');
                    var formData = serializeForm($form);
                    PUQajax('{{ route('client.api.cloud.service.module.post', ['uuid' => $service_uuid, 'method' => 'postAppBackupNow']) }}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            loadInfoData();
                        });
                }
            });

            loadInfoData();
        });
    </script>

@endsection
