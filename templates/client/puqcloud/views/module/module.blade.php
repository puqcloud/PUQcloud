@extends(config('template.client.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')
    @if(!empty($data['blade']))
        {!! \Illuminate\Support\Facades\View::file($data['blade'], $data['variables'])->render() !!}
    @endif
@endsection

@section('js')
    @parent
@endsection
