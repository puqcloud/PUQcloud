{{-- Ordered --}}
<div class="d-flex justify-content-between align-items-center pt-2 pb-2 border-bottom">
    <div class="d-flex align-items-center text-muted fw-semibold text-nowrap">
        <i class="pe-7s-cart text-success me-2" style="font-size: 1.3rem;"></i>
        {{ __('main.Ordered') }}
    </div>
    <div class="text-end flex-fill">
        {{ $service->order_date }}
    </div>
</div>

{{-- Suspended --}}
@if($service->status == 'suspended')
    <div class="d-flex justify-content-between align-items-center pt-2 pb-2 border-bottom">
        <div class="d-flex align-items-center text-muted fw-semibold text-nowrap">
            <i class="fa fa-pause-circle text-warning me-2" style="font-size: 1.3rem;"></i>
            {{ __('main.Suspended') }}
        </div>
        <div class="text-end text-nowrap">
            <div class="text-end text-nowrap">
                <span class="d-block">{{ $service->suspended_date }}</span>
                <small class="text-danger d-block">{{ __('main.'.$service->suspended_reason) }}</small>
            </div>
        </div>
    </div>
@endif

{{-- Period --}}
<div class="d-flex justify-content-between align-items-center pt-2 pb-2 border-bottom">
    <div class="d-flex align-items-center text-muted fw-semibold ">
        <i class="pe-7s-timer me-2 text-warning" style="font-size: 1.3rem;"></i>
        {{ __('main.Period') }}
    </div>
    <div class="text-end  flex-fill">
        {{ __('main.' . $service->price_detailed['period']) }}
    </div>
</div>

{{-- Price Total --}}
<div class="d-flex justify-content-between align-items-center pt-2 pb-2 border-bottom">
    <div class="d-flex align-items-center text-muted fw-semibold ">
        <i class="pe-7s-cash me-2 text-success" style="font-size: 1.3rem;"></i>
        {{ __('main.Price Total') }}
    </div>
    <div class="text-end  flex-fill">
        {{ $service->price_detailed['currency']['prefix'] }}
        {{ number_format_custom($service->price_detailed['total']['base'], 2, $client->currency->format) }}
        {{ $service->price_detailed['currency']['suffix'] }}
    </div>
</div>

{{-- Hourly Billing OR Next Due Date --}}
@if($product->hourly_billing && $service->price_detailed['period'] == 'monthly')
    <div class="d-flex justify-content-between align-items-center pt-2 pb-2 border-bottom">
        <div class="d-flex align-items-center text-muted fw-semibold ">
            <i class="pe-7s-clock me-2 text-info" style="font-size: 1.3rem;"></i>
            {{ __('main.Hourly Billing') }}
        </div>
        <div class="text-end  flex-fill">
            <span class="badge bg-success text-uppercase px-3 py-1">{{ __('main.Yes') }}</span>
        </div>
    </div>
@endif
<div class="d-flex justify-content-between align-items-center pt-2 pb-2 border-bottom">
    <div class="d-flex align-items-center text-muted fw-semibold ">
        <i class="pe-7s-date me-2 text-primary" style="font-size: 1.3rem;"></i>
        {{ __('main.Next Due Date') }}
    </div>
    <div class="text-end  flex-fill">
        {{ $service->billing_timestamp }}
    </div>
</div>

