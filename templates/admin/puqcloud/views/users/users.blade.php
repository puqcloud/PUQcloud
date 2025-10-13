@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
    <style>
        #phone_number {
            width: 100% !important;
        }
        .iti {
            width: 100%;
        }
    </style>
@endsection

@section('content')
    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="fa fa-users icon-gradient bg-primary"></i>
                </div>
                <div>
                    {{__('main.View/Search Users')}}
                    <div class="page-title-subheading"></div>
                </div>
            </div>
            <div class="page-title-actions">
                @if($admin->hasPermission('users-create'))
                    <button type="button" id="create" class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                        <i class="fa fa-plus"></i>
                        {{__('main.Create')}}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="users" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.2FA')}}</th>
                    <th>{{__('main.Contacts')}}</th>
                    <th>{{__('main.Clients')}}</th>
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
                    <th>{{__('main.Clients')}}</th>
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
            var ajaxUrl = '{{ route('admin.api.users.get') }}';
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
                    data: 'clients',
                    render: function (data, type, row) {

                        var button = '';
                        if(data>0){
                            button = renderViewButton(row.urls.get_clients)
                        }

                        return `
                        <button class="mb-2 me-2 btn btn-light">${data}</button>${button}`;
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
                order: [[2, 'desc']]
            });

            function DataTableAddData() {
                return {};
            }

            $('#create').on('click', function () {
                $('#universalModal .modal-dialog').removeAttr('style');
                $('#universalModal #modalSaveButton').show();

                var modelUrl = $(this).data('model-url');
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                var $modalSaveButton = $('#modalSaveButton');
                $modalSaveButton.data('modelUrl', modelUrl);
                $modalTitle.text(translate('Create'));

                const formHtml = `
    <form id="createForm" class="mx-auto">

    <div class="row">

        <div class="col-12 col-md-6">
            <div class="position-relative mb-3">
                <label for="email" class="form-label">${translate('Email')}</label>
                <input name="email" id="email" type="text" class="form-control">
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="position-relative mb-3">
                <label for="password" class="form-label">${translate('Password')}</label>
                <input name="password" id="password" type="text" class="form-control">
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="position-relative mb-3">
                <label for="two_factor" class="form-label">${translate('2FA')}</label>
                <div>
                <input type="checkbox" id="two_factor" name="two_factor"
                    data-toggle="toggle" data-on="${translate('On')}" data-off="${translate('Off')}"
                    data-onstyle="success" data-offstyle="danger">
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="firstname" class="form-label">${translate('Firstname')}</label>
                <input name="firstname" id="firstname"
                       type="text" class="form-control">
            </div>
        </div>

        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="lastname" class="form-label">${translate('Lastname')}</label>
                <input name="lastname" id="lastname"
                       type="text" class="form-control">
            </div>
        </div>

        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label class="form-label" for="phone_number">${translate('Phone Number')}</label>
                <div>
                    <input type="text" class="form-control" id="phone_number" name="phone_number" style="width: 100%;">
                    <input id="country_code" type="hidden" name="country_code" value="+1">
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="language" class="form-label">${translate('Language')}</label>
                <select name="language" id="language"
                        class="form-select mb-2 form-control"></select>
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
                $('[data-toggle="toggle"]').bootstrapToggle({
                    width: '70'
                });
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


                var $element_language = $("#language");
                initializeSelect2($element_language, '{{route('admin.api.languages.select.get')}}', null, 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                $('#universalModal').modal('show');

            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.user.post')}}', formData, 500, $(this), 'POST', $form)
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
                $('#universalModal #modalSaveButton').show();
                var modelUrl = $(this).data('model-url');
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                var $modalSaveButton = $('#modalSaveButton');
                $modalSaveButton.data('modelUrl', modelUrl);

                $modalTitle.text(translate('Edit'));

                $('#universalModal .modal-dialog').removeAttr('style');

                const formHtml = `
    <form id="editForm" class="mx-auto">

    <div class="row">

        <div class="col-12 col-md-6">
            <div class="position-relative mb-3">
                <label for="email" class="form-label">${translate('Email')}</label>
                <input name="email" id="email" type="text" class="form-control">
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="position-relative mb-3">
                <label for="password" class="form-label">${translate('Password')}</label>
                <input name="password" id="password" type="text" class="form-control">
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="position-relative mb-3">
                <label for="email_verified" class="form-label">${translate('Email Verified')}</label>
                <div>
                <input type="checkbox" id="email_verified" name="email_verified"
                    data-toggle="toggle" data-on="${translate('On')}" data-off="${translate('Off')}"
                    data-onstyle="success" data-offstyle="danger">
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="position-relative mb-3">
                <label for="phone_verified" class="form-label">${translate('Phone Verified')}</label>
                <div>
                <input type="checkbox" id="phone_verified" name="phone_verified"
                    data-toggle="toggle" data-on="${translate('On')}" data-off="${translate('Off')}"
                    data-onstyle="success" data-offstyle="danger">
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="position-relative mb-3">
                <label for="two_factor" class="form-label">${translate('2FA')}</label>
                <div>
                <input type="checkbox" id="two_factor" name="two_factor"
                    data-toggle="toggle" data-on="${translate('On')}" data-off="${translate('Off')}"
                    data-onstyle="success" data-offstyle="danger">
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="firstname" class="form-label">${translate('Firstname')}</label>
                <input name="firstname" id="firstname"
                       type="text" class="form-control">
            </div>
        </div>

        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="lastname" class="form-label">${translate('Lastname')}</label>
                <input name="lastname" id="lastname"
                       type="text" class="form-control">
            </div>
        </div>

        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label class="form-label" for="phone_number">${translate('Phone Number')}</label>
                <div>
                    <input type="text" class="form-control" id="phone_number" name="phone_number" style="width: 100%;">
                    <input id="country_code" type="hidden" name="country_code" value="+1">
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="language" class="form-label">${translate('Language')}</label>
                <select name="language" id="language"
                        class="form-select mb-2 form-control"></select>
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

                        $('[data-toggle="toggle"]').bootstrapToggle({
                            width: '70'
                        });

                        if (response.data.two_factor) {
                            $('#two_factor').bootstrapToggle('on');
                        } else {
                            $('#two_factor').bootstrapToggle('off');
                        }

                        if (response.data.email_verified) {
                            $('#email_verified').bootstrapToggle('on');
                        } else {
                            $('#email_verified').bootstrapToggle('off');
                        }

                        if (response.data.phone_verified) {
                            $('#phone_verified').bootstrapToggle('on');
                        } else {
                            $('#phone_verified').bootstrapToggle('off');
                        }

                        if (response.data.phone_number !== null) {
                            iti.setNumber(response.data.phone_number);
                        }

                        initializeSelect2($('#language'), '{{route('admin.api.languages.select.get')}}', response.data.language_data, 'GET', 1000, {
                            dropdownParent: $('#universalModal')
                        });

                        $('#admin_notes').val(response.data.admin_notes);
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
                    PUQajax(modelUrl, null, 3000, $(this), 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $dataTable.on('click', 'button.view-btn', function (e) {
                e.preventDefault();
                const modelUrl = $(this).data('model-url');
                PUQajax(modelUrl, null, 500, $(this), 'GET', null)
                    .then(function (response) {
                        displayModalData(response.data);
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            });

            function displayModalData(data) {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $('#universalModal #modalSaveButton').hide();
                $('#universalModal .modal-dialog').css({
                    'max-width': '90%',
                    'width': '90%'
                });

                $modalTitle.text(translate('User Clients'));

                const userDetails = `
        <div class="container mb-4">
            <div class="row">
                <div class="col-12"><strong>${translate('UUID')}:</strong> ${data.uuid}</div>
                <div class="col-12"><strong>${translate('Name')}:</strong> ${data.firstname} ${data.lastname}</div>
                <div class="col-12"><strong>${translate('Email')}:</strong> ${data.email}</div>
                <div class="col-12"><strong>${translate('Phone Number')}:</strong> ${data.phone_number || translate('Not Available')}</div>
            </div>
        </div>
    `;

                const clientsHeader = `
        <div class="container mb-3">
            <div class="row fw-bold text-uppercase">
                <div class="col-3">${translate('Client Name')}</div>
                <div class="col-3">${translate('Company Name')}</div>
                <div class="col-2">${translate('Tax ID')}</div>
                <div class="col-4">${translate('Status')}</div>
            </div>
        </div>
    `;

                let clientsList = data.clients && data.clients.length > 0
                    ? data.clients.map(client => `
            <div class="container py-2 border-bottom">
                <div class="row">
                    <div class="col-3">${client.firstname} ${client.lastname}</div>
                    <div class="col-3">${client.company_name || translate('Not Available')}</div>
                    <div class="col-2">${client.tax_id || translate('Not Available')}</div>
                    <div class="col-4">
                        <div class="d-inline-block badge bg-${getClientStatusLabelClass(client.status)}">
                            ${client.status}
                        </div>
                        ${client.pivot.owner
                        ? `<div class="d-inline-block badge bg-info ms-2">${translate('Owner')}</div>`
                        : ''}
                        <a class="d-inline-block badge ms-1 btn btn-info active" href="${client.web_url}" target="_blank">
                            <i class="fas fa-wrench btn-icon-wrapper"></i></a>
                    </div>
                </div>
            </div>
        `).join('')
                    : `<div class="text-center py-3">${translate('No associated companies found.')}</div>`;

                const formattedData = `
        <div>
            <h5>${translate('User Information')}</h5>
            ${userDetails}
            <h5 class="mt-4">${translate('Associated Clients')}</h5>
            ${clientsHeader}
            ${clientsList}
        </div>
    `;

                $modalBody.html(formattedData);

                $('#universalModal').modal('show');
            }

        });
    </script>

@endsection
