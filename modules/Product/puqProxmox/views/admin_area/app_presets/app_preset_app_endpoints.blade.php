@if(request()->has('edit'))
    @include('modules.Product.puqProxmox.views.admin_area.app_presets.app_preset_app_endpoint')
@else
    @include('modules.Product.puqProxmox.views.admin_area.app_presets.app_preset_app_endpoints_list')
@endif
