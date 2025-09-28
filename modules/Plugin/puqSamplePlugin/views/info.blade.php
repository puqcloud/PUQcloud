@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('content')
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center">
            @php
                $logoPath = $config['logo'];
                $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
            @endphp

            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo" class="me-3" style="width: 50px; height: 50px;">
            @else
                <div class="me-3" style="width: 50px; height: 50px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                    <i class="{{ $config['icon'] }}" style="font-size: 24px;"></i>
                </div>
            @endif

            <h4 class="mb-0">{{ $config['name'] }}</h4>
        </div>
        <div class="card-body">
            <p class="text-muted">{{ $config['description'] }}</p>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <strong>{{__('Plugin.puqSamplePlugin.Version')}}:</strong> {{ $config['version'] }}
                </li>
                <li class="list-group-item">
                    <strong>{{__('Plugin.puqSamplePlugin.Author')}}:</strong> {{ $config['author'] }}
                </li>
                <li class="list-group-item">
                    <strong>{{__('Plugin.puqSamplePlugin.Email')}}:</strong>
                    <a href="mailto:{{ $config['email'] }}">{{ $config['email'] }}</a>
                </li>
                <li class="list-group-item">
                    <strong>{{__('Plugin.puqSamplePlugin.Website')}}:</strong>
                    <a href="{{ $config['website'] }}" target="_blank">{{ $config['website'] }}</a>
                </li>
            </ul>
        </div>
    </div>
@endsection

@section('js')
    @parent
@endsection
