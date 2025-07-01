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
                    <i class="fas fa-key icon-gradient bg-tempting-azure"></i>
                </div>
                <div>
                    {{__('main.Change Password')}}
                    <div class="page-title-subheading">
                        {{__('main.On this page, you can change the password for your account to keep it secure')}}
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

    <div class="container px-0">
        <div id="mainCard" class="card shadow-sm">
            <div class="card-body">
                <form id="changePasswordForm" novalidate>
                    <!-- Existing Password -->
                    <div class="mb-3">
                        <label for="inputExistingPassword" class="form-label">
                            <i class="fas fa-lock me-1"></i> {{ __('main.Existing Password') }}
                        </label>
                        <input type="password" class="form-control" name="existingpw" id="inputExistingPassword" autocomplete="off">
                    </div>

                    <!-- New Password -->
                    <div class="mb-3">
                        <label for="inputNewPassword1" class="form-label">
                            <i class="fas fa-unlock-alt me-1"></i> {{ __('main.New Password') }}
                        </label>
                        <input type="password" class="form-control" name="newpw" id="inputNewPassword1" autocomplete="off">
                        <div class="invalid-feedback">
                            {{ __('main.Password must be at least 6 characters, include a number and a special character') }}
                        </div>
                        <div class="valid-feedback">
                            {{ __('main.Strong password') }}
                        </div>
                        <div class="progress mt-2 mb-2" id="passwordStrengthBar">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 0%;">0%</div>
                        </div>
                        <small class="text-muted">
                            {{ __('main.Must be at least 6 characters, include 1 number and 1 special character') }}
                        </small>
                    </div>

                    <!-- Confirm New Password -->
                    <div class="mb-3">
                        <label for="inputNewPassword2" class="form-label">
                            <i class="fas fa-check-circle me-1"></i> {{ __('main.Confirm New Password') }}
                        </label>
                        <input type="password" class="form-control" name="confirmpw" id="inputNewPassword2" autocomplete="off">
                        <div class="invalid-feedback">
                            {{ __('main.Passwords do not match') }}
                        </div>
                        <div class="valid-feedback">
                            {{ __('main.Passwords match') }}
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

            $('#inputNewPassword1').on('input', function () {
                const password = $(this).val();
                const $input = $(this);

                const hasLength = password.length >= 6;
                const hasNumber = /[0-9]/.test(password);
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

                const strength = [hasLength, hasNumber, hasSpecial].filter(Boolean).length;
                updateStrengthBar(strength);

                if (validatePassword(password)) {
                    $input.removeClass('is-invalid').addClass('is-valid');
                } else {
                    $input.removeClass('is-valid').addClass('is-invalid');
                }

                $('#inputNewPassword2').trigger('input');
            });

            $('#inputNewPassword2').on('input', function () {
                const pw1 = $('#inputNewPassword1').val();
                const pw2 = $(this).val();
                const $input = $(this);

                if (pw1 === pw2 && validatePassword(pw1)) {
                    $input.removeClass('is-invalid').addClass('is-valid');
                } else {
                    $input.removeClass('is-valid').addClass('is-invalid');
                }
            });

            $("#save").on("click", function (event) {
                var $form = $("#changePasswordForm");
                event.preventDefault();
                const formData = serializeForm($form);
                PUQajax('{{ route('client.api.user.change_password.put') }}', formData, 5000, $(this), 'PUT', $form);
            });

        });
    </script>

@endsection
