@section('content')
    @parent
    <div class="container px-0">
        <div class="row">
            <div class="col-12">
                @include('modules.Product.puqProxmox.views.client_area.lxc.networks.public')
            </div>
            <div class="col-12">
                @include('modules.Product.puqProxmox.views.client_area.lxc.networks.private')
            </div>
        </div>
    </div>
@endsection
