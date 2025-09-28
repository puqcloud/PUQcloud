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
                    <i class="fas fa-lock icon-gradient bg-tempting-azure"></i>
                </div>
                <div>
                    {{__('main.Two Factor Authentication')}}
                    <div class="page-title-subheading">
                        {{__('main.Enable or manage two-factor authentication for extra login protection')}}
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
            </div>
        </div>
    </div>

    <div class="container px-0">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">{{ __('main.Two-Factor Authentication') }}</h3>

                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="widget-chart widget-chart2 text-start mb-3 card-btm-border
                        {{ $user->two_factor ? 'card-shadow-success border-success' : 'card-shadow-danger border-danger' }} card">
                            <div class="widget-chat-wrapper-outer">
                                <div class="widget-chart-content">
                                    <div class="widget-title opacity-5 text-uppercase">{{ __('main.Currently') }}</div>
                                    <div class="widget-numbers mt-2 fsize-4 mb-0 w-100">
                                        <div class="widget-chart-flex align-items-center">
                                            <div>
                                            <span class="pe-2">
                                                @if($user->two_factor)
                                                    <i class="fa fa-check-circle text-success"></i>
                                                @else
                                                    <i class="fa fa-times-circle text-danger"></i>
                                                @endif
                                            </span>
                                                {{ $user->two_factor ? __('main.Enabled') : __('main.Disabled') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 text-center">
                        @if($user->two_factor)
                            <button id="disable2fa" class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-danger">
                                <i class="fa fa-lock-open me-2"></i> {{ __('main.Disable 2FA') }}
                            </button>
                        @else
                            <button id="enable2fa" class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-success">
                                <i class="fa fa-lock me-2"></i> {{ __('main.Enable 2FA') }}
                            </button>
                        @endif
                    </div>
                </div>

                <div class="alert alert-warning mt-4 mb-3">
                    <i class="fa fa-shield-alt fa-2x me-3 text-warning"></i>
                    {{ __('main.We recommend enabling two-factor authentication to enhance your account security') }}
                </div>

                <div class="alert alert-info d-flex align-items-center justify-content-between" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-user-shield fa-2x me-3 text-primary"></i>
                        <div>
                            <strong>{{ __('main.Manage your verification methods') }}</strong><br>
                            {{ __('main.To update or change your security settings, please visit the Verification Center') }}
                        </div>
                    </div>
                    <a href="{{ route('client.web.panel.user.verification_center') }}" class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-primary">
                        <i class="fa fa-cog me-1"></i> {{ __('main.Go to Verification Center') }}
                    </a>
                </div>

            </div>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>

        $('#enable2fa').on('click', function (e) {
            e.preventDefault();
            openConfirmActionModal({
                fetchUrl: '{{route('client.api.verification.get')}}',
                fetchMethod: 'GET',
                actionUrl: '{{route('client.api.user.two_factor_authentication.enable.post')}}',
                actionMethod: 'POST',
                actionText: translate('Confirming the activation of two-factor verification'),
                actionType: 'info',
                confirmButtonText: '<i class="fa fa-check"> </i> ' + translate('OK'),
                titleText: translate('Enable 2FA'),
                onSuccess: function (res) {
                    location.reload();
                },
                onError: function (res) {
                },
                button: $(this)
            });
        });

        $('#disable2fa').on('click', function (e) {
            e.preventDefault();
            openConfirmActionModal({
                fetchUrl: '{{route('client.api.verification.get')}}',
                fetchMethod: 'GET',
                actionUrl: '{{route('client.api.user.two_factor_authentication.disable.post')}}',
                actionMethod: 'POST',
                actionText: translate('Confirming the deactivation of two-factor verification'),
                actionType: 'info',
                confirmButtonText: '<i class="fa fa-check"> </i> ' + translate('OK'),
                titleText: translate('Disable 2FA'),
                onSuccess: function (res) {
                    location.reload();
                },
                onError: function (res) {
                },
                button: $(this)
            });
        });

    </script>
@endsection
