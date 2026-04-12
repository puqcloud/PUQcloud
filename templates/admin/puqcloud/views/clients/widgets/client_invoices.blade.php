@if($invoices->isEmpty())
    <div class="text-center text-muted py-3">
        <i class="fa fa-file-invoice" style="font-size: 24px;"></i>
        <p class="mt-2 mb-0" style="font-size: 12px;">{{ __('main.No invoices') }}</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size: 12px;">
            <thead>
                <tr>
                    <th>{{ __('main.Invoice') }}</th>
                    <th class="text-center">{{ __('main.Status') }}</th>
                    <th class="text-center">{{ __('main.Date') }}</th>
                    <th class="text-end">{{ __('main.Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                    @php
                        $badgeClass = match($invoice->status) {
                            'paid' => 'bg-success',
                            'unpaid' => 'bg-danger',
                            'draft' => 'bg-secondary',
                            'canceled' => 'bg-warning text-dark',
                            'refunded' => 'bg-info',
                            default => 'bg-light text-dark',
                        };
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.web.redirect', ['label' => 'Invoice', 'uuid' => $invoice->uuid]) }}">
                                {{ $invoice->number ?? $invoice->uuid }}
                            </a>
                            @if($invoice->type !== 'invoice')
                                <small class="text-muted">({{ $invoice->type }})</small>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $badgeClass }}">{{ ucfirst($invoice->status) }}</span>
                        </td>
                        <td class="text-center text-nowrap">
                            {{ $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('Y-m-d') : '---' }}
                        </td>
                        <td class="text-end text-nowrap">
                            {{ number_format($invoice->total, 2) }}
                            <small class="text-muted">{{ $invoice->currency_code }}</small>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($unpaidTotal > 0)
        <hr class="mt-1 mb-1">
        <div class="text-center">
            <span class="text-danger" style="font-size: 12px;">
                <i class="fa fa-exclamation-circle"></i>
                {{ __('main.Unpaid') }}: <b>{{ number_format($unpaidTotal, 2) }} {{ $currencyCode }}</b>
            </span>
        </div>
    @endif
@endif
