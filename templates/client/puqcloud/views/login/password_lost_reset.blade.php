@extends(config('template.client.view') . '.login.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')
    <div class="d-flex min-vh-100 justify-content-center align-items-center bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8 col-sm-10 col-xs-12 mx-auto">
                    <div class="card shadow-lg" style="box-shadow: 0 10px 30px rgba(0,0,0,0.7); border-radius: 5px;">
                        <div class="card-body">
                            <div class="h5 text-center mb-4">
                                <h1 class="mt-2 card-title fs-1">{{ __('main.Request new password') }}</h1>
                            </div>
                            <form id="resetPasswordForm" novalidate>
                                <input type="hidden" name="token" id="token" value="{{$token}}">
                                <div class="mb-3">
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="fa fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="{{ __('main.Password') }}" autocomplete="off" required>
                                    </div>
                                    <div class="invalid-feedback">{{ __('main.Password must be at least 6 characters, include a number and a special character') }}</div>
                                    <div class="valid-feedback">{{ __('main.Strong password') }}</div>
                                    <div class="progress mt-2 mb-2" id="passwordStrengthBar">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 0%;">0%</div>
                                    </div>
                                    <small class="text-muted">{{ __('main.Must be at least 6 characters, include 1 number and 1 special character') }}</small>
                                </div>

                                <div class="mb-3">
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="fa fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="{{ __('main.Confirm password') }}" autocomplete="off" required>
                                    </div>
                                    <div class="invalid-feedback">{{ __('main.Passwords do not match') }}</div>
                                    <div class="valid-feedback">{{ __('main.Passwords match') }}</div>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer bg-premium-dark"
                             style="border-bottom-right-radius: 5px; border-bottom-left-radius: 5px;">
                            <div class="w-100">
                                <div class="w-100 mt-3 mb-3">
                                    <button id="continue" class="btn-wide btn btn-primary btn-lg btn-warning w-100">
                                        {{ __('main.CONTINUE') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="container px-0">
                        <div class="text-center mt-2">
                            <p class="btn card-title btn-wide w-100 btn-lg btn-link text-white p-2 mb-2"
                               style="text-shadow: 0 2px 2px rgba(0,0,0,0.9);">
                                {{ __('main.Do you already have an account?') }}
                            </p>
                            <a class="btn-lg btn btn-light btn-wide btn-lg btn-link"
                               href="{{route('client.web.panel.login')}}">
                                {{ __('main.Login now') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent

    <script>
        $(document).ready(function () {
            function validatePassword(password) {
                const hasLength = password.length >= 6;
                const hasNumber = /[0-9]/.test(password);
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                return hasLength && hasNumber && hasSpecial;
            }

            function updateStrengthBar(strength) {
                const $bar = $('#passwordStrengthBar .progress-bar');
                let color = 'bg-danger';
                let value = 33;

                if (strength === 2) {
                    color = 'bg-warning';
                    value = 66;
                } else if (strength === 3) {
                    color = 'bg-success';
                    value = 100;
                }

                $bar.removeClass('bg-success bg-warning bg-danger').addClass(color);
                $bar.css('width', value + '%').text(value + '%');
            }

            $('#password').on('input', function () {
                const password = $(this).val();
                const hasLength = password.length >= 6;
                const hasNumber = /[0-9]/.test(password);
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                const strength = [hasLength, hasNumber, hasSpecial].filter(Boolean).length;

                updateStrengthBar(strength);

                if (validatePassword(password)) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                }

                $('#password_confirmation').trigger('input');
            });

            $('#password_confirmation').on('input', function () {
                const pw1 = $('#password').val();
                const pw2 = $(this).val();

                if (pw1 === pw2 && validatePassword(pw1)) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                }
            });

            $('#continue').on('click', function (e) {
                blockUI('appContainer');
                e.preventDefault();
                const $form = $('#resetPasswordForm');
                const formData = serializeForm($form);
                PUQajax('{{ route('client.api.reset_password.post') }}', formData, 50, $(this), 'POST', $form)
                    .then(function (response) {
                        unblockUI('appContainer');
                    })
                    .catch(function (error) {
                        unblockUI('appContainer');
                    });
            });
        });
    </script>

@endsection
