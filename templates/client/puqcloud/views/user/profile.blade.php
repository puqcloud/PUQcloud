@extends(config('template.client.view') . '.layout.layout')

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
                    <i class="fas fa-address-card icon-gradient bg-tempting-azure"></i>
                </div>
                <div>
                    {{__('main.My Account')}}
                    <div class="page-title-subheading">
                        {{__('main.Manage your personal account details')}}
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <button id="save" type="button"
                        class="btn-wide mb-2 me-2 btn btn-outline-2x btn-outline-success btn">
                    <i class="fa fa-save"></i> {{__('main.Save')}}
                </button>
            </div>
        </div>
    </div>

    <div class="container px-0">
        <div id="mainCard" class="card shadow-sm">
            <div class="card-body">
                <form id="profileForm" novalidate>
                    <div class="row g-3">

                        <!-- Email -->
                        <div class="col-md-6 col-xxl-4">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i> {{ __('main.Email') }}
                            </label>
                            <input type="text" class="form-control input-mask-trigger" id="email" name="email"
                                   data-inputmask="'alias': 'email'" inputmode="email"
                                   placeholder="{{ __('main.Email') }}">
                        </div>

                        <!-- Firstname -->
                        <div class="col-md-6 col-xxl-4">
                            <label for="firstname" class="form-label">
                                <i class="fas fa-user me-1"></i> {{ __('main.Firstname') }}
                            </label>
                            <input type="text" class="form-control" id="firstname" name="firstname"
                                   placeholder="{{ __('main.Firstname') }}">
                        </div>

                        <!-- Lastname -->
                        <div class="col-md-6 col-xxl-4">
                            <label for="lastname" class="form-label">
                                <i class="fas fa-user me-1"></i> {{ __('main.Lastname') }}
                            </label>
                            <input type="text" class="form-control" id="lastname" name="lastname"
                                   placeholder="{{ __('main.Lastname') }}">
                        </div>

                        <!-- Language -->
                        <div class="col-md-6 col-xxl-4">
                            <label for="language" class="form-label">
                                <i class="fas fa-language me-1"></i> {{ __('main.Language') }}
                            </label>
                            <select name="language" id="language" class="form-select">
                                <!-- Fill with options dynamically -->
                            </select>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6 col-xxl-4">
                            <label for="phone_number" class="form-label">
                                <i class="fas fa-phone me-1"></i> {{ __('main.Phone Number') }}
                            </label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number">
                            <input type="hidden" id="country_code" name="country_code">
                        </div>

                    </div>

                    <!-- Password Section -->
                    <div class="row mt-4">
                        <div class="col-md-6 col-xxl-4">
                            <div class="card border-warning shadow-sm">
                                <div class="card-body">
                                    <label for="inputExistingPassword" class="form-label">
                                        <i class="fas fa-lock me-1 text-warning"></i> {{ __('main.Existing Password') }}
                                    </label>
                                    <input type="password" class="form-control" name="existingpw"
                                           id="inputExistingPassword" autocomplete="off"
                                           placeholder="{{ __('main.Existing Password') }}">
                                </div>
                            </div>
                        </div>
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
            const $input = $("#phone_number");
            const iti = window.intlTelInput($input[0], {
                separateDialCode: true,
                width: '100%',
                initialCountry: navigator.language.split('-')[1] || 'us',
            });

            $input.on('countrychange', function () {
                var $countryCodeInput = $("#country_code");
                var dialCode = iti.getSelectedCountryData().dialCode;
                $countryCodeInput.val('+' + dialCode);
            });

            function loadFormData() {
                blockUI('mainCard');
                var $form = $('#profileForm');

                PUQajax('{{route('client.api.user.profile.get')}}', {}, 50, null, 'GET')
                    .then(function (response) {

                        $.each(response.data, function (key, value) {
                            var $element = $form.find('[name="' + key + '"]');
                            if ($element.length) {

                                if (key === 'phone_number' && value !== null) {
                                    iti.setNumber(value);
                                    return;
                                }

                                if ($element.is('select')) {
                                    var selected = response.data[key + '_data'];
                                    initializeSelect2($element[0], '{{route('client.api.languages.select.get')}}', selected, 'GET', 1000, {});
                                    return;
                                }
                                $element.val(value);
                            }
                        });

                        if (response.data) {
                            unblockUI('mainCard');
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                var $form = $("#profileForm");
                event.preventDefault();
                const formData = serializeForm($form);

                PUQajax('{{ route('client.api.user.profile.put') }}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            loadFormData()
        });
    </script>
@endsection
