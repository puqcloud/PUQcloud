@if(request()->has('edit'))
    @include('modules.Product.puqProxmox.views.admin_area.load_balancers.load_balancer_web_proxy')
@else
    @include('modules.Product.puqProxmox.views.admin_area.load_balancers.load_balancer_web_proxies_list')
@endif
