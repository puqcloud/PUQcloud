@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')
    <div class="app-page-title app-page-title-simple">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div>
                    <div class="page-title-head center-elem">
                    <span class="d-inline-block pe-2">
                        <i class="fas fa-building icon-gradient bg-primary"></i>
                    </span>
                        <span class="d-inline-block">{{ __('main.Edit Ticket Department') }}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('main.Dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ route('admin.web.ticket_departments') }}">{{ __('main.Ticket Departments') }}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{ $uuid }}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <button id="save" type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                    <i class="fa fa-save"></i> {{__('main.Save')}}
                </button>
            </div>
        </div>
    </div>

    <form id="editTicketDepartmentForm" class="mx-auto" novalidate="novalidate">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-4 col-xxl-4">
                        <div class="mb-3">
                            <label class="form-label" for="name">{{ __('main.Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name" value="" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="description">{{ __('main.Description') }}</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">{{ __('main.Email') }}</label>
                            <input type="email" class="form-control" id="email" name="email" value="">
                            <small class="form-text text-muted">{{ __('main.Used as Reply-To in ticket email notifications. When client clicks Reply, the email will be sent to this address. Also used for Mail Import.') }}</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="hidden" name="hidden" value="1">
                                <label class="form-check-label" for="hidden">{{ __('main.Hidden') }}</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="feedback_on_close" name="feedback_on_close" value="1">
                                <label class="form-check-label" for="feedback_on_close">{{ __('main.Feedback on Close') }}</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="allow_client_reopen" name="allow_client_reopen" value="1">
                                <label class="form-check-label" for="allow_client_reopen">{{ __('main.Allow Client Reopen') }}</label>
                            </div>
                            <small class="form-text text-muted">{{ __('main.Allow clients to reopen closed tickets in this department') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="auto_close_hours">{{ __('main.Auto Close Hours') }}</label>
                            <input type="number" min="0" step="1" class="form-control" id="auto_close_hours" name="auto_close_hours" value="0">
                            <small class="form-text text-muted">{{ __('main.Set to 0 to disable auto close') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="sla_response_hours">{{ __('main.SLA Response Hours') }}</label>
                            <input type="number" min="0" step="1" class="form-control" id="sla_response_hours" name="sla_response_hours" value="">
                            <small class="form-text text-muted">{{ __('main.Leave empty to disable SLA') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="allowed_file_extensions">{{ __('main.Allowed File Extensions') }}</label>
                            <input type="text" class="form-control" id="allowed_file_extensions" name="allowed_file_extensions" value=""
                                   placeholder="jpg,png,pdf,doc,docx">
                            <small class="form-text text-muted">{{ __('main.Comma-separated. Leave empty to use global settings.') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="max_file_size_mb">{{ __('main.Max File Size (MB)') }}</label>
                            <input type="number" min="0" step="1" class="form-control" id="max_file_size_mb" name="max_file_size_mb" value="">
                            <small class="form-text text-muted">{{ __('main.Leave empty to use global settings.') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="max_files_count">{{ __('main.Max Files Count') }}</label>
                            <input type="number" min="0" step="1" class="form-control" id="max_files_count" name="max_files_count" value="">
                            <small class="form-text text-muted">{{ __('main.Max number of files per client reply. Leave empty for unlimited.') }}</small>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-4 col-xxl-4">
                        <div class="mb-3">
                            <label for="admin_uuids" class="form-label">{{ __('main.Admin Operators') }}</label>
                            <select multiple name="admin_uuids" id="admin_uuids"
                                    class="form-select mb-2 form-control"></select>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-4 col-xxl-4">
                        <h5 class="mb-3">{{ __('main.Mail Import') }}</h5>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="mail_import_enabled" name="mail_import_enabled" value="1">
                                <label class="form-check-label" for="mail_import_enabled">{{ __('main.Enabled') }}</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="mail_import_type">{{ __('main.Type') }}</label>
                            <select class="form-select" id="mail_import_type" name="mail_import_type">
                                <option value="imap">IMAP</option>
                                <option value="pop3">POP3</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="mail_import_host">{{ __('main.Host') }}</label>
                            <input type="text" class="form-control" id="mail_import_host" name="mail_import_host" value="">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="mail_import_port">{{ __('main.Port') }}</label>
                            <input type="number" min="1" max="65535" class="form-control" id="mail_import_port" name="mail_import_port" value="">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="mail_import_username">{{ __('main.Username') }}</label>
                            <input type="text" class="form-control" id="mail_import_username" name="mail_import_username" value="">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="mail_import_password">{{ __('main.Password') }}</label>
                            <input type="password" class="form-control" id="mail_import_password" name="mail_import_password" value="">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="mail_import_encryption">{{ __('main.Encryption') }}</label>
                            <select class="form-select" id="mail_import_encryption" name="mail_import_encryption">
                                <option value="ssl">SSL</option>
                                <option value="tls">TLS</option>
                                <option value="none">{{ __('main.None') }}</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="mail_import_delete_after" name="mail_import_delete_after" value="1">
                                <label class="form-check-label" for="mail_import_delete_after">{{ __('main.Delete After Import') }}</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="mail_import_replies_only" name="mail_import_replies_only" value="1">
                                <label class="form-check-label" for="mail_import_replies_only">{{ __('main.Replies Only') }}</label>
                            </div>
                            <small class="form-text text-muted">{{ __('main.Only import replies to existing tickets') }}</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="mail_import_clients_only" name="mail_import_clients_only" value="1">
                                <label class="form-check-label" for="mail_import_clients_only">{{ __('main.Clients Only (Import)') }}</label>
                            </div>
                            <small class="form-text text-muted">{{ __('main.Only import emails from known clients') }}</small>
                        </div>

                        <button type="button" id="testMailConnection"
                                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info">
                            <i class="fas fa-plug"></i> {{ __('main.Test Connection') }}
                        </button>
                        <div id="testMailResult" class="mt-2" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function loadFormData() {
                blockUI('editTicketDepartmentForm');
                const $form = $('#editTicketDepartmentForm');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{ route('admin.api.ticket_department.get', $uuid) }}', {}, 5000, null, 'GET')
                    .then(function (response) {
                        if (response.data) {
                            const data = response.data;

                            $('#name').val(data.name || '');
                            $('#description').val(data.description || '');
                            $('#email').val(data.email || '');
                            $('#auto_close_hours').val(data.auto_close_hours || 0);

                            $('#hidden').prop('checked', !!data.hidden);
                            $('#feedback_on_close').prop('checked', !!data.feedback_on_close);
                            $('#allow_client_reopen').prop('checked', data.allow_client_reopen !== false);

                            $('#sla_response_hours').val(data.sla_response_hours || '');
                            $('#allowed_file_extensions').val(data.allowed_file_extensions || '');
                            $('#max_file_size_mb').val(data.max_file_size_mb || '');
                            $('#max_files_count').val(data.max_files_count || '');

                            $('#mail_import_enabled').prop('checked', !!data.mail_import_enabled);
                            $('#mail_import_type').val(data.mail_import_type || 'imap');
                            $('#mail_import_host').val(data.mail_import_host || '');
                            $('#mail_import_port').val(data.mail_import_port || '');
                            $('#mail_import_username').val(data.mail_import_username || '');
                            $('#mail_import_password').val('');
                            $('#mail_import_password').attr('placeholder', data.mail_import_password_set ? '••••••••' : '');
                            $('#mail_import_encryption').val(data.mail_import_encryption || 'ssl');
                            $('#mail_import_delete_after').prop('checked', !!data.mail_import_delete_after);
                            $('#mail_import_replies_only').prop('checked', !!data.mail_import_replies_only);
                            $('#mail_import_clients_only').prop('checked', !!data.mail_import_clients_only);

                            var $elementAdmins = $('#admin_uuids');
                            initializeSelect2($elementAdmins, '{{route('admin.api.admins.select.get')}}', data.admins_data, 'GET', 1000, {});

                            unblockUI('editTicketDepartmentForm');
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                        unblockUI('editTicketDepartmentForm');
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#editTicketDepartmentForm");
                event.preventDefault();

                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.ticket_department.put', $uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            loadFormData();

            $('#testMailConnection').on('click', function (event) {
                event.preventDefault();
                var $btn = $(this);
                var $result = $('#testMailResult');
                $result.hide();

                PUQajax('{{route('admin.api.ticket_department.test_mail.post', $uuid)}}', {}, 15000, $btn, 'POST')
                    .then(function (response) {
                        var msg = translate('Connection successful') + '. ' + translate('Unseen messages') + ': ' + (response.data.unseen_messages || 0);
                        $result.html('<div class="alert alert-success mb-0 py-2">' + msg + '</div>').show();
                    })
                    .catch(function () {
                        $result.html('<div class="alert alert-danger mb-0 py-2">' + translate('Connection failed') + '</div>').show();
                    });
            });
        });
    </script>
@endsection


