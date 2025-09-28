<div class="app-header__logo">
    <div class="logo-src"
         style="background: url({{ asset_admin('images/logo.png') }}) no-repeat center / contain;  width: 200px; height: 50px;"></div>
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

<div class="scrollbar-sidebar ps ps--active-y">
    <div class="app-sidebar__inner">
        <ul class="vertical-nav-menu metismenu">

            @foreach ($admin->getSidebar() as $sidebarItem)
                <li class="app-sidebar__heading">{{ $sidebarItem['title'] }}</li>

                @if (isset($sidebarItem['subItems']))

                    @foreach ($sidebarItem['subItems'] as $subItem)

                        @if (isset($subItem['subItems']))
                            <li>
                                <a href="#">
                                    <i class="{{ $subItem['icon'] }}"></i>
                                    {{ $subItem['title'] }}
                                    <i class="metismenu-state-icon pe-7s-angle-down caret-left"></i>
                                </a>
                                <ul class="mm-collapse">
                                    @foreach ($subItem['subItems'] as $subSubItem)
                                        <li @if (!empty($subSubItem['active'])) class="mm-active" @endif>
                                            <a href="{{ $subSubItem['link'] }}" @if(!empty($subSubItem['blank']))target="_blank"@endif>
                                                {{ $subSubItem['title'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li @if (!empty($subItem['active'])) class="mm-active" @endif>
                                <a href="{{ $subItem['link'] }}" aria-expanded="true" @if(!empty($subItem['blank']))target="_blank"@endif>
                                    <i class="{{ $subItem['icon'] }}"></i>
                                    {{ $subItem['title'] }}
                                </a>
                            </li>
                        @endif
                    @endforeach

                @endif
            @endforeach
        </ul>
    </div>
</div>
