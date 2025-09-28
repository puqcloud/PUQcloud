@section('content')
    @parent
    <div class="container px-0">
        @include(config('template.client.view') . '.service_views.manage.' . $product_group->manage_template . '.general.service_details')

        @if($service->status == 'suspended')
            @include(config('template.client.view') . '.service_views.manage.' . $product_group->manage_template . '.general.suspended')
        @endif

        @if($service->status == 'pending')
            @include(config('template.client.view') . '.service_views.manage.' . $product_group->manage_template . '.general.pending')
        @endif

    </div>
@endsection

@section('buttons')
    @parent
    @if($service->termination_request)
        <button type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary"
                id="cancel_termination_request">
            <i class="fa fa-undo"></i>
            {{ __('main.Cancel Termination Request') }}
        </button>
    @else
        <button type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-danger"
                id="termination_request">
            <i class="fa fa-trash-alt"></i>
            {{__('main.Termination Request')}}
        </button>
    @endif
@endsection


@section('js')
    @parent
    <script>
        $(document).ready(function () {
            const el = $('#countdown-{{ $service->uuid }}');
            if (el.length) {
                const seconds = parseInt(el.data('seconds'));
                const label = el.data('label');
                if (!isNaN(seconds)) {
                    startCountdown(el[0], seconds, label);
                }
            }
        });
    </script>

    <script>
        $(document).ready(function () {

            $("#cancel_termination_request").on("click", function (event) {
                PUQajax('{{ route('client.api.cloud.service.manage.post', ['uuid' => $service->uuid, 'method' => 'CancelTerminationRequest']) }}', null, 50, $(this), 'POST', null)
                    .then(response => {
                    });
            });

            $("#save_label").on("click", function (event) {
                event.preventDefault();
                const $form = $("#clientLabelForm");
                const formData = serializeForm($form);
                PUQajax('{{ route('client.api.cloud.service.manage.post', ['uuid' => $service->uuid, 'method' => 'SetLabel']) }}', formData, 50, $(this), 'POST', $form)
                    .then(response => {
                    });
            });

            $('#termination_request').on('click', function (e) {
                e.preventDefault();
                openConfirmActionModal({
                    fetchUrl: '{{ route('client.api.verification.get') }}',
                    fetchMethod: 'GET',
                    actionUrl: '{{ route('client.api.cloud.service.manage.post', ['uuid' => $service->uuid, 'method' => 'TerminationRequest']) }}',
                    actionMethod: 'POST',
                    actionText:
                        `{{ __('main.Confirm that you want to delete the service') }}` +
                        '<br>{{ $product->name }} - <span class="badge bg-secondary ms-2">{{ $service->client_label }}</span>' +
                        '<br>' +
                        `{!! $service->billing_timestamp
                ? __('main.Will be removed from') . ': ' . $service->billing_timestamp
                : '<strong class="text-danger">' . __('main.Will be deleted immediately') . '</strong>' !!}`,
                    actionType: 'danger',
                    confirmButtonText: '<i class="fa fa-trash"> </i> ' + translate('Delete'),
                    titleText: '{{__('main.Termination Request')}}',
                    onSuccess: function (res) {
                        location.reload();
                    },
                    onError: function (res) {
                    },
                    button: $(this)
                });
            });


        });
    </script>
@endsection
