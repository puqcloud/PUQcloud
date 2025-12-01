@section('content')
    @parent
    <div class="container px-0">
        <div class="row">
            <div class="col-12">
                @include('modules.Product.puqProxmox.views.client_area.app.backups.info')
            </div>
            <div class="col-12">
                @include('modules.Product.puqProxmox.views.client_area.app.backups.schedule')
            </div>
            <div class="col-12">
                @include('modules.Product.puqProxmox.views.client_area.app.backups.backups')
            </div>
        </div>
    </div>
@endsection
