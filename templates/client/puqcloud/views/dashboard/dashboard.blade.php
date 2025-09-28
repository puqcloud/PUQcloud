@extends(config('template.client.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')

    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="pe-7s-display2 icon-gradient bg-premium-dark"></i>
                </div>
                <div>
                    {{__('main.Dashboard')}}
                    <div class="page-title-subheading">
                    </div>
                </div>
            </div>
            <div class="page-title-actions">

            </div>
        </div>
    </div>

    <div class="container px-0">
        <div class="mb-3 card" id="services">
            <div class="card-header-tab card-header">
                <div class="card-header-title font-size-lg text-capitalize fw-normal">
                    <i class="header-icon lnr-charts icon-gradient bg-happy-green"></i>
                    {{ __('main.Services') }}
                </div>
                <div class="btn-actions-pane-right text-capitalize"></div>
            </div>
            <div class="g-0 row">

                <!-- Total Services -->
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-3 col-xxl-3">
                    <div class="card no-shadow rm-border bg-transparent widget-chart text-start">
                        <div class="icon-wrapper rounded-circle">
                            <div class="icon-wrapper-bg opacity-10 bg-primary"></div>
                            <i class="lnr-layers text-primary opacity-8 text-white"></i>
                        </div>
                        <div class="widget-chart-content">
                            <div class="widget-subheading">{{ __('main.Total Services') }}</div>
                            <div id="total-services" class="widget-numbers">0</div>
                        </div>
                    </div>
                </div>

                <!-- Active -->
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-3 col-xxl-3">
                    <div class="card no-shadow rm-border bg-transparent widget-chart text-start">
                        <div class="icon-wrapper rounded-circle">
                            <div class="icon-wrapper-bg opacity-10 bg-success"></div>
                            <i class="lnr-checkmark-circle text-success opacity-8 text-white"></i>
                        </div>
                        <div class="widget-chart-content">
                            <div class="widget-subheading">{{ __('main.Active') }}</div>
                            <div id="active-services" class="widget-numbers">0</div>
                        </div>
                    </div>
                </div>

                <!-- Suspended -->
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-3 col-xxl-3">
                    <div class="card no-shadow rm-border bg-transparent widget-chart text-start">
                        <div class="icon-wrapper rounded-circle">
                            <div class="icon-wrapper-bg opacity-10 bg-warning"></div>
                            <i class="lnr-hourglass text-warning opacity-8 text-white"></i>
                        </div>
                        <div class="widget-chart-content">
                            <div class="widget-subheading">{{ __('main.Suspended') }}</div>
                            <div id="suspended-services" class="widget-numbers">0</div>
                        </div>
                    </div>
                </div>

                <!-- Termination Request -->
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-3 col-xxl-3">
                    <div class="card no-shadow rm-border bg-transparent widget-chart text-start">
                        <div class="icon-wrapper rounded-circle">
                            <div class="icon-wrapper-bg opacity-10 bg-danger"></div>
                            <i class="lnr-trash text-warning opacity-8 text-white"></i>
                        </div>
                        <div class="widget-chart-content">
                            <div class="widget-subheading">{{ __('main.Termination Request') }}</div>
                            <div id="termination-request" class="widget-numbers">0</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="mb-3 card" id="calculate_recurring_payments_breakdown" style="min-height: 320px;">
            <div class="card-header-tab card-header">
                <h5 class="mb-0 text-dark">{{ __('main.Recurring Cost Breakdown') }}</h5>
            </div>
            <div class="card-body">
                <div class="mb-4 text-center">
                    <h5 class="text-dark fw-bold">
                        {{ __('main.Recommended Add Funds') }}:
                        <span id="recommended-funds" class="text-success text-nowrap">
                    <span class="placeholder col-3"></span>
                </span>
                    </h5>
                </div>

                <div class="row text-center" id="breakdown-cards" >
                    @foreach(['hourly', 'daily', 'weekly', 'monthly', 'yearly'] as $period)
                        <div class="col-6 col-md-4 col-xl-2 mb-3">
                            <div class="card placeholder-glow border-0" id="card-{{ $period }}" style="min-height: 145px;">
                                <div class="card-body py-4">
                                    <div class="mb-3">
                                        <i class="lnr-tag fs-1" id="icon-{{ $period }}"></i>
                                    </div>
                                    <div class="small text-uppercase fw-bold" id="label-{{ $period }}">
                                        {{ __('main.' . ucfirst($period)) }}
                                    </div>
                                    <div class="fs-5 fw-semibold" id="amount-{{ $period }}">
                                        <span class="placeholder col-6"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    @parent

    <script>
        $(document).ready(function () {
            function loadServicesData() {
                blockUI('services');

                PUQajax('{{ route('client.api.dashboard.services.get') }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        if (response.data) {
                            const data = response.data;

                            $('#total-services').text(data.total);
                            $('#active-services').text(data.active);
                            $('#suspended-services').text(data.suspended);
                            $('#termination-request').text(data.termination_request);

                            unblockUI('services');
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading services data:', error);
                    });
            }

            loadServicesData();

            function loadRecurringPaymentsBreakdownData() {
                blockUI('calculate_recurring_payments_breakdown');

                PUQajax('{{ route('client.api.dashboard.calculate_recurring_payments_breakdown.get') }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        if (response.data) {
                            const data = response.data;
                            const breakdown = { ...data };
                            const prefix = data.currency.prefix || '';
                            const suffix = data.currency.suffix || '';
                            const recommended = data.recommended_funds || 0;

                            delete breakdown.currency;
                            delete breakdown.recommended_funds;

                            const icons = {
                                hourly: 'lnr-clock',
                                daily: 'lnr-sun',
                                weekly: 'pe-7s-graph',
                                monthly: 'lnr-calendar-full',
                                yearly: 'lnr-earth'
                            };

                            const colors = {
                                hourly: 'bg-amy-crisp text-white',
                                daily: 'bg-malibu-beach text-white',
                                weekly: 'bg-midnight-bloom text-white',
                                monthly: 'bg-mixed-hopes text-white',
                                yearly: 'bg-night-sky text-white'
                            };

                            const labels = {
                                hourly: '{{ __("main.Hourly") }}',
                                daily: '{{ __("main.Daily") }}',
                                weekly: '{{ __("main.Weekly") }}',
                                monthly: '{{ __("main.Monthly") }}',
                                yearly: '{{ __("main.Yearly") }}'
                            };

                            let html = '';

                            for (const [period, amount] of Object.entries(breakdown)) {
                                html += `
                            <div class="col-6 col-md-4 col-xl-2 mb-3">
                                <div class="card ${colors[period] || 'bg-light'} border-0">
                                    <div class="card-body py-4">
                                        <div class="mb-3">
                                            <i class="${icons[period] || 'lnr-tag'} fs-1"></i>
                                        </div>
                                        <div class="small text-uppercase fw-bold">
                                            ${labels[period] || period}
                                        </div>
                                        <div class="fs-5 fw-semibold">
                                            ${prefix} ${amount} ${suffix}
                                        </div>
                                    </div>
                                </div>
                            </div>`;
                            }

                            $('#breakdown-cards').html(html);
                            $('#recommended-funds').text(`${prefix} ${recommended} ${suffix}`);
                            unblockUI('calculate_recurring_payments_breakdown');
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading recurring payments breakdown:', error);
                    });
            }

            loadRecurringPaymentsBreakdownData();

        });
    </script>

@endsection
