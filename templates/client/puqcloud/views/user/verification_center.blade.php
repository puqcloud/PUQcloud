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
                    <i class="fas fa-address-card icon-gradient bg-tempting-azure"></i>
                </div>
                <div>
                    {{__('main.Verification Center')}}
                    <div class="page-title-subheading">
                        {{__('main.Manage your verification methods, emails, phones and 2fa')}}
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <button type="button"
                        id="addTOTP"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                    <i class="fa fa-plus"></i>
                    {{__('main.Add Authenticator App (TOTP)')}}
                </button>
            </div>
        </div>
    </div>
    <div class="container px-0">
        <div class="main-card mb-3 card">
            <div class="card-body">
                <table style="width: 100%;" id="verifications" class="table table-hover table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>{{__('main.Type')}}</th>
                        <th>{{__('main.Value')}}</th>
                        <th>{{__('main.Verified')}}</th>
                        <th>{{__('main.Default')}}</th>
                        <th>{{__('main.Last Used At')}}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th>{{__('main.Type')}}</th>
                        <th>{{__('main.Value')}}</th>
                        <th>{{__('main.Verified')}}</th>
                        <th>{{__('main.Default')}}</th>
                        <th>{{__('main.Last Used At')}}</th>
                        <th></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            var $table = $('#verifications');
            var ajaxUrl = '{{ route('client.api.user.verifications.get') }}';
            var columnsConfig = [
                {
                    name: "type",
                    data: "type",
                    render: function (data, type, row) {
                        let icon = '';
                        let label = '';

                        if (data === 'email') {
                            icon = '<i class="fa fa-envelope text-primary fa-2x"></i>';
                            label = translate('Email');
                        } else if (data === 'phone') {
                            icon = '<i class="fa fa-phone text-success fa-2x"></i>';
                            label = translate('Phone');
                        } else if (data === 'totp') {
                            icon = '<i class="fa fa-shield-alt text-warning fa-2x"></i>';
                            label = translate('Authenticator App (TOTP)');
                        } else {
                            icon = '<i class="fa fa-question-circle text-muted fa-2x"></i>';
                            label = data;
                        }

                        return `
            <div class="d-flex align-items-center">
                <div class="me-2">${icon}</div>
                <div class="fw-bold fs-5">${label}</div>
            </div>
        `;
                    }
                },
                {name: "value", data: "value"},
                {
                    data: "verified",
                    render: function (data) {
                        return renderStatus(!data);
                    }
                },
                {
                    data: "default",
                    render: function (data, type, row) {
                        return renderSetDefaultButton(row.urls.get, data);
                    }
                },
                {name: "last_used_at", data: "last_used_at"},
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.verify) {
                            btn += renderVerifyButton(row.urls.verify);
                        }
                        if (row.urls.delete) {
                            btn += renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable($table, ajaxUrl, columnsConfig, undefined, {
                paging: false,
                ordering: false,
                responsive: true,
                autoWidth: false,
            });

            $dataTable.on('click', 'button.verify-btn', function (e) {
                e.preventDefault();
                openConfirmActionModal({
                    fetchUrl: $(this).data('model-url'),
                    fetchMethod: 'GET',
                    actionUrl: '{{route('client.api.user.verification.verify.post')}}',
                    actionMethod: 'POST',
                    actionText: translate('Verification'),
                    actionType: 'info',
                    confirmButtonText: '<i class="fa fa-check"> </i> ' + translate('OK'),
                    titleText: translate('Verify Verification Method'),
                    onSuccess: function (res) {
                        $dataTable.ajax.reload(null, false);
                    },
                    onError: function (res) {
                        $dataTable.ajax.reload(null, false);
                    },
                    button: $(this)
                });
            });

            $dataTable.on('click', 'button.set-default-btn', function (e) {
                e.preventDefault();
                openConfirmActionModal({
                    fetchUrl: $(this).data('model-url'),
                    fetchMethod: 'GET',
                    actionUrl: '{{route('client.api.user.verification.default.post')}}',
                    actionMethod: 'POST',
                    actionText: translate('Set this verification method as default'),
                    actionType: 'warning',
                    confirmButtonText: '<i class="fa fa-check"> </i> ' + translate('OK'),
                    titleText: translate('Set default Verification Method'),
                    onSuccess: function (res) {
                        $dataTable.ajax.reload(null, false);
                    },
                    onError: function (res) {
                        $dataTable.ajax.reload(null, false);
                    },
                    button: $(this)
                });
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                openConfirmActionModal({
                    fetchUrl: '{{route('client.api.verification.get')}}',
                    fetchMethod: 'GET',
                    actionUrl: $(this).data('model-url'),
                    actionMethod: 'DELETE',
                    actionText: translate('Are you sure you want to delete this verification method?'),
                    actionType: 'danger',
                    confirmButtonText: '<i class="fa fa-trash"> </i> ' + translate('Delete'),
                    titleText: translate('Delete Verification Method'),
                    onSuccess: function (res) {
                        $dataTable.ajax.reload(null, false);
                    },
                    onError: function (res) {
                        $dataTable.ajax.reload(null, false);
                    },
                    button: $(this)
                });
            });


            $('#addTOTP').on('click', function (e) {
                e.preventDefault();

                var getUrl = '{{route('client.api.user.verification.totp.add.get')}}';
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                var $modalConfirmButton = $('#modalConfirmButton');
                $modalConfirmButton.data('modelUrl', getUrl);

                $modalConfirmButton.removeClass(function (index, className) {
                    return (className.match(/(^|\s)btn-outline-\S+/g) || []).join(' ');
                });
                $modalConfirmButton.addClass('btn-outline-success');
                $modalConfirmButton.html('<i class="fa fa-save"> </i> ' + translate('Save'));

                $modalTitle.text(translate('Add Time-based One-Time Password'));

                PUQajax(getUrl, {}, 50, $(this), 'GET')
                    .then(function (response) {
                        const code = response.data.code;
                        const qrDataUri = response.data.qrcode_uri;

                        const formHtml = `
            <form id="addTotpForm">
                <h3 style="margin-top:0;">${translate('Connect your app')}</h3>
                <p>${translate('Using an authenticator app like')}</p>
                    <a href="https://itunes.apple.com/gb/app/google-authenticator/id388497605" target="_blank">Google Authenticator</a>/
                    <a href="https://itunes.apple.com/gb/app/duo-mobile/id422663827" target="_blank">Duo</a>
                    <p>${translate('Scan the QR code below')}</p>
                    <p>${translate('Having trouble scanning the code? Enter the code manually')}: <strong>${code}</strong></p>
                <div class="text-center mb-3">
                    <img src="${qrDataUri}" alt="QR Code" width="200" height="200" class="img-fluid" />
                </div>
                <p>${translate('Enter the 6-digit code that the application generates to verify and complete setup')}</p>

                <div class="row mb-3">
                    <div class="col-sm-8 mx-auto">
                        <input type="text" name="code" maxlength="6" style="font-size:18px;" class="form-control form-control-lg text-center" placeholder="${translate('Enter authentication code')}" autofocus>
                    </div>
                </div>

        <div class="mb-3">
            <label for="device_name" class="form-label">${translate('Device Name')}</label>
            <div>
                <input type="text" class="form-control" id="device_name" name="device_name" placeholder="${translate('Device Name')}">
            </div>
        </div>
                <br>
            </form>
            `;

                        $modalBody.html(formHtml);
                        $('#universalModal').modal('show');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            });

            $('#modalConfirmButton').on('click', function (event) {
                event.preventDefault();

                if ($('#addTotpForm').length) {
                    var $form = $('#addTotpForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('client.api.user.verification.totp.add.post')}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

            });

        });

    </script>
@endsection
