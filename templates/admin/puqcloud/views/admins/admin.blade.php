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

    <div class="app-page-title app-page-title-simple">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div>
                    <div class="page-title-head center-elem">
                                            <span class="d-inline-block pe-2">
                                                <i class="fas fa-address-card"></i>
                                            </span>
                        <span class="d-inline-block">{{__('main.Edit Administrator')}}</span>
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
                                    <a href="{{route('admin.web.admins')}}">{{__('main.Administrators')}}</a>
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
                @if($admin->hasPermission('admins-edit'))
                    <button id="save" type="button"
                            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                        <i class="fa fa-save"></i> {{__('main.Save')}}
                    </button>
                    <button type="button" class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-primary"
                            data-bs-toggle="modal" data-bs-target="#universalModal">
                        <i class="fa fa-lock"></i> {{__('main.Change password')}}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <form id="admin" class="col-md-10 mx-auto" novalidate="novalidate">
        <div id="mainCard" class="main-card mb-3 card">
            <div class="card-body">
                <div class="row">

                    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">

                        <div class="row">

                            <div class="col-12 col-sm-6 col-md-6 col-lg mb-1">
                                <div class="mb-3">
                                    <label class="form-label" for="email">{{__('main.Email')}}</label>
                                    <div>
                                        <input type="text" class="form-control input-mask-trigger" id="email"
                                               name="email"
                                               data-inputmask="'alias': 'email'" inputmode="email"
                                               placeholder="{{__('main.Email')}}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-md-6 col-lg mb-1">
                                <label class="form-label" for="disable">{{__('main.Status')}}</label>
                                <div>
                                    <input type="checkbox" data-toggle="toggle" data-on="{{__('main.Disabled')}}"
                                           id="disable"
                                           name="disable"
                                           data-off="{{__('main.Enabled')}}" data-onstyle="danger"
                                           data-offstyle="success">
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-md-6 col-lg mb-1">
                                <div class="mb-3">
                                    <label class="form-label" for="firstname">{{__('main.Firstname')}}</label>
                                    <div>
                                        <input type="text" class="form-control" id="firstname" name="firstname"
                                               placeholder="{{__('main.Firstname')}}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-md-6 col-lg mb-1">
                                <div class="mb-3">
                                    <label class="form-label" for="lastname">{{__('main.Lastname')}}</label>
                                    <div>
                                        <input type="text" class="form-control" id="lastname" name="lastname"
                                               placeholder="{{__('main.Lastname')}}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-md-6 col-lg mb-1">
                                <div class="mb-3">
                                    <div class="position-relative mb-3">
                                        <div>
                                            <label for="language" class="form-label">{{__('main.Language')}}</label>
                                            <select name="language" id="language"
                                                    class="form-select mb-2 form-control"></select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-4 col-md-6 col-lg mb-1">
                                <div class="mb-3">
                                    <label class="form-label" for="phone_number">{{__('main.Phone Number')}}</label>
                                    <div>
                                        <input type="text" class="form-control" id="phone_number" name="phone_number" style="width: 100%;">
                                        <input id="country_code" type="hidden" name="country_code">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mb-1">
                                <div class="mb-3">
                                    <div class="position-relative mb-3">
                                        <div>
                                            <label for="groups" class="form-label">{{__('main.Groups')}}</label>
                                            <select multiple name="groups" id="groups"
                                                    class="form-select mb-2 form-control"></select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mb-1">
                                <label class="form-label" for="admin_notes">{{__('main.Notes')}}</label>
                                <div>
                                    <textarea name="admin_notes" id="admin_notes" class="form-control"
                                              rows="1"></textarea>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('main.Session IPs') }}</h5>
                            <div class="scroll-area">
                                <div class="scrollbar-container ps ps--active-y">
                                    <div id="sessionIPs"
                                         class="vertical-timeline vertical-timeline--animate vertical-timeline--one-column">
                                        <div class="ps__rail-x" style="left: 0px; bottom: 0px;">
                                            <div class="ps__thumb-x" tabindex="0" style="left: 0px; width: 0px;"></div>
                                        </div>
                                        <div class="ps__rail-y" style="top: 0px; right: 0px; height: 400px;">
                                            <div class="ps__thumb-y" tabindex="0"
                                                 style="top: 0px; height: 176px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
            const $input = $("#phone_number");
            const iti = window.intlTelInput($input[0], {
                separateDialCode: true,
                initialCountry: navigator.language.split('-')[1] || 'us',
            });

            $input.on('countrychange', function () {
                const $countryCodeInput = $("#country_code");
                const dialCode = iti.getSelectedCountryData().dialCode;
                $countryCodeInput.val('+' + dialCode);
            });

            function loadFormData() {
                blockUI('admin');
                const $form = $('#admin');

                PUQajax('{{route('admin.api.admin.get',$uuid)}}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $.each(response.data, function (key, value) {
                            const $element = $form.find(`[name="${key}"]`);
                            if ($element.length) {

                                if ($element.is(':checkbox')) {
                                    $element.prop('checked', !!value).trigger('click');
                                    return;
                                }

                                if (key === 'phone_number' && value !== null) {
                                    iti.setNumber(value);
                                    return;
                                }

                                if ($element.is('select')) {
                                    if (key === 'language') {
                                        var language_selected = response.data[key + '_data'];
                                        initializeSelect2($element[0], '{{route('admin.api.languages.select.get')}}', language_selected, 'GET', 1000, {});
                                    }

                                    if (key === 'groups') {
                                        var groups_selected = response.data[key + '_data'];
                                        initializeSelect2($element[0], '{{route('admin.api.groups.select.get')}}', groups_selected, 'GET', 1000, {});
                                    }

                                    return;
                                }

                                if ($element.is('textarea')) {
                                    if (value !== null) {
                                        $element.val(value);
                                    }
                                    $element.textareaAutoSize().trigger('autosize');
                                    return;
                                }

                                $element.val(value);
                            }

                            if (key === 'ips' && Array.isArray(value)) {
                                const $sessionIPs = $('#sessionIPs');
                                $sessionIPs.empty();

                                value.forEach(function (ipSession) {
                                    const ipHtml = `
            <div class="vertical-timeline-item vertical-timeline-element">
                <div>
                    <span class="vertical-timeline-element-icon bounce-in">
                        <i class="badge badge-dot badge-dot-xl bg-success"></i>
                    </span>
                    <div class="vertical-timeline-element-content bounce-in">
                        <h4 class="timeline-title">${ipSession.ip_address}</h4>
${formatDateWithoutTimezone(ipSession.start_use)} - ${formatDateWithoutTimezone(ipSession.stop_use)}
                        <span class="vertical-timeline-element-date">
                           ${new Date(ipSession.stop_use).toISOString().split('T')[0]}
                        </span>
                    </div>
                </div>
            </div>
        `;

                                    $sessionIPs.append(ipHtml);
                                });
                            }
                        });

                        if (response.data) {
                            unblockUI('admin');
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            function validateAdmin($form) {
                $form.data('validator', null);
                $form.validate({
                    rules: {
                        email: {
                            required: true,
                            email: true,
                        },
                        firstname: {
                            required: true,
                        },
                        lastname: {
                            required: true,
                        },
                    },
                    messages: {
                        firstname: translate('Please enter Firstname'),
                        lastname: translate('Please enter Lastname'),
                        email: translate('Please enter a valid email address'),
                    },
                    errorElement: "em",
                    errorPlacement: function (error, $element) {
                        error.addClass("invalid-feedback");
                        if ($element.prop("type") === "checkbox") {
                            error.insertAfter($element.next("label"));
                        } else {
                            error.insertAfter($element);
                        }
                    },
                    highlight: function ($element) {
                        $($element).addClass("is-invalid").removeClass("is-valid");
                    },
                    unhighlight: function ($element) {
                        $($element).removeClass("is-invalid").addClass("is-valid");
                    }
                });
                $form.validate().resetForm();
                return $form.valid();
            }

            $("#save").on("click", function (event) {
                const $form = $("#admin");
                event.preventDefault();
                if (!validateAdmin($form)) {
                    return;
                }

                const formData = serializeForm($form);

                PUQajax('{{route('admin.api.admin.put',$uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            const $modalElement = $('#universalModal');
            const $modalTitle = $modalElement.find('.modal-title');
            const $modalBody = $modalElement.find('.modal-body');

            $modalElement.on('show.bs.modal', function () {
                $modalTitle.text(translate('Change password'));
                const formHtml = `<form id="changePassword" class="col-md-10 mx-auto" method="post" action="" novalidate="novalidate">
        <div class="mb-3">
            <label class="form-label" for="password">` + translate('Password') + `</label>
            <input type="password" class="form-control" id="password" name="password"
                   placeholder="` + translate('Password') + `">
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirmation">` + translate('Confirm password') + `</label>
            <div>
                <input type="password" class="form-control" id="password_confirmation"
                       name="password_confirmation" placeholder="` + translate('Confirm password') + `">
            </div>
        </div>
    </form>`;

                $modalBody.html(formHtml);
            });

            const $saveButton = $('#modalSaveButton');
            $saveButton.on('click', function (event) {
                event.preventDefault();

                const $form = $('#changePassword');

                if ($form.length === 0) {
                    console.error("Form not found");
                    return;
                }

                const formData = serializeForm($form);

                PUQajax('{{route('admin.api.admin.put',$uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        const bootstrapModal = bootstrap.Modal.getInstance($modalElement[0]);
                        bootstrapModal.hide();
                    });
            });

            loadFormData();
        });
    </script>

@endsection
