<div class="card mb-2 shadow-sm border-0" style="min-height: 260px;">
    <div class="card-header bg-light d-flex align-items-center">
        <i class="fas fa-map-marker-alt text-danger me-2"></i>
        <span class="fw-bold">{{ __('Product.puqProxmox.Location') }}</span>
    </div>
    <div class="card-body" id="location">
        <div class="row g-3 align-items-center" id="location_row" style="display:none;">

            <div class="col-12 col-md-12 col-lg-6 col-xl-7">
                <div class="mb-2 d-flex">
                    <img id="location_icon"
                         alt=""
                         class="rounded border me-3"
                         style="width:64px; height:48px; object-fit:cover;">
                    <div>
                        <h6 class="mb-1 fw-bold" id="location_name"></h6>
                        <p class="text-muted mb-2" id="location_dc"></p>
                    </div>
                </div>

                <p class="small text-secondary mb-0" id="location_description"></p>
            </div>

            <div class="col-12 col-md-12 col-lg-6 col-xl-5">
                <img id="location_background"
                     alt=""
                     class="img-fluid rounded border">
            </div>

        </div>
    </div>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            function loadLocationData() {
                blockUI('location');
                PUQajax("{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getLxcLocation']) }}", {}, 50, null, 'GET')
                    .then(function (response) {
                        let data = response.data;
                        $("#location_name").text(data.name);
                        $("#location_dc").text(data.data_center);
                        $("#location_description").text(data.description);
                        $("#location_icon").attr("src", data.icon_url).attr("alt", data.name);
                        $("#location_background").attr("src", data.background_url).attr("alt", data.name);
                        $("#location_row").show();
                        unblockUI('location');
                    });
            }
            loadLocationData();
        });
    </script>
@endsection
