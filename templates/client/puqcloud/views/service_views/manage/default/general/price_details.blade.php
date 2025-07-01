<div class="main-card card position-relative mb-2">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fa fa-dollar-sign text-success me-2" style="font-size: 1.3rem;"></i>{{ __('main.Price Details') }}
        </h5>
        <button class="btn-icon btn-shadow btn-outline-2x btn btn-outline-info"
                type="button"
                data-bs-toggle="collapse" data-bs-target="#priceDetails"
                aria-expanded="false" aria-controls="priceDetails">
            <i class="fa fa-chevron-down" data-bs-toggle-icon></i>
        </button>
    </div>
    <div class="collapse position-absolute top-100 start-0 w-100 bg-white shadow border rounded"
         id="priceDetails"
         style="z-index: 9999;">
        <div class="card-body p-3">
            {{-- Service main prices --}}
            @php
                $serviceLabels = [
                    'setup' => 'Setup',
                    'base' => 'Base',
                    'idle' => 'Idle',
                    'switch_down' => 'Switch Down',
                    'switch_up' => 'Switch Up',
                    'uninstall' => 'Uninstall'
                ];
            @endphp

            @foreach($serviceLabels as $key => $label)
                @php
                    $value = $service->price_detailed['service'][$key] ?? 0;
                @endphp
                @if($value > 0)
                    <div class="d-flex border-bottom py-2 align-items-center">
                        <i class="fa
                            @switch($key)
                                @case('setup') fa-wrench text-info @break
                                @case('base') fa-cube text-primary @break
                                @case('idle') fa-hourglass-half text-warning @break
                                @case('switch_down') fa-arrow-down text-danger @break
                                @case('switch_up') fa-arrow-up text-success @break
                                @case('uninstall') fa-trash text-danger @break
                                @default fa-circle
                            @endswitch
                            me-3" style="font-size:1.3rem; width:30px;"></i>
                        <div class="flex-grow-1 fw-semibold text-nowrap">{{ __('main.' . $label) }}</div>
                        <div class="text-nowrap">
                            {{ $service->price_detailed['currency']['prefix'] }}
                            {{ number_format_custom($value, 2, $client->currency->format) }}
                            {{ $service->price_detailed['currency']['suffix'] }}
                        </div>
                    </div>
                @endif
            @endforeach

            {{-- Options --}}
            @foreach($service->price_detailed['options'] as $option)
                @php
                    $hasPrice = collect($option['price'])->filter(fn($v) => $v > 0)->isNotEmpty();
                @endphp
                @if($hasPrice)
                    <div class="border rounded mb-3 p-3 bg-light">
                        <div class="fw-bold text-secondary mb-2 d-flex align-items-center">
                            <i class="fa fa-puzzle-piece me-2" style="font-size:1.3rem;"></i>
                            <span>{{ $option['model']->name }}</span>
                        </div>

                        @foreach($serviceLabels as $key => $label)
                            @php
                                $value = $option['price'][$key] ?? 0;
                            @endphp
                            @if($value > 0)
                                <div class="d-flex border-bottom py-2 align-items-center ps-4">
                                    <i class="fa
                                        @switch($key)
                                            @case('setup') fa-wrench text-info @break
                                            @case('base') fa-cube text-primary @break
                                            @case('idle') fa-hourglass-half text-warning @break
                                            @case('switch_down') fa-arrow-down text-danger @break
                                            @case('switch_up') fa-arrow-up text-success @break
                                            @case('uninstall') fa-trash text-danger @break
                                            @default fa-circle
                                        @endswitch
                                        me-3" style="font-size:1.3rem; width:30px;"></i>
                                    <div class="flex-grow-1 text-nowrap">{{ __('main.' . $label) }}</div>
                                    <div class="text-nowrap">
                                        {{ $service->price_detailed['currency']['prefix'] }}
                                        {{ number_format_custom($value, 2, $client->currency->format) }}
                                        {{ $service->price_detailed['currency']['suffix'] }}
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
