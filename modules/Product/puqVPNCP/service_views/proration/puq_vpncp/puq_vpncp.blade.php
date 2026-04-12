@php
    $iconUrl = $product->images['icon'] ?? null;
    $backgroundUrl = $product->images['background'] ?? null;
    $groupIcon = $product_group->icon ?? null;
    $isFlag = $groupIcon && strpos($groupIcon, 'flag') === 0;
@endphp

@if($backgroundUrl)
    @section('background')
        <style>
            .puq-background-blur {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.5)),
                url('{{ $backgroundUrl }}') no-repeat center center fixed;
                background-size: cover;
                filter: blur(6px);
                z-index: 0;
            }
        </style>
    @endsection
@endif

<div id="header" class="app-page-title">
    <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div class="page-title-icon" style="{{ $iconUrl ? 'width: auto; padding: 0;' : '' }}">
                @if ($iconUrl)
                    <div class="p-1" style="display: flex; align-items: center; justify-content: center;">
                        <img src="{{ $iconUrl }}" alt="icon" style="max-height: 50px;">
                    </div>
                @elseif ($groupIcon)
                    @if ($isFlag)
                        <i class="{{ $groupIcon }} large"></i>
                    @else
                        <i class="{{ $groupIcon }} icon-gradient bg-ripe-malin"></i>
                    @endif
                @else
                    <i class="fas fa-shield-alt icon-gradient bg-ripe-malin"></i>
                @endif
            </div>
            <div>
                {{ __('main.Billing Alignment') }}: {{ $service->client_label }}
                <div class="page-title-subheading">
                    {{ $product->name }}
                </div>
            </div>
        </div>
        <div class="page-title-actions">
            <a href="{{ route('client.web.panel.cloud.service', ['uuid' => $service->uuid, 'tab' => 'general']) }}"
               class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i>
                {{ __('main.Back to Service') }}
            </a>
        </div>
    </div>
</div>

<div class="container px-0">
    <div class="main-card card mb-3">
        <div class="card-header">
            <i class="fas fa-shield-alt me-2 text-primary"></i>
            {{ __('main.Current Billing') }}
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>{{ __('main.Product') }}:</strong> {{ $product->name }}
                </div>
                <div class="col-md-4">
                    <strong>{{ __('main.Next Due Date') }}:</strong>
                    {{ $service->billing_timestamp ? \Carbon\Carbon::parse($service->billing_timestamp)->format('Y-m-d H:i:s') : '-' }}
                </div>
                <div class="col-md-4">
                    <strong>{{ __('main.Billing Mode') }}:</strong>
                    <span class="badge bg-{{ $service->billing_mode === 'invoice' ? 'info' : 'success' }}">
                        {{ __('main.' . $service->billing_mode) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="main-card card mb-3">
        <div class="card-header">
            <i class="fas fa-calendar-alt me-2 text-warning"></i>
            {{ __('main.Billing Date Alignment') }}
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">{{ __('main.Align your billing date to a specific day of the month') }}</p>

            <div class="row align-items-end mb-3">
                <div class="col-auto">
                    <label for="target_day" class="form-label">{{ __('main.Day of Month') }}</label>
                    <select id="target_day" class="form-select" style="width: 100px;">
                        @for($i = 1; $i <= 28; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-outline-warning" id="calculate_alignment">
                        <i class="fas fa-calculator me-1"></i> {{ __('main.Calculate') }}
                    </button>
                </div>
            </div>

            <div id="alignment-result" style="display: none;">
                <hr>
                <div class="row">
                    <div class="col-md-8">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td>{{ __('main.Period') }}</td>
                                <td id="res-period"></td>
                            </tr>
                            <tr>
                                <td>{{ __('main.Days') }}</td>
                                <td id="res-days"></td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('main.Subtotal') }}</strong></td>
                                <td><strong id="res-amount"></strong></td>
                            </tr>
                            <tbody id="res-taxes"></tbody>
                            <tr class="table-warning">
                                <td><strong>{{ __('main.Total with taxes') }}</strong></td>
                                <td><strong id="res-gross"></strong></td>
                            </tr>
                            <tr>
                                <td>{{ __('main.New Due Date') }}</td>
                                <td><strong id="res-new-date"></strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-4 d-flex align-items-center justify-content-center">
                        <button class="btn btn-lg btn-warning" id="execute-alignment">
                            <i class="fas fa-calendar-check me-2"></i>{{ __('main.Apply') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            let currentProration = null;

            $('#calculate_alignment').on('click', function () {
                const targetDay = $('#target_day').val();
                const $btn = $(this);

                PUQajax('{{ route('client.api.cloud.service.proration.get', ['uuid' => $service->uuid, 'method' => 'Calculate']) }}'
                    + '?target_day=' + targetDay,
                    null, 0, $btn, 'GET', null
                ).then(response => {
                    if (response && response.data) {
                        currentProration = response.data;
                        const d = response.data;
                        const from = d.from.replace('T', ' ').substring(0, 19);
                        const to = d.new_billing_timestamp.replace('T', ' ').substring(0, 19);

                        $('#res-period').text(from + ' — ' + to);
                        $('#res-days').text(d.days);
                        $('#res-amount').text(d.amount_str);

                        let taxHtml = '';
                        if (d.taxes && d.taxes.length > 0) {
                            d.taxes.forEach(tax => {
                                taxHtml += `<tr><td>${tax.name} (${tax.rate}%)</td><td>${tax.amount_str}</td></tr>`;
                            });
                        }
                        $('#res-taxes').html(taxHtml);
                        $('#res-gross').text(d.gross_total_str);

                        $('#res-new-date').text(to);
                        $('#alignment-result').show();
                    }
                });
            });

            $('#execute-alignment').on('click', function () {
                const targetDay = $('#target_day').val();
                const $btn = $(this);

                openConfirmActionModal({
                    actionUrl: '{{ route('client.api.cloud.service.proration.post', ['uuid' => $service->uuid, 'method' => 'Execute']) }}',
                    actionMethod: 'POST',
                    hiddenInputs: { target_day: targetDay },
                    actionText: translate('Align billing date to day') + ' <strong>' + targetDay + '</strong>?'
                        + '<br>' + translate('Amount') + ': <strong>' + (currentProration ? currentProration.gross_total_str : '') + '</strong>',
                    actionType: 'warning',
                    confirmButtonText: '<i class="fas fa-calendar-check"></i> ' + translate('Confirm'),
                    titleText: translate('Billing Date Alignment'),
                    onSuccess: function (res) {
                        if (res && res.data && res.data.redirect) {
                            window.location.href = res.data.redirect;
                        } else {
                            window.location.href = '{{ route('client.web.panel.cloud.service', ['uuid' => $service->uuid, 'tab' => 'general']) }}';
                        }
                    },
                    button: $btn
                });
            });
        });
    </script>
@endsection
