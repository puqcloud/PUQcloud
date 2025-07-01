@extends(config('template.client.view') . '.login.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')
    <div class="d-flex min-vh-100 justify-content-center align-items-center bg-light">
        <div class="container px-0">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8 col-sm-10 col-xs-12 mx-auto">
                    <div class="card shadow-lg" style="box-shadow: 0 10px 30px rgba(0,0,0,0.7); border-radius: 5px;">
                        <div class="card-body">
                            <div class="h5 text-center mb-4">
                                <h1 class="mt-2 card-title fs-1">{{ __('main.Login') }}</h1>
                            </div>
                            <form id="loginForm" class="mx-auto" novalidate="novalidate">
                                <div class="mb-3">
                                    <div class="input-group input-group-lg">
                                    <span class="input-group-text">
                                        <i class="fa fa-envelope" style="width: 20px;"></i>
                                    </span>
                                        <input id="email" name="email" type="email" class="form-control"
                                               placeholder="{{ __('main.Email') }}">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="input-group input-group-lg">
                                    <span class="input-group-text">
                                        <i class="fa fa-lock" style="width: 20px;"></i>
                                    </span>
                                        <input id="password" name="password" type="password" class="form-control"
                                               placeholder="{{ __('main.Password') }}">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer bg-premium-dark"
                             style="border-bottom-right-radius: 5px; border-bottom-left-radius: 5px;">
                            <div class="w-100">
                                <div class="w-100 mt-3">
                                    <button id="login" name="login"
                                            class="btn-wide btn btn-primary btn-lg btn-warning w-100">
                                        {{ __('main.LOGIN') }}
                                    </button>
                                </div>
                                <div class="w-100 d-flex justify-content-center">
                                    <a href="{{ route('client.web.panel.password_lost') }}"
                                       class="btn-wide btn w-100 btn btn-lg btn-link text-white">
                                        {{ __('main.Forgot password?') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="container px-0">
                        <div class="text-center mt-2">
                            <p class="btn card-title btn-wide w-100 btn-lg btn-link text-white p-2 mb-2"
                               style="text-shadow: 0 2px 2px rgba(0,0,0,0.9);">
                                {{ __('main.Do you not have an account?') }}
                            </p>
                            <a class="btn-lg btn btn-light btn-wide btn-lg btn-link"
                               href="{{ route('client.web.panel.sign_up') }}">
                                {{ __('main.Register now') }}
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

            $("#login").on("click", function (event) {
                blockUI('appContainer');

                const $form = $("#loginForm");
                event.preventDefault();
                const formData = serializeForm($form);

                PUQajax('{{route('client.api.login.post')}}', formData, 50, $(this), 'POST', $form)
                    .then(function (response) {
                        if (response.data) {
                            unblockUI('appContainer');
                            openConfirmActionModal({
                                fetchUrl: null,
                                fetchMethod: 'GET',
                                actionUrl: '{{ route('client.api.login.post') }}',
                                actionMethod: 'POST',
                                actionText: translate('Please Enter Your 2FA Verification Code'),
                                actionType: 'info',
                                confirmButtonText: '<i class="fa fa-check"></i> ' + translate('Verify'),
                                titleText: translate('Two-Factor Authentication Required'),
                                onSuccess: function (res) {
                                    blockUI('appContainer');
                                },
                                onError: function (res) {
                                    // handle error
                                },
                                button: $(this),
                                fetchData: response.data,
                                hiddenInputs: {
                                    email: formData.email,
                                    password: formData.password
                                }
                            });
                        }
                    })
                    .catch(function (error) {
                        unblockUI('appContainer');
                    });
            });

        });
    </script>
@endsection
