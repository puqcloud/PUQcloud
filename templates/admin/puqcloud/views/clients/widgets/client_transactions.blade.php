@if($transactions->isEmpty())
    <div class="text-center text-muted py-3">
        <i class="fa fa-exchange-alt" style="font-size: 24px;"></i>
        <p class="mt-2 mb-0" style="font-size: 12px;">{{ __('main.No transactions') }}</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size: 12px;">
            <thead>
                <tr>
                    <th>{{ __('main.Date') }}</th>
                    <th>{{ __('main.Gateway') }}</th>
                    <th class="text-end">{{ __('main.Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                    @php
                        $amountClass = $transaction->amount_gross >= 0 ? 'text-success' : 'text-danger';
                    @endphp
                    <tr>
                        <td class="text-nowrap">
                            {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i') }}
                        </td>
                        <td>
                            {{ $transaction->gateway_name ?? '---' }}
                            @if($transaction->type)
                                <small class="text-muted">({{ $transaction->type }})</small>
                            @endif
                        </td>
                        <td class="text-end text-nowrap {{ $amountClass }}">
                            <b>{{ ($transaction->amount_gross >= 0 ? '+' : '') . number_format($transaction->amount_gross, 2) }}</b>
                            <small class="text-muted">{{ $transaction->currency_code }}</small>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
