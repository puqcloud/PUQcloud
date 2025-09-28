<div id="header" class="app-page-title app-page-title-simple p-0">
    <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div>
                <div class="page-title-head center-elem">
                                            <span class="d-inline-block pe-2">
                                                <i class="fas fa-server"></i>
                                            </span>
                    <span class="d-inline-block">{{ $title }}</span>
                </div>
                <div class="page-title-subheading opacity-10">
                    <nav class="" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a>
                                    <i aria-hidden="true" class="fa fa-home"></i>
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{route('admin.web.dashboard')}}">{{ __('Product.puqProxmox.Dashboard') }}</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{route('admin.web.Product.puqProxmox.lxc_presets')}}">{{ __('Product.puqProxmox.LXC Presets') }}</a>
                            </li>
                            <li class="active breadcrumb-item" aria-current="page">
                                {{ $title }}
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        <div class="page-title-actions">
            @yield('buttons')
        </div>
    </div>
</div>

<div class="p-0">
    <ul class="body-tabs body-tabs-layout tabs-animated body-tabs-animated nav p-0">
        @php
            $tabs = [
                'general' => __('Product.puqProxmox.General'),
                'cluster_groups' => __('Product.puqProxmox.Cluster Groups'),
                'os_templates' => __('Product.puqProxmox.OS Templates'),
            ];
        @endphp

        @foreach($tabs as $key => $label)
            <li class="nav-item">
                <a
                    role="tab"
                    class="nav-link {{ $tab === $key ? 'active show' : '' }}"
                    href="{{ route('admin.web.Product.puqProxmox.lxc_preset.tab', ['uuid' => $uuid, 'tab' => $key]) }}"
                    aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
                    <span>{{ $label }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>

@section('js')
    @parent
@endsection
