@php
    $product = $service->product;
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

@section('content')
    <div id="header" class="app-page-title pb-3">
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
                    @elseif (!$groupIcon)
                        <i class="fas fa-cloud icon-gradient bg-ripe-malin"></i>
                    @endif
                </div>
                <div>
                    {{$product->name}} - <span class="badge bg-secondary ms-2">{{ $service->client_label }}</span>
                    <div class="page-title">
                        <a href="{{route('client.web.panel.cloud.group', $product_group->uuid)}}">{{$product_group->name}}</a>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                @yield('buttons')
            </div>
        </div>
        @if(count($menu) > 1)
            <ul class="nav mt-0 border-bottom">
                @foreach($menu as $key => $value)
                    <li class="nav-item">
                        <a class="nav-link px-3 {{ $tab === $key ? 'active fw-bold border-bottom border-primary text-primary' : 'text-dark' }}"
                           href="{{ route('client.web.panel.cloud.service', ['uuid' => $service->uuid, 'tab' => $key]) }}">
                            {{ $value['name'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection

@section('js')
    @parent
@endsection

