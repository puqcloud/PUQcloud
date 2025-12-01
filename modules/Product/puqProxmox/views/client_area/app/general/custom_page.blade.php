<div class="card mb-2 shadow-sm border-0" style="min-height: 260px;">
    <div class="card-body" id="custom_page">

    </div>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function loadControlData() {
                blockUI('custom_page');

                PUQajax("{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getAppCustomPage']) }}", {}, 50, null, 'GET')
                    .then(function (response) {
                        if (response.data) {
                            $("#custom_page").html(response.data);
                        }
                        unblockUI('custom_page');
                    })
                    .catch(function () {
                        unblockUI('custom_page');
                    });
            }
            loadControlData();
        });
    </script>
@endsection
