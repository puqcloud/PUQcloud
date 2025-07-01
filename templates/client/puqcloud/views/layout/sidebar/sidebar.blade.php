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

<div class="scrollbar-sidebar ps ps--active-y">
    <div class="app-sidebar__inner" style="padding-right: 10px; padding-left: 10px;">
        <ul class="vertical-nav-menu metismenu">
            @foreach ($navigation->getSidebar() as $sidebarItem)
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
                                            <a href="{{ $subSubItem['link'] }}">
                                                {{ $subSubItem['title'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            @php
                                $count = $subItem['service_count'] ?? 0;
$length = strlen((string) $count);
$minWidth = match(true) {
    $length === 1 => 16,
    $length === 2 => 24,
    $length === 3 => 32,
    $length >= 4 => 40,
    default => 12
};
                            @endphp
                            <li @if (!empty($subItem['active'])) class="mm-active" @endif>
                                <a href="{{ $subItem['link'] }}" aria-expanded="true"
                                   class="d-flex align-items-center w-100">
                                    <div class="btn-icon btn-icon-only btn-sm p-0">
                                        <i class="{{ $subItem['icon'] }}"></i>
                                        @if (!empty($count) && $count > 0)
                                            <span class="badge rounded-pill bg-success"
                                                  style="padding: 2px 5px 3px 5px; text-align: right; min-width: {{ $minWidth }}px;">
                                                {{ $count }}
                                            </span>
                                        @endif
                                    </div>
                                    <span class="nav-item-text">{{ $subItem['title'] }}</span>
                                </a>

                            </li>
                        @endif
                    @endforeach
                @endif

            @endforeach
        </ul>
    </div>
</div>
