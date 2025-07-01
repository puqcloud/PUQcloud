<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Language" content="en">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, shrink-to-fit=no">
    <!-- Disable tap highlight on IE -->
    <meta name="msapplication-tap-highlight" content="no">

    <title>@yield('title')</title>

    <link rel="icon" href="{{ $login_layout_options->images['favicon'] ?? asset_client('images/favicon.png') }}"
          type="image/png">

    <link rel="stylesheet" href="{{ mix('/css/app.css') }}">

    <link rel="stylesheet" href="{{ asset_client('styles/css/base.css') }}">
    <style>
        html {
            font-size: 14px;
        }
    </style>
    @yield('head')
    <script>
        window.translations = {!! json_encode($js_translations) !!};
    </script>
    <style>
        .background-blur {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('{{ $login_layout_options->images['background'] ?? asset_client('images/server_room.jpg') }}') no-repeat center center fixed;
            background-size: cover;
            filter: blur(6px);
            z-index: 0;
        }

        .app-container {
            position: relative;
            z-index: 0;
        }
    </style>
</head>
<body>

<div id="appContainer" class="app-container app-theme-white body-tabs-shadow fixed-header fixed-footer">

    <div class="app-header header-shadow {{ setting('clientAreaLoginPageHeaderColorScheme') }}">
        <div class="m-4">
            <a href="{{ route('client.web.panel.login') }}">
                <div class="logo-src"
                     style="background: url({{ $login_layout_options->images['logo'] ?? asset('puqcloud/images/logo.png') }}) no-repeat center / contain; width: 200px; height: 50px;"></div>
            </a>
        </div>
        <div class="header-btn-lg pe-0">
            <div class="header-dots">
                @include(config('template.client.view') .'.login.language')
            </div>
        </div>
    </div>

    <div class="app-container">
        <div class="background-blur"></div>
        @yield('content')
    </div>

    <div class="app-wrapper-footer">
        <div class="app-footer bg-white">
            @include(config('template.client.view') .'.login.footer')
        </div>
    </div>

</div>

<script src="{{ mix('/js/app.js') }}"></script>
<script type="text/javascript" src="{{ asset_client('js/app.js') }}"></script>
<script src="{{ route('static.puqcloud.js') }}"></script>
@yield('js')

<!-- Universal Modal -->
<div class="modal fade" id="universalModal" tabindex="-1" aria-labelledby="universalModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="universalModalLabel">Modal Title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Dynamic content will be inserted here -->
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div>
                    <button type="button" class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-secondary"
                            id="modalCloseButton" data-bs-dismiss="modal">
                        <i class="fa fa-times-circle"></i> {{ __('main.Close') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-success"
                            id="modalConfirmButton">
                        <i class="fa fa-save"></i> {{ __('main.Save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
