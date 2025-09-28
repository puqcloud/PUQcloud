<div class="app-header__logo">
    <a href="{{route('admin.web.dashboard')}}">
        <div class="logo-src"
             style="background: url({{ asset_admin('images/logo.png') }}) no-repeat center / contain;  width: 200px; height: 50px;"></div>
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
        @include(config('template.admin.view') .'.layout.header.search')
    </div>


    <div class="app-header-right">

        <div class="header-dots">
            <div class="dropdown">
                @include(config('template.admin.view') .'.layout.header.notifications')
            </div>
        </div>

        <div class="header-btn-lg pe-0">
            @include(config('template.admin.view') .'.layout.header.user')
        </div>

{{--        <div class="header-btn-lg">--}}
{{--            <div class="header-dots">--}}
{{--                <div class="dropdown">--}}
{{--                    @include(config('template.admin.view') .'.layout.header.help')--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}

    </div>
</div>
