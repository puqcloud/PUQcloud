@section('content')
    @parent
    <div class="container px-0">
        <div class="row g-2">
            <div class="col-12">
                @include('modules.Product.puqProxmox.views.client_area.lxc.general.info')
            </div>
            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
                @include('modules.Product.puqProxmox.views.client_area.lxc.general.control')
            </div>
            <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
                @include('modules.Product.puqProxmox.views.client_area.lxc.general.location')
            </div>
        </div>
    </div>
@endsection
