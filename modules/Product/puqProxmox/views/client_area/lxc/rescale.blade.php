@section('content')
    @parent
    <div class="container px-0">
        <div class="row g-2">
            <div class="col-12">
                @include('modules.Product.puqProxmox.views.client_area.lxc.rescale.options')
            </div>
        </div>
    </div>
@endsection
