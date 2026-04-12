@extends(config('template.client.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')

    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="fas fa-plus-circle icon-gradient bg-tempting-azure"></i>
                </div>
                <div>
                    {{ __('main.Open a Ticket') }}
                    <div class="page-title-subheading text-muted">
                        {{ __('main.Submit a new support request') }}
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <a href="{{ route('client.web.panel.support.tickets') }}" class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info">
                    <i class="fa fa-arrow-left me-1"></i> {{ __('main.Back to Tickets') }}
                </a>
            </div>
        </div>
    </div>

    <div class="container px-0">
        <div class="card shadow-sm">
            <div class="card-body">
                <form id="ticketCreateForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department_uuid" class="form-label"><i class="fas fa-building me-1"></i>{{ __('main.Department') }} <span class="text-danger">*</span></label>
                            <select id="department_uuid" name="department_uuid" class="form-control" style="width: 100%;"></select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label"><i class="fas fa-flag me-1"></i>{{ __('main.Priority') }}</label>
                            <select id="priority" name="priority" class="form-control">
                                <option value="low">{{ __('main.Low') }}</option>
                                <option value="medium" selected>{{ __('main.Medium') }}</option>
                                <option value="high">{{ __('main.High') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="subject" class="form-label"><i class="fas fa-heading me-1"></i>{{ __('main.Subject') }} <span class="text-danger">*</span></label>
                            <input type="text" id="subject" name="subject" class="form-control" maxlength="255" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="service_uuid" class="form-label"><i class="fas fa-server me-1"></i>{{ __('main.Related Service') }}</label>
                            <select id="service_uuid" name="service_uuid" class="form-control" style="width: 100%;"></select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="message" class="form-label"><i class="fas fa-comment-alt me-1"></i>{{ __('main.Message') }} <span class="text-danger">*</span></label>
                            <textarea id="message" name="message" class="form-control" rows="8" maxlength="50000" required></textarea>
                        </div>
                    </div>

                    <div id="attachmentsSection" class="row d-none">
                        <div class="col-md-12 mb-3">
                            <label class="form-label"><i class="fas fa-paperclip me-1"></i>{{ __('main.Attachments') }}</label>
                            <div id="fileInputsContainer"></div>
                            <small id="fileSettingsHint" class="form-text text-muted"></small>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" id="submitTicket" class="btn-icon btn-outline-2x btn btn-outline-success">
                            <i class="fa fa-paper-plane me-1"></i> {{ __('main.Submit Ticket') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            var $department = $('#department_uuid');
            var $service = $('#service_uuid');
            var fileSettingsUrl = '{{ route('client.api.support.ticket_department.file_settings.get', '__UUID__') }}';
            var fileSettings = {};

            initializeSelect2(
                $department[0],
                '{{ route('client.api.support.ticket_departments.select.get') }}',
                null,
                'GET',
                1000,
                {
                    placeholder: '{{ __('main.Select Department') }}',
                    allowClear: false,
                    minimumResultsForSearch: -1
                }
            );

            initializeSelect2(
                $service[0],
                '{{ route('client.api.support.ticket_services.select.get') }}',
                {id: '', text: '{{ __('main.None') }}'},
                'GET',
                1000,
                {
                    placeholder: '{{ __('main.Select Service (optional)') }}',
                    allowClear: true
                }
            );

            // Load file settings when department changes
            $department.on('change', function () {
                var deptUuid = $(this).val();
                if (!deptUuid) {
                    $('#attachmentsSection').addClass('d-none');
                    fileSettings = {};
                    return;
                }

                var url = fileSettingsUrl.replace('__UUID__', deptUuid);
                PUQajax(url, {}, 1000, null, 'GET').then(function (response) {
                    if (response && response.data) {
                        fileSettings = response.data;
                        var maxFiles = fileSettings.max_files_count || 3;

                        var $container = $('#fileInputsContainer');
                        $container.empty();
                        for (var i = 0; i < maxFiles; i++) {
                            $container.append(
                                '<input type="file" name="create_attachment_' + i + '" class="form-control mb-1 create-file-input">'
                            );
                        }

                        var hints = [];
                        if (fileSettings.allowed_extensions) {
                            hints.push('{{ __('main.Allowed') }}: ' + fileSettings.allowed_extensions);
                        }
                        if (fileSettings.max_file_size_mb) {
                            hints.push('{{ __('main.Max size') }}: ' + fileSettings.max_file_size_mb + ' MB');
                        }
                        if (fileSettings.max_files_count) {
                            hints.push('{{ __('main.Max files') }}: ' + fileSettings.max_files_count);
                        }
                        $('#fileSettingsHint').text(hints.join(' | '));
                        $('#attachmentsSection').removeClass('d-none');
                    }
                });
            });

            function getSelectedFiles() {
                var files = [];
                $('.create-file-input').each(function () {
                    if (this.files && this.files.length > 0) {
                        files.push(this.files[0]);
                    }
                });
                return files;
            }

            function validateFiles(files) {
                if (!files || files.length === 0) return true;

                if (fileSettings.allowed_extensions) {
                    var allowed = fileSettings.allowed_extensions.split(',').map(function (ext) {
                        return ext.trim().toLowerCase();
                    });
                    for (var i = 0; i < files.length; i++) {
                        var ext = files[i].name.split('.').pop().toLowerCase();
                        if (allowed.indexOf(ext) === -1) {
                            alert_error(translate('Error'), ['{{ __('error.File extension not allowed') }}: ' + files[i].name], 5000);
                            return false;
                        }
                    }
                }

                if (fileSettings.max_file_size_mb) {
                    var maxBytes = fileSettings.max_file_size_mb * 1024 * 1024;
                    for (var i = 0; i < files.length; i++) {
                        if (files[i].size > maxBytes) {
                            alert_error(translate('Error'), ['{{ __('error.File size exceeds maximum allowed') }}: ' + files[i].name], 5000);
                            return false;
                        }
                    }
                }

                return true;
            }

            function uploadAttachments(replyUuid, files, callback) {
                if (!files || files.length === 0) {
                    if (callback) callback();
                    return;
                }

                var attachmentUploadUrl = '{{ route('client.api.support.ticket.attachment.post') }}';
                var uploaded = 0;
                for (var i = 0; i < files.length; i++) {
                    var formData = new FormData();
                    formData.append('reply_uuid', replyUuid);
                    formData.append('file', files[i]);

                    $.ajax({
                        url: attachmentUploadUrl,
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'Authorization': 'Bearer ' + $('meta[name="api_token"]').attr('content'),
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function () {
                            uploaded++;
                            if (uploaded >= files.length && callback) callback();
                        },
                        error: function () {
                            uploaded++;
                            if (uploaded >= files.length && callback) callback();
                        }
                    });
                }
            }

            $('#ticketCreateForm').on('submit', function (e) {
                e.preventDefault();
                var $btn = $('#submitTicket');
                var $form = $(this);
                var files = getSelectedFiles();

                if (!validateFiles(files)) return;

                var data = {
                    subject: $('#subject').val(),
                    message: $('#message').val(),
                    department_uuid: $department.val(),
                    priority: $('#priority').val(),
                    service_uuid: $service.val() || null,
                };

                PUQajax(
                    '{{ route('client.api.support.ticket.post') }}',
                    data,
                    5000,
                    $btn,
                    'POST',
                    $form
                ).then(function (response) {
                    if (response && response.data && response.data.redirect) {
                        if (files.length > 0 && response.data.ticket_uuid) {
                            $('#attachmentsSection').html(
                                '<div class="col-md-12 text-center py-3"><i class="fa fa-spinner fa-spin fa-2x"></i>' +
                                '<div class="mt-2 text-muted">{{ __('main.Uploading files...') }}</div></div>'
                            ).removeClass('d-none');
                            $('#submitTicket').prop('disabled', true);
                            var ticketApiUrl = '{{ route('client.api.support.ticket.get', '__UUID__') }}'.replace('__UUID__', response.data.ticket_uuid);
                            $.ajax({
                                url: ticketApiUrl,
                                method: 'GET',
                                headers: {
                                    'Authorization': 'Bearer ' + $('meta[name="api_token"]').attr('content')
                                },
                                success: function (ticketResponse) {
                                    var replies = ticketResponse.data.replies;
                                    if (replies && replies.length > 0) {
                                        uploadAttachments(replies[0].uuid, files, function () {
                                            window.location.href = response.data.redirect;
                                        });
                                    } else {
                                        window.location.href = response.data.redirect;
                                    }
                                },
                                error: function () {
                                    window.location.href = response.data.redirect;
                                }
                            });
                        } else {
                            window.location.href = response.data.redirect;
                        }
                    }
                });
            });
        });
    </script>
@endsection
