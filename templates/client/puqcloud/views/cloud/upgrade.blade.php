@extends(config('template.client.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent

@endsection

@section('content')
    @php
        $template = $product_group->upgrade_template ?: 'default';
        $serviceViewBase = resolveServiceViewBase('upgrade', $template);
        $viewPath = $serviceViewBase . '.' . $template;

        if (!view()->exists($viewPath)) {
            $serviceViewBase = config('template.client.view') . '.service_views.upgrade.default';
            $viewPath = $serviceViewBase . '.default';
        }
    @endphp

    @include($viewPath)

@endsection

@section('js')
    @parent

@endsection
