<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Language" content="en">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, shrink-to-fit=no">
    <meta name="msapplication-tap-highlight" content="no">
    <title>@yield('title')</title>
    <link rel="icon" href="{{ asset_admin('images/favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ mix('/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset_admin('styles/css/base.css') }}">
    <script>
        window.routes = {
            adminRedirect: "{{ route('admin.web.redirect', ['label' => '__label__', 'uuid' => '__uuid__']) }}",
            adminApiFileImageUpload  : "{{ route('admin.api.file.image.upload') }}",
            adminApiFileImageDelete  : "{{ route('admin.api.file.image.delete') }}",
        };

        window.translations = {!! json_encode($js_translations) !!};
    </script>
    @yield('head')
</head>
<body>

<div class="app-container app-theme-white body-tabs-shadow {{ setting('layoutOptionFixed_header') }} {{ setting('layoutOptionFixed_sidebar') }}">
    <div class="app-header header-shadow {{ setting('layoutOptionHeaderColorScheme') }}">
        @include(config('template.admin.view') .'.layout.header.header')
    </div>
    @yield('before-app-main')
    <div class="app-main">
        <div class="app-sidebar sidebar-shadow {{ setting('layoutOptionSidebarColorScheme') }}">
            @include(config('template.admin.view') .'.layout.sidebar.sidebar')
        </div>
        <div class="app-main__outer">
            <div id="appMainInner" class="app-main__inner">
                @yield('content')
            </div>
            <div class="app-wrapper-footer">
                <div class="app-footer">
                    <div class="app-footer__inner">
                        @include(config('template.admin.view') .'.layout.footer.footer')
                    </div>
                    {{app(App\Services\HookService::class)->callHooks('AdminAreaFooterOutput')}}
                    {{app('AdminAreaFooterOutput')}}
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ mix('/js/app.js') }}"></script>
<script type="text/javascript" src="{{ asset_admin('js/app.js') }}"></script>
<script src="{{ route('admin.web.static.template.puqcloud.js') }}"></script>

@yield('js')
<script>
    $(document).ready(function () {
        initPerfectScrollbar();

        $("#logout").on("click", function () {
            PUQajax('{{route('admin.api.logout')}}', [], 1000, $(this), 'GET')
        });

        $(".close-sidebar-btn").click(function () {
            var classToSwitch = $(this).attr("data-class");
            var containerElement = ".app-container";
            $(containerElement).toggleClass(classToSwitch);

            var closeBtn = $(this);

            if (closeBtn.hasClass("is-active")) {
                closeBtn.removeClass("is-active");
            } else {
                closeBtn.addClass("is-active");
            }
        });

    });
</script>
<!-- Universal Modal -->
<div class="modal fade" id="universalModal" tabindex="-1" aria-labelledby="universalModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="universalModalLabel">Modal Title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Dynamic content will be inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-secondary"
                        data-bs-dismiss="modal"><i class="fa fa-times-circle"></i> {{ __('main.Close') }}</button>
                <button type="button" class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-success"
                        id="modalSaveButton"><i class="fa fa-save"></i> {{ __('main.Save') }}</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
