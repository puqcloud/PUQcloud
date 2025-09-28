@section('content')
    @parent
    <div class="container px-0">
        <div class="row">
            <div class="col-12">
                @include('modules.Product.puqProxmox.views.client_area.lxc.backups.info')
            </div>
            <div class="col-12">
                @include('modules.Product.puqProxmox.views.client_area.lxc.backups.schedule')
            </div>
            <div class="col-12">
                @include('modules.Product.puqProxmox.views.client_area.lxc.backups.backups')
            </div>
        </div>
    </div>
@endsection
