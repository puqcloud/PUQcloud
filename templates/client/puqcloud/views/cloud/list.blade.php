@extends(config('template.client.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent

@endsection

@section('content')
    @php
        $basePath = config('template.client.view') . '.service_views.list.';
        $template = $product_group->list_template ?: 'default';
        $viewPath = $basePath . $template . '.' . $template;

        if (!view()->exists($viewPath)) {
            $viewPath = $basePath . 'default.default';
        }
    @endphp

    @include($viewPath)

@endsection

@section('js')
    @parent

@endsection
