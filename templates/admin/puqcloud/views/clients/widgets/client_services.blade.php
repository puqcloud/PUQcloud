@if($services->isEmpty())
    <div class="text-center text-muted py-3">
        <i class="fa fa-server" style="font-size: 24px;"></i>
        <p class="mt-2 mb-0" style="font-size: 12px;">{{ __('main.No services') }}</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size: 12px;">
            <thead>
                <tr>
                    <th>{{ __('main.Product') }}</th>
                    <th class="text-center">{{ __('main.Status') }}</th>
                    <th class="text-end">{{ __('main.Price') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($services as $service)
                    @php
                        $badgeClass = match($service->status) {
                            'active' => 'bg-success',
                            'suspended' => 'bg-warning text-dark',
                            'pending' => 'bg-info',
                            'terminated' => 'bg-danger',
                            'cancelled' => 'bg-secondary',
                            default => 'bg-light text-dark',
                        };
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.web.redirect', ['label' => 'Service', 'uuid' => $service->uuid]) }}">
                                {{ $service->product_name ?? '---' }}
                            </a>
                            @if($service->termination_request)
                                <i class="fa fa-exclamation-triangle text-danger" title="Termination requested"></i>
                            @endif
                            @if($service->idle)
                                <i class="fa fa-moon text-warning" title="Idle"></i>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $badgeClass }}">{{ ucfirst($service->status) }}</span>
                        </td>
                        <td class="text-end text-nowrap">
                            {{ number_format($service->price_base ?? 0, 2) }}
                            <small class="text-muted">/{{ $service->price_period ?? '' }}</small>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="text-center mt-1">
        <span class="text-muted" style="font-size: 11px;">
            {{ __('main.Total') }}: <b>{{ $services->count() }}</b>
            | {{ __('main.Active') }}: <b class="text-success">{{ $services->where('status', 'active')->count() }}</b>
        </span>
    </div>
@endif
