<div class="card w-100 mb-2 d-flex flex-column">
    <div class="card-body d-flex flex-column">
        <div class="row align-items-stretch">
            <div class="col-xs-12 col-sm-12 col-md-4 col-lg-3 col-xl-3 col-xxl-3 ">
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
                                <i class="fa fa-save"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <div class="d-flex justify-content-between align-items-center pb-2 border-bottom">
                    <div class="d-flex align-items-center text-muted fw-semibold text-nowrap">
                        <i class="pe-7s-check text-info me-2 fs-5"></i>
                        {{ __('main.Status') }}
                    </div>
                    <div class="text-end flex-fill">
                        <div
                            class="badge bg-{{ renderServiceStatusClass($service->status) }} text-uppercase px-3 py-1">
                            {{ __('main.' . $service->status) }}
                        </div>
                    </div>
                </div>

                @if($service->idle)
                    <div class="d-flex justify-content-between align-items-center pt-2 pb-2 border-bottom">
                        <div class="d-flex align-items-center text-muted fw-semibold text-nowrap">
                            <i class="pe-7s-moon text-warning me-2 fs-5"></i>
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
                         class="countdown-timer text-danger fw-bold text-center mb-3"
                         style="font-size: 1.5rem;">
                        <i class="fa {{ $countdown['icon'] }} me-2"></i>
                        <span class="countdown-label"></span>
                        <span class="countdown-time"></span>
                    </div>
                @endif
            </div>
            <div class="col-xs-12 col-sm-12 col-md-8 col-lg-9 col-xl-9 col-xxl-9">
                <div class="row g-3">

                    {{-- Ordered --}}
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4">
                        <div
                            class="d-flex justify-content-between align-items-center p-2 border rounded bg-light">
                            <div class="d-flex align-items-center text-muted fw-semibold text-nowrap">
                                <i class="pe-7s-cart text-success me-2 fs-5"></i>
                                {{ __('main.Ordered') }}
                            </div>
                            <div class="text-end flex-fill">
                                {{ $service->order_date }}
                            </div>
                        </div>
                    </div>
                    {{-- Suspended --}}
                    @if($service->status == 'suspended')
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4">
                            <div
                                class="d-flex justify-content-between align-items-center p-2 border rounded bg-light">
                                <div class="d-flex align-items-center text-muted fw-semibold text-nowrap">
                                    <i class="fa fa-pause-circle text-warning me-2 fs-5"></i>
                                    {{ __('main.Suspended') }}
                                </div>
                                <div class="text-end text-nowrap">
                                    <span class="d-block">{{ $service->suspended_date }}</span>
                                    @if($service->suspended_reason)
                                        <small
                                            class="text-danger d-block">{{ __('main.'.$service->suspended_reason) }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                    {{-- Period --}}
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4">
                        <div
                            class="d-flex justify-content-between align-items-center p-2 border rounded bg-light">
                            <div class="d-flex align-items-center text-muted fw-semibold">
                                <i class="pe-7s-timer me-2 text-warning fs-5"></i>
                                {{ __('main.Period') }}
                            </div>
                            <div class="text-end flex-fill">
                                {{ __('main.' . $service->price_detailed['period']) }}
                            </div>
                        </div>
                    </div>
                    {{-- Price Total --}}
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4">
                        <div
                            class="d-flex justify-content-between align-items-center p-2 border rounded bg-light">
                            <div class="d-flex align-items-center text-muted fw-semibold">
                                <i class="pe-7s-cash me-2 text-success fs-5"></i>
                                {{ __('main.Price Total') }}
                            </div>
                            <div class="text-end flex-fill">
                                {{ $service->price_detailed['currency']['prefix'] }}
                                {{ number_format_custom($service->price_detailed['total']['base'], 2, $client->currency->format) }}
                                {{ $service->price_detailed['currency']['suffix'] }}
                            </div>
                        </div>
                    </div>
                    {{-- Hourly Billing --}}
                    @if($product->hourly_billing && $service->price_detailed['period'] == 'monthly')
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4">
                            <div
                                class="d-flex justify-content-between align-items-center p-2 border rounded bg-light">
                                <div class="d-flex align-items-center text-muted fw-semibold">
                                    <i class="pe-7s-clock me-2 text-info fs-5"></i>
                                    {{ __('main.Hourly Billing') }}
                                </div>
                                <div class="text-end flex-fill">
                                            <span
                                                class="badge bg-success text-uppercase px-3 py-1">{{ __('main.Yes') }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                    {{-- Next Due Date --}}
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4">
                        <div
                            class="d-flex justify-content-between align-items-center p-2 border rounded bg-light">
                            <div class="d-flex align-items-center text-muted fw-semibold">
                                <i class="pe-7s-date me-2 text-primary fs-5"></i>
                                {{ __('main.Next Due Date') }}
                            </div>
                            <div class="text-end flex-fill">
                                {{ $service->billing_timestamp }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
