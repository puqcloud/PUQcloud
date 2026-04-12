@extends(config('template.client.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent

@endsection

@section('content')
    @php
        $template = $product_group->proration_template ?: 'default';
        $serviceViewBase = resolveServiceViewBase('proration', $template);
        $viewPath = $serviceViewBase . '.' . $template;

        if (!view()->exists($viewPath)) {
            $serviceViewBase = config('template.client.view') . '.service_views.proration.default';
            $viewPath = $serviceViewBase . '.default';
        }
    @endphp

    @include($viewPath)

@endsection

@section('js')
    @parent

@endsection
