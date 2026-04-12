{{--
    Generic "terminated" / "cancelled" sub-view for the default manage template.

    Rendered by general.blade.php when getManageViewState() reports phase = terminated
    (which covers service.status of `terminated`, `cancelled`, or `not_found`).
    No polling — terminal state.
--}}
@php
    $status = $service->status;
    $headline = match ($status) {
        'terminated' => __('main.Service Terminated'),
        'cancelled' => __('main.Service Cancelled'),
        'not_found' => __('main.Service Not Found'),
        default => __('main.Service Unavailable'),
    };
    $reason = $service->terminated_reason ?: $service->cancelled_reason ?: null;
@endphp

<div class="text-center my-5 bg-secondary text-white py-5 rounded shadow-lg border border-dark">
    <div class="mb-4">
        <i class="fa fa-circle-xmark fa-7x animate-fade"></i>
    </div>
    <div class="display-2 fw-bold mb-3 text-uppercase">{{ $headline }}</div>
    <div class="fs-4 fw-medium">
        {{ __('main.This service is no longer available') }}
    </div>
    @if($reason)
        <div class="mt-4 fs-5 fst-italic">
            {{ __('main.Reason') }}: {{ $reason }}
        </div>
    @endif
    @if($status === 'terminated' && $service->terminated_date)
        <div class="mt-3 fs-6">
            {{ __('main.Terminated at') }}: {{ \Carbon\Carbon::parse($service->terminated_date)->format('Y-m-d H:i:s') }}
        </div>
    @endif
    @if($status === 'cancelled' && $service->cancelled_date)
        <div class="mt-3 fs-6">
            {{ __('main.Cancelled at') }}: {{ \Carbon\Carbon::parse($service->cancelled_date)->format('Y-m-d H:i:s') }}
        </div>
    @endif
</div>

<style>
    .animate-fade { animation: fade 2s infinite; }
    @keyframes fade {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }
</style>
