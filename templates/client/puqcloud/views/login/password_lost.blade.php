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
                                <span>{{ __('main.Please enter the email address associated with your account.') }}</span>
                            </div>
                            <form id="resetPasswordForm" data-bitwarden-watching="1">
                                <div class="mb-3">
                                    <div class="input-group input-group-lg">
                                    <span class="input-group-text">
                                        <i class="fa fa-envelope" style="width: 20px;"></i>
                                    </span>
                                        <input type="email" name="email" id="email" class="form-control"
                                               placeholder="{{ __('main.Email') }}">
                                    </div>
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
    </script>

@endsection
