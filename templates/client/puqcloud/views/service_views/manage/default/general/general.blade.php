@section('content')
    @parent
    <div class="container px-0">
        <div class="row align-items-stretch">
            <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-4 d-flex">
                <div class="card w-100 mb-2 d-flex flex-column">
                    <div class="card-body d-flex flex-column">
                        {{-- Label with Save --}}
                        <form id="clientLabelForm">
                            <div class="row align-items-start mb-2">
                                <label for="client_label" class="form-label">{{ __('main.Label') }}</label>
                                <div class="col pe-0">
                                    <input name="client_label" id="client_label" type="text"
                                           class="form-control"
                                           value="{{ $service->client_label }}">
                                </div>
                                <div class="col-auto ps-1 align-text-top">
                                    <button
                                        class="btn btn-outline-success btn-icon-only me-2 btn-icon-only btn-outline-2x"
                                        type="button" id="save_label" style="width: 50px;">
                                        <i class="fa fa-save"> </i>
                                    </button>
                                </div>
                            </div>

                        </form>
                        {{-- Status --}}
                        <div class="d-flex justify-content-between align-items-center pb-2 border-bottom">
                            <div class="d-flex align-items-center text-muted fw-semibold text-nowrap">
                                <i class="pe-7s-check text-info me-2" style="font-size: 1.3rem;"></i>
                                {{ __('main.Status') }}
                            </div>
                            <div class="text-end flex-fill">
                                <div class="badge bg-{{ renderServiceStatusClass($service->status) }} text-uppercase px-3 py-1">
                                    {{ __('main.' . $service->status) }}
                                </div>
                            </div>
                        </div>

                        {{-- Idle --}}
                        @if($service->idle)
                            <div class="d-flex justify-content-between align-items-center pt-2 pb-2 border-bottom">
                                <div class="d-flex align-items-center text-muted fw-semibold text-nowrap">
                                    <i class="pe-7s-moon text-warning me-2" style="font-size: 1.3rem;"></i>
                                    {{ __('main.Idle') }}
                                </div>
                                <div class="text-end flex-fill">
                                    <span class="badge bg-success text-uppercase px-3 py-1">{{ __('main.Yes') }}</span>
                                </div>
                            </div>
                        @endif
                        @php
                            $countdown = null;

                            if ($service->termination_request) {
                                $countdown = [
                                    'seconds' => $service->getTerminationTime()['seconds_left'],
                                    'label' => __('main.Terminates in'),
                                    'icon' => 'fa-exclamation-circle'
                                ];
                            } elseif ($service->status === 'pending') {
                                $countdown = [
                                    'seconds' => $service->getCancellationTime()['seconds_left'],
                                    'label' => __('main.Cancels in'),
                                    'icon' => 'fa-clock'
                                ];
                            } elseif ($service->status === 'suspended') {
                                $countdown = [
                                    'seconds' => $service->getTerminationTime()['seconds_left'],
                                    'label' => __('main.Terminates in'),
                                    'icon' => 'fa-exclamation-circle'
                                ];
                            }
                        @endphp

                        @if ($countdown)
                            <div id="countdown-{{ $service->uuid }}"
                                 data-seconds="{{ $countdown['seconds'] }}"
                                 data-label="{{ $countdown['label'] }}"
                                 class="countdown-timer text-danger fw-bold text-center mb-2"
                                 style="font-size: 1.5rem;">
                                <i class="fa {{ $countdown['icon'] }} me-2"></i>
                                <span class="countdown-label"></span>
                                <span class="countdown-time"></span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-4 d-flex">
                <div class="card w-100 mb-2 d-flex flex-column">
                    <div class="card-body d-flex flex-column">
                        @include(config('template.client.view') . '.service_views.manage.' . $product_group->manage_template . '.general.service_details')
                    </div>
                </div>
            </div>

            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-4">
                <div class="row d-flex">
                    <div class="col-12">
                        @include(config('template.client.view') . '.service_views.manage.' . $product_group->manage_template . '.general.price_details')
                    </div>
                    @php
                        $product_options = $service->product_options;
                    @endphp

                    @if($product_options->count() != 0)
                        <div class="col-12">
                            @include(config('template.client.view') . '.service_views.manage.' . $product_group->manage_template . '.general.options')
                        </div>
                    @endif

                </div>
            </div>

        </div>
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
