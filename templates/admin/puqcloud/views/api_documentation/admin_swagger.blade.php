@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')
    <div id="swagger-ui"></div>
@endsection

@section('js')
    @parent
    <script>
        window.initSwagger("{{ route('admin.api.api_documentation.admin.swagger.get', ['module'=>$module,'name'=>$name]) }}", "#swagger-ui");
    </script>
@endsection
