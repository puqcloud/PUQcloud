@php
    // Core state machine decides which sub-view to render. VPNCP-specific downgrade
    // (empty provision_data.client_name means still deploying even when status=active)
    // is applied on top so the client keeps seeing the spinner until the VPN client
    // record is fully written by the background job.
    $manageState = $service->getManageViewState();
    if (
        $manageState['phase'] === 'active'
        && empty($service->provision_data['client_name'] ?? null)
    ) {
        $manageState['phase'] = 'deploying';
        $manageState['should_poll'] = true;
        $manageState['show_active_content'] = false;
        $manageState['show_deploying'] = true;
    }
@endphp

@section('content')
    @parent
    <div class="container px-0">
        @include($serviceViewBase . '.general.service_details')

        @if($manageState['show_suspended'])
            @include($serviceViewBase . '.general.suspended')
        @endif

        @if($manageState['show_awaiting_payment'] ?? false)
            @include($serviceViewBase . '.general.awaiting_payment')
        @endif

        @if($manageState['show_deploying'])
            @include($serviceViewBase . '.general.pending')
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
    @if($service->product->billing_mode === 'both' && $service->status === 'active' && !$service->termination_request && $service->provision_status === 'completed')
        @if($service->billing_mode === 'invoice')
            <button type="button"
                    class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info"
                    id="switch_billing_mode" data-mode="prepaid">
                <i class="fa fa-exchange-alt"></i>
                {{__('main.Switch to Prepaid')}}
            </button>
        @else
            <button type="button"
                    class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info"
                    id="switch_billing_mode" data-mode="invoice">
                <i class="fa fa-exchange-alt"></i>
                {{__('main.Switch to Invoice')}}
            </button>
        @endif
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
                    actionUrl: '{{ route('client.api.cloud.service.manage.post', ['uuid' => $service->uuid, 'method' => 'TerminationRequest']) }}',
                    actionMethod: 'POST',
                    actionText:
                        `{{ __('main.Confirm that you want to delete the service') }}` +
                        '<br>{{ $product->name }} - <span class="badge bg-secondary ms-2">{{ $service->client_label }}</span>' +
                        '<br>' +
                        `{!! $service->billing_timestamp
                ? __('main.Will be removed from') . ': ' . \Carbon\Carbon::parse($service->billing_timestamp)->format('Y-m-d H:i:s')
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

            $('#switch_billing_mode').on('click', function (e) {
                e.preventDefault();
                const newMode = $(this).data('mode');
                const modeLabel = newMode === 'prepaid' ? translate('Prepaid') : translate('Invoice');
                openConfirmActionModal({
                    actionUrl: '{{ route('client.api.cloud.service.manage.post', ['uuid' => $service->uuid, 'method' => 'SwitchBillingMode']) }}',
                    actionMethod: 'POST',
                    hiddenInputs: {billing_mode: newMode},
                    actionText: translate('Confirm switching billing mode to') + ' <strong>' + modeLabel + '</strong>' +
                        '<br>{{ $product->name }} - <span class="badge bg-secondary ms-2">{{ $service->client_label }}</span>',
                    actionType: 'info',
                    confirmButtonText: '<i class="fa fa-exchange-alt"></i> ' + translate('Switch'),
                    titleText: translate('Switch Billing Mode'),
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
