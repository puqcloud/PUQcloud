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
                                                <i class="fas fa-server icon-gradient bg-primary"></i>
                                            </span>
                        <span class="d-inline-block">{{__('main.Edit DNS Server')}}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav class="" aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a>
                                        <i aria-hidden="true" class="fa fa-home"></i>
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('main.Dashboard') }}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    <a href="{{route('admin.web.dns_servers')}}">{{__('main.DNS Servers')}}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{ request()->route('uuid') }}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <button id="save_and_test" type="button"
                        class="mb-2 me-2 btn btn-outline-secondary btn-icon btn-outline-2x">
                    <i class="fa-solid fa-floppy-disk me-1"></i>
                    <i class="fa-solid fa-plug me-1"></i>
                    {{ __('main.Save and Test') }}
                </button>

                <a href="{{ route('admin.web.dns_server.import',$uuid)}} "
                   class="mb-2 me-2 btn btn-outline-primary btn-icon btn-outline-2x">
                    <i class="fa-solid fa-file-import me-1"></i>
                    {{ __('main.Import') }}
                </a>

                <button id="save" type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                    <i class="fa fa-save"></i> {{__('main.Save')}}
                </button>
            </div>
        </div>
    </div>
    <div id="mainCard" class="main-card mb-3 card">
        <div class="card-body">
            <form id="dns_server" class="col-md-10 mx-auto" novalidate="novalidate">
                <div class="row">
                    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
                        <label class="form-label" for="name">{{__('main.Name')}}</label>
                        <div>
                            <input type="text" class="form-control input-mask-trigger" id="name" name="name"
                                   placeholder="{{__('main.Name')}}">
                        </div>
                    </div>
                    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
                        <label class="form-label" for="description">{{__('main.Description')}}</label>
                        <div>
                            <textarea name="description" id="description" class="form-control" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <hr>
                <div id="module_html"></div>
            </form>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function loadFormData() {
                blockUI('dns_server');
                const $form = $('#dns_server');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.dns_server.get',$uuid)}}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $.each(response.data, function (key, value) {
                            const $element = $form.find(`[name="${key}"]`);
                            if ($element.length) {
                                $element.val(value);
                            }
                        });
                        $('#module_html').html(response.data.module_html);
                        unblockUI('dns_server');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#dns_server");
                event.preventDefault();

                const formData = serializeForm($form);

                PUQajax('{{route('admin.api.dns_server.put',$uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            $("#save_and_test").on("click", function (event) {
                event.preventDefault();
                const $form = $("#dns_server");
                const $button = $(this);
                const formData = serializeForm($form);

                PUQajax('{{route('admin.api.dns_server.put',$uuid)}}', formData, 5000, $button, 'PUT', $form)
                    .then(function (saveResponse) {
                        return PUQajax('{{route('admin.api.dns_server.test_connection.get',$uuid)}}', null, 5000, $button, 'GET', null);
                    })
                    .then(function (testResponse) {
                        displayConnectionResult(testResponse);
                    })
                    .catch(function (error) {
                        showModalError(error);
                    });
            });

            function displayConnectionResult(response) {
                if (response.status === 'error') {
                    showModalError(response.errors || ['Unknown error']);
                } else {
                    showModalSuccess(response.data);
                }
            }

            function showModalError(errors) {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');

                $('#universalModal #modalSaveButton').remove();
                $('#universalModal .modal-dialog').css({
                    'min-width': '50%',
                    'width': '50%'
                });

                $modalTitle.text('Connection Test Error');

                let errorHtml = `
    <div style="text-align:center; margin-bottom: 10px;">
        <i class="fas fa-times fa-5x text-danger fa-beat"></i>
    </div>
    <div class="mt-3">
        <ul style="color:#f44336; font-weight:bold;">
    `;

                errors.forEach(err => {
                    errorHtml += `<li>${err}</li>`;
                });
                errorHtml += '</ul></div>';

                $modalBody.html(errorHtml);
                $('#universalModal').modal('show');
            }

            function showModalSuccess(data) {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');

                $('#universalModal #modalSaveButton').remove();
                $('#universalModal .modal-dialog').addClass('modal-lg');


                $modalTitle.text(translate('Connection Test Result'));

                let successHtml = '';
                if (typeof data === 'string' && data.trim() !== '') {
                    successHtml = data;
                } else {
                    successHtml = `
        <div style="text-align:center; margin-bottom: 10px;">
            <i class="fas fa-check fa-5x text-success fa-beat"></i>
        </div>
        <div class="mt-2" style="text-align:center; font-weight:bold;">
            ${translate('Connection successful')}
        </div>
        `;
                }

                $modalBody.html(successHtml);
                $('#universalModal').modal('show');
            }


            loadFormData();
        });
    </script>
@endsection
