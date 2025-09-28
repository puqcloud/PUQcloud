<div class="row py-2 border-bottom align-items-center">
    <div class="col-6 text-muted text-start">
        <strong>{{ __('main.Balance') }}</strong>
    </div>
    <div class="col-6 text-end">
        <div class="fsize-1">
            <span>{{ number_format_custom($client->balance->balance ?? 0, 4, $client->currency->format) }}</span>
            <small class="opacity-5">{{ $currency->code }}</small>
        </div>
    </div>
</div>

<div class="row py-2 border-bottom align-items-center">
    <div class="col-6 text-muted text-start">
        <strong>{{ __('main.Credit Limit') }}</strong>
    </div>
    <div class="col-6 text-end">
        <div class="fsize-1">
            <span>{{ number_format_custom($client->credit_limit ?? 0, 2, $currency->format) }}</span>
            <small class="opacity-5">{{ $currency->code }}</small>
        </div>
    </div>
</div>

<div class="row py-2 border-bottom align-items-center">
    <div class="col-6 text-muted text-start">
        <strong>{{ __('main.VIES Status') }}</strong>
    </div>
    <div class="col-6 text-end">
        @if($client->viesValidation)
            <div class="fsize-1">
                @if($client->viesValidation->error)
                    <span class="text-danger">
                        {{ $client->viesValidation->error }}
                    </span>
                @else
                    @if($client->viesValidation->valid)
                        <span class="text-success fw-bold">{{ __('main.Valid') }}</span>
                    @else
                        <span class="text-danger fw-bold">{{ __('main.Invalid') }}</span>
                    @endif
                @endif
                <br>
                <small class="opacity-5">
                    {{ $client->viesValidation->request_date ?? '---' }}
                </small>
            </div>
        @else
            <div class="fsize-1">
                <span class="text-muted">{{ __('main.Not applicable') }}</span><br>
                <small class="opacity-5">---</small>
            </div>
        @endif
    </div>
</div>

<div class="row py-2 border-bottom align-items-center">
    <div class="col-6 text-muted text-start">
        <strong>{{ __('main.Home Company') }}</strong>
    </div>
    <div class="col-6 text-end">
        <div class="fsize-1">
            <span>{{ $client->getHomeCompany()->company_name }}</span>
        </div>
    </div>
</div>

<div class="row py-2 border-bottom align-items-center">
    <div class="col-6 text-muted text-start">
        <strong>{{ __('main.Taxes') }}</strong>
    </div>
    <div class="col-6 text-end">
        <div class="fsize-1">
            @if (!empty($client->getTaxes()))
                @foreach ($client->getTaxes() as $tax)
                    <div><strong>{{ $tax['name'] }}:</strong> {{ $tax['rate'] }}%</div>
                @endforeach
            @else
                <div class="text-danger">{{ __('main.No taxes') }}</div>
            @endif
        </div>
    </div>
</div>

