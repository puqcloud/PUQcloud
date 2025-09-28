@extends(config('template.client.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent

@endsection

@section('content')
    @parent
@endsection

@if(!empty($menu[$tab]['template']))
    {!! \Illuminate\Support\Facades\View::file($menu[$tab]['template'], $service->getModuleVariables($tab))->render() !!}
@endif

    @php
        $basePath = config('template.client.view') . '.service_views.manage.';
        $template = $product_group->manage_template ?: 'default';
        $viewPath = $basePath . $template . '.' . $template;

        if (!view()->exists($viewPath)) {
            $viewPath = $basePath . 'default.default';
        }
    @endphp
    @include($viewPath)


@section('js')
    @parent

@endsection
