<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
{{--<script>--}}
{{--    (function () {--}}
{{--        const width = window.innerWidth;--}}
{{--        const BREAKPOINT = 1250;--}}
{{--        const classes = width < BREAKPOINT--}}
{{--            ? ' closed-sidebar closed-sidebar-mobile'--}}
{{--            : '';--}}
{{--        document.write('<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="' + classes + '">');--}}
{{--    })();--}}
{{--</script>--}}
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Language" content="en">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, shrink-to-fit=no">
    <meta name="msapplication-tap-highlight" content="no">
    <title>@yield('title')</title>
    <link rel="icon" href="{{ $layout_options->images['favicon'] ?? asset_client('images/favicon.png') }}"
          type="image/png">
    <link rel="stylesheet" href="{{ mix('/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset_client('styles/css/base.css') }}">
    <style>
        html {
            font-size: 15px;
        }
    </style>
    <script>
        window.translations = {!! json_encode($js_translations) !!};
    </script>

    @hasSection('background')
        @yield('background')
    @else
        @if($layout_options->images['background'])
            <style>
                .puq-background-blur {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.5)),
                    url('{{ $layout_options->images['background'] }}') no-repeat center center fixed;
                    background-size: cover;
                    filter: blur(6px);
                    z-index: 0;
                }
            </style>
        @endif
    @endif
    @yield('head')
</head>
<body>

<div
    class="app-container app-theme-white body-tabs-shadow fixed-footer {{ $layout_options->fixed_header }} {{ $layout_options->fixed_sidebar }}">
    <div class="app-header header-shadow {{ $layout_options->header_color_scheme }}">
        @include(config('template.client.view') .'.layout.header.header')
    </div>

    <div class="app-main">
        <div class="puq-background-blur"></div>
        <div class="app-sidebar sidebar-shadow {{ $layout_options->sidebar_color_scheme }}">
            @include(config('template.client.view') .'.layout.sidebar.sidebar')
        </div>
        <div class="app-main__outer" id="mainInner" >
            @if(!empty($navigation->incidents))
                @include(config('template.client.view') .'.layout.header.messages')
            @endif
            <div class="app-main__inner">
                @yield('content')
            </div>
            <div class="app-wrapper-footer d-inline d-md-none" style="z-index: 999999;">
                <div class="app-footer">
                    <div class="app-footer__inner">
                        @include(config('template.client.view') .'.layout.footer.footer')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ mix('/js/app.js') }}"></script>
<script type="text/javascript" src="{{ asset_client('js/app.js') }}"></script>
<script src="{{ route('static.puqcloud.js') }}"></script>

@yield('js')
<script>
    $(document).ready(function () {
        $("#logout").on("click", function () {
            PUQajax('{{route('client.api.logout')}}', [], 50, $(this), 'GET')
        });
    });
</script>

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
