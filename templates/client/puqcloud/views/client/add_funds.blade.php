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
                    <i class="fas fa-wallet icon-gradient bg-mean-fruit fs-2"></i>
                </div>
                <div>
                    {{ __('main.Add Funds') }}
                    <div class="page-title-subheading text-muted">
                        {{ __('main.Replenish your account to continue using services without interruption') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container px-0">
        <div class="card shadow-sm p-4">
            <div class="alert alert-primary d-flex align-items-start mb-4" role="alert">
                <i class="bi bi-lightbulb-fill me-3 fs-4 text-warning"></i>
                <div>
                    <div class="fw-bold">
                        {{ __('main.Recommended amount') }}:
                        <span class="text-primary">
                            {{ $funds_params['currency']['prefix'] .' '. number_format($funds_params['recommended_add_funds_amount'], 2) .' '. $funds_params['currency']['suffix'] }}
                        </span>
                    </div>
                    <small class="text-muted d-block mt-1">
                        {{ __('main.This amount covers upcoming or overdue service payments including setup fees') }}
                    </small>
                </div>
            </div>
            <form id="amountForm">
                <div class="row">
                    <div class="col-md-8 p-3">
                        <label for="amountInput" class="form-label fw-bold">
                            <i class="bi bi-cash-coin"></i> {{ __('main.Enter amount (net)') }}
                        </label>
                        <div class="input-group mb-3">
                            <button class="btn btn-outline-primary" type="button" onclick="setRecommendedAmount()">
                                {{ number_format($funds_params['recommended_add_funds_amount'], 2) }}
                                <i class="fas fa-arrow-right"></i>
                            </button>
                            <span class="input-group-text">{{ $funds_params['currency']['code'] }}</span>
                            <input type="number" class="form-control form-control-lg"
                                   id="amountInput"
                                   name="amount"
                                   value="{{ $funds_params['recommended_add_funds_amount'] }}"
                                   min="{{ $funds_params['min_add_funds_amount'] }}"
                                   max="{{ $funds_params['max_add_funds_amount'] }}"
                                   step="0.01">
                        </div>

                        <div class="d-flex justify-content-between text-muted small border-bottom pb-2 mb-3">
                        <span><i class="bi bi-arrow-right-circle"></i> {{ __('main.Minimum') }}:
                            <strong>{{ $funds_params['currency']['prefix'] .' '. number_format($funds_params['min_add_funds_amount'], 2) .' '. $funds_params['currency']['suffix'] }}</strong>
                        </span>
                            <div class="text-center mb-0">
                                <div class="fw-bold fs-6">{{ __('main.Balance after top-up') }}</div>
                                <div class="fs-5 text-info fw-bold" id="balanceAfterTopup">
                                    {{ $funds_params['currency']['prefix'] .' '. number_format($funds_params['balance'] + $funds_params['recommended_add_funds_amount'], 2) .' '. $funds_params['currency']['suffix'] }}
                                </div>
                            </div>
                            <span><i class="bi bi-arrow-up-circle"></i> {{ __('main.Maximum') }}:
                            <strong>{{ $funds_params['currency']['prefix'] .' '. number_format($funds_params['max_add_funds_amount'], 2) .' '. $funds_params['currency']['suffix'] }}</strong>
                        </span>
                        </div>

                        <div class="text-center mb-2">
                            <div class="fw-bold fs-5">{{ __('main.Total with taxes') }}</div>
                            <div class="fs-1 text-success fw-bold" id="totalWithTaxes">
                                {{ $funds_params['currency']['prefix'] .' '. number_format($funds_params['recommended_add_funds_amount'], 2) .' '. $funds_params['currency']['suffix'] }}
                            </div>
                        </div>

                        <div class="text-center">
                            <button id="top_up_button"
                                    class="btn-wide mb-2 me-2 btn btn-outline-2x btn-outline-success btn-lg" i>
                                <i class="fa fa-money-bill btn-icon-wrapper"></i> {{ __('main.Top Up Now') }}
                            </button>
                        </div>
                    </div>

                    @if(!empty($funds_params['taxes']))
                        <div class="col-md-4 p-3">
                            <div class="fw-bold mb-2">{{ __('main.Taxes breakdown') }}</div>
                            <ul class="list-group">
                                @foreach($funds_params['taxes'] as $tax)
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>{{ $tax['name'] }} ({{ $tax['rate'] }}%)</span>
                                        <span class="tax-value" data-rate="{{ $tax['rate'] }}">
                                        {{ $funds_params['currency']['prefix'] }} 0.00 {{ $funds_params['currency']['suffix'] }}
                                    </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        const minAmount = {{ $funds_params['min_add_funds_amount'] }};
        const maxAmount = {{ $funds_params['max_add_funds_amount'] }};
        const maxClientBalance = {{ $funds_params['max_client_balance'] }};
        const currentBalance = {{ $funds_params['balance'] }};
        const currencyPrefix = "{{ $funds_params['currency']['prefix'] }} ";
        const currencySuffix = " {{ $funds_params['currency']['suffix'] }}";

        function updateTaxesAndTotal() {
            const inputEl = document.getElementById('amountInput');
            let amount = parseFloat(inputEl.value.replace(',', '.')) || 0;

            amount = Math.round(amount * 100) / 100;

            let taxesSum = 0;

            document.querySelectorAll('.tax-value').forEach(el => {
                const rate = parseFloat(el.getAttribute('data-rate')) || 0;
                let tax = amount * (rate / 100);
                tax = Math.round(tax * 100) / 100;

                el.innerText = currencyPrefix + tax.toFixed(2) + currencySuffix;
                taxesSum += tax;
            });

            taxesSum = Math.round(taxesSum * 100) / 100;
            let total = Math.round((amount + taxesSum) * 100) / 100;
            const futureBalance = Math.round((currentBalance + amount) * 100) / 100;

            document.getElementById('totalWithTaxes').innerText =
                currencyPrefix + total.toFixed(2) + currencySuffix;

            document.getElementById('balanceAfterTopup').innerText =
                currencyPrefix + futureBalance.toFixed(2) + currencySuffix;

            if (futureBalance > maxClientBalance) {
                inputEl.classList.add('is-invalid');
            } else {
                inputEl.classList.remove('is-invalid');
            }
        }


        function setRecommendedAmount() {
            const recommended = {{ $funds_params['recommended_add_funds_amount'] }};
            const input = document.getElementById('amountInput');
            input.value = recommended.toFixed(2);
            updateTaxesAndTotal();
        }

        document.getElementById('amountInput').addEventListener('input', function () {
            const input = this;
            let val = parseFloat(input.value) || 0;
            const max = parseFloat(input.max);

            if (val > max) {
                input.value = max.toFixed(2);
            }

            updateTaxesAndTotal();
        });

        window.addEventListener('DOMContentLoaded', updateTaxesAndTotal);

        $("#top_up_button").on("click", function (event) {
            var $form = $("#amountForm");
            event.preventDefault();
            const formData = serializeForm($form);

            PUQajax('{{ route('client.api.client.add_funds.top_up.post') }}', formData, 50, $(this), 'POST', $form);
        });

    </script>
@endsection
