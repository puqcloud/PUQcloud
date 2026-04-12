@extends(config('template.client.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent

@endsection

@section('content')
    @if(!empty($menu[$tab]['template']))
        {!! \Illuminate\Support\Facades\View::file($menu[$tab]['template'], $service->getModuleVariables($tab))->render() !!}
    @endif

    @php
        $template = $product_group->manage_template ?: 'default';
        $serviceViewBase = resolveServiceViewBase('manage', $template);
        $viewPath = $serviceViewBase . '.' . $template;

        if (!view()->exists($viewPath)) {
            $serviceViewBase = config('template.client.view') . '.service_views.manage.default';
            $viewPath = $serviceViewBase . '.default';
        }
    @endphp
    @include($viewPath)
@endsection

@section('js')
    @parent

@endsection
