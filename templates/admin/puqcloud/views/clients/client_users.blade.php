@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('clients-edit'))
        <button id="associateUser" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info">
            <i class="fa fa-plus"></i>
            {{__('main.Associate User')}}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.clients.client_header')

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="users" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.2FA')}}</th>
                    <th>{{__('main.Contacts')}}</th>
                    <th>{{__('main.Created')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.2FA')}}</th>
                    <th>{{__('main.Contacts')}}</th>
                    <th>{{__('main.Created')}}</th>
                    <th></th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            var $tableId = $('#users');
            var ajaxUrl = '{{ route('admin.api.client.users.get',$uuid) }}';
            var columnsConfig = [
                {
                    data: 'email',
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left me-3">
                            <div class="avatar-icon-wrapper">
                                <div class="badge badge-bottom"></div>
                                <div class="avatar-icon rounded">
                                    <img src="${row.urls.gravatar}" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="widget-content-left">
                            <div class="widget-heading">${row.firstname} ${row.lastname}</div>
                            <div style="margin-left: 10px;" class="badge bg-${getClientStatusLabelClass(row.status)}">${row.status}</div>
                        ${row.owner
                            ? `<div class="d-inline-block badge bg-info ms-2">${translate('Owner')}</div>`
                            : ''}
                        </div>
                    </div>
                </div>`;
                    }
                },
                {
                    data: 'two_factor',
                    render: function (data, type, row) {
                        return renderStatus(!data);
                    }
                },
                {
                    data: 'phone_number',
                    render: function (data, type, row) {
                        var phoneInfo = '';
                        if (row.phone_number) {
                            phoneInfo = `<div class="widget-heading">${renderStatus(!row.phone_verified)} ${row.phone_number}</div>`;
                        }

                        return `<div class="widget-content p-0">
            <div class="widget-content-wrapper">
                <div class="widget-content-left me-3">
                </div>
                <div class="widget-content-left">
                    <div class="widget-heading">${renderStatus(!row.email_verified)} ${row.email}</div>
                    ${phoneInfo}
                </div>
            </div>
        </div>`;
                    }
                },
                {
                    data: 'created_at',
                    render: function (data, type, row) {
                        return formatDateWithoutTimezone(data);
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.put) {
                            btn = btn + renderEditButton(row.urls.put);
                        }
                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                },
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                order: [[1, 'desc']]
            });

            function DataTableAddData() {
                return {};
            }

            $("#associateUser").on("click", function (event) {
                $('#universalModal .modal-dialog').removeAttr('style');
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text(translate('Associate User'));
                const formHtml = `
    <form id="associateUserForm" class="mx-auto">

    <div class="row">

        <div class="col-md-12">
            <div class="position-relative mb-3">
                <label for="user_uuid" class="form-label">${translate('User')}</label>
                <select name="user_uuid" id="user_uuid"
                        class="form-select mb-2 form-control"></select>
            </div>
        </div>

        <div class="col-md-12">
            <div class="position-relative mb-3">
                <label for="permissions" class="form-label">${translate('Permissions')}</label>
                <select name="permissions" id="permissions"
                        multiple class="form-select mb-2 form-control"></select>
            </div>
        </div>

    </div>
</form>
    `;

                $modalBody.html(formHtml);

                var $element_language = $("#user_uuid");
                initializeSelect2($element_language, '{{route('admin.api.users.select.get')}}', null, 'GET', 1000, {
                        dropdownParent: $('#universalModal')
                    });

                var $element_permissions = $("#permissions");
                initializeSelect2($element_permissions, '{{route('admin.api.user.permissions.select.get')}}', null, 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                    $('#universalModal').modal('show');
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#associateUserForm').length) {
                    var $form = $('#associateUserForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.client.user.associate.post',$uuid)}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

                if ($('#editForm').length) {
                    var $form = $('#editForm');
                    var formData = serializeForm($form);
                    var modelUrl = $(this).data('model-url');

                    PUQajax(modelUrl, formData, 500, $(this), 'PUT', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

            });

            $dataTable.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();

                $('#universalModal .modal-dialog').removeAttr('style');
                var modelUrl = $(this).data('model-url');
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                var $modalSaveButton = $('#modalSaveButton');
                $modalSaveButton.data('modelUrl', modelUrl);
                $modalTitle.text(translate('Edit'));
                $('#universalModal .modal-dialog').css({
                    'max-width': '90%',
                    'width': '90%'
                });
                const formHtml = `
    <form id="editForm" class="mx-auto">

    <div class="row">

        <div class="col-12 col-md-6 col-lg-4 col-xl-2">
            <div class="position-relative mb-3">
                <label for="email" class="form-label">${translate('Email')}</label>
                <input name="email" id="email" type="text" class="form-control">
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4 col-xl-2">
            <div class="position-relative mb-3">
                <label for="firstname" class="form-label">${translate('Firstname')}</label>
                <input name="firstname" id="firstname"
                       type="text" class="form-control">
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4 col-xl-2">
            <div class="position-relative mb-3">
                <label for="lastname" class="form-label">${translate('Lastname')}</label>
                <input name="lastname" id="lastname"
                       type="text" class="form-control">
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4 col-xl-2">
            <div class="position-relative mb-3">
                <label class="form-label" for="phone_number">${translate('Phone Number')}</label>
                <div>
                    <input type="text" class="form-control" id="phone_number" name="phone_number" style="width: 100%;">
                    <input id="country_code" type="hidden" name="country_code" value="+1">
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4 col-xl-2">
            <div class="position-relative mb-3">
                <label for="language" class="form-label">${translate('Language')}</label>
                <select name="language" id="language"
                        class="form-select mb-2 form-control"></select>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4 col-xl-2">
            <div class="position-relative mb-3">
                <label for="owner" class="form-label">${translate('Owner')}</label>
                <div>
                <input type="checkbox" id="owner" name="owner"
                    data-toggle="toggle" data-on="${translate('On')}" data-off="${translate('Off')}"
                    data-onstyle="success" data-offstyle="danger">
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <label class="form-label" for="admin_notes">${translate('Admin Notes')}</label>
            <div>
                <textarea name="admin_notes" id="admin_notes" class="form-control" rows="5"></textarea>
            </div>
        </div>

    </div>
</form>
    `;

                $modalBody.html(formHtml);

                const $input = $("#phone_number");
                const iti = window.intlTelInput($input[0], {
                    separateDialCode: true,
                    initialCountry: navigator.language.split('-')[1] || 'us',
                });

                $input.on('countrychange', function () {
                    var $countryCodeInput = $("#country_code");
                    var dialCode = iti.getSelectedCountryData().dialCode;
                    $countryCodeInput.val('+' + dialCode);
                });

                PUQajax(modelUrl, {}, 50, $(this), 'GET')
                    .then(function (response) {
                        $('#email').val(response.data.email);
                        $('#firstname').val(response.data.firstname);
                        $('#lastname').val(response.data.lastname);

                        if (response.data.phone_number !== null) {
                            iti.setNumber(response.data.phone_number);
                        }

                        initializeSelect2($('#language'), '{{route('admin.api.languages.select.get')}}', response.data.language_data, 'GET', 1000, {
                            dropdownParent: $('#universalModal')
                        });

                        $('#admin_notes').val(response.data.admin_notes);

                        var permissionsHtml = `
        <div class="card">
            <div class="card-header">
                ${translate('Permissions')}
            </div>
            <div class="card-body">
                <div class="row">
        `;
                        var systemPermissions = response.data.permissions_data;
                        var userPermissions = response.data.pivot.permissions ? response.data.pivot.permissions : {};
                        var isOwner = response.data.pivot.owner === 1;

                        systemPermissions.forEach(function (system_permission) {
                            var checked = isOwner || userPermissions.includes(system_permission.key) ? 'checked' : '';
                            var disabled = isOwner ? 'disabled' : '';

                            permissionsHtml += `
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="widget-content mb-0 card-shadow-primary border-primary">
                        <div class="widget-content-wrapper">
                            <div class="widget-content-left me-0"></div>
                            <div class="widget-content-left">
                                <div class="widget-heading">${system_permission.name}</div>
                                <div class="widget-subheading">
                                    ${system_permission.description ? system_permission.description : '***'}
                                </div>
                            </div>
                            <div class="widget-content-right">
                                <div class="widget-numbers text-primary">
                                    <span class="count-up-wrapper">
                                        <input type="checkbox" id="checkbox_${system_permission.key}" name="permissions[${system_permission.key}]"
                                               data-toggle="toggle" data-on="${translate('On')}" data-off="${translate('Off')}"
                                               data-onstyle="success" data-offstyle="danger" ${checked} ${disabled}>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
                        });

                        permissionsHtml += '</div></div></div>';

                        $('#admin_notes').closest('.row').after(permissionsHtml);

                        $('#owner').prop('checked', !response.data.pivot.owner).trigger('click');

                        if(response.data.pivot.owner){
                            $('#owner').prop('disabled', true);
                        }

                        $('[data-toggle="toggle"]').bootstrapToggle({
                            width: '70'
                        });

                        $('#universalModal').modal('show');

                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });

            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm(translate('Are you sure you want to delete this record?'))) {
                    PUQajax(modelUrl, null, 3000, null, 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

        });
    </script>
@endsection

