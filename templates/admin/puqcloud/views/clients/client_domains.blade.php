@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')
    @include(config('template.admin.view') .'.clients.client_header')
    Domains
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

        });
    </script>
@endsection

