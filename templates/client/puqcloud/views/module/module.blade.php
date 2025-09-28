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

@if(!empty($data['blade']))
    {!! \Illuminate\Support\Facades\View::file($data['blade'], $data['variables'])->render() !!}
@endif

@section('js')
    @parent
@endsection
