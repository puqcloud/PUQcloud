<div class="app-header__logo">

    <a href="{{ route('client.web.home') }}">
        <div class="logo-src"
             style="background: url({{ $layout_options->images['logo'] ?? asset('puqcloud/images/logo.png') }}) no-repeat center / contain; width: 200px; height: 50px;"></div>
    </a>

    <div class="header__pane ms-auto">
        <div>
            <button type="button" class="hamburger close-sidebar-btn hamburger--elastic"
                    data-class="closed-sidebar">
                                <span class="hamburger-box">
                                    <span class="hamburger-inner"></span>
                                </span>
            </button>
        </div>
    </div>
</div>
<div class="app-header__mobile-menu">
    <div>
        <button type="button" class="hamburger hamburger--elastic mobile-toggle-nav">
                            <span class="hamburger-box">
                                <span class="hamburger-inner"></span>
                            </span>
        </button>
    </div>
</div>
<div class="app-header__menu">
                    <span>
                        <button type="button"
                                class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav">
                            <span class="btn-icon-wrapper">
                                <i class="fa fa-ellipsis-v fa-w-6"></i>
                            </span>
                        </button>
                    </span>
</div>
<div class="app-header__content">

    <div class="app-header-left">
        @include(config('template.client.view') .'.layout.header.balance')
    </div>

    <div class="app-header-right">

        <div class="header-dots">

{{--            <div class="dropdown">--}}
{{--                @include(config('template.client.view') .'.layout.header.notifications')--}}
{{--            </div>--}}

            <div class="dropdown">
                @include(config('template.client.view') .'.layout.header.client')
            </div>

            @if(session()->has('login_as_client_owner'))
                <div class="dropdown">
                    @include(config('template.client.view') .'.layout.header.admin')
                </div>
            @endif
        </div>

        <div class="header-btn-lg pe-0">
            @include(config('template.client.view') .'.layout.header.user')
        </div>

    </div>
</div>
