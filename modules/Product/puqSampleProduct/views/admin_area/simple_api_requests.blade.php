@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('content')
    <h1>{{ $title }}</h1>
    <button id="get" type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
        <i class="fa fa-download"></i> {{__('Product.puqSampleProduct.GET Request')}}
    </button>
    <button id="post" type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-primary">
        <i class="fa fa-upload"></i> {{__('Product.puqSampleProduct.POST Request')}}
    </button>
    <button id="put" type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-warning">
        <i class="fa fa-edit"></i> {{__('Product.puqSampleProduct.PUT Request')}}
    </button>
    <button id="delete" type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-danger">
        <i class="fa fa-trash"></i> {{__('Product.puqSampleProduct.DELETE Request')}}
    </button>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            $('#get').on('click', function () {
                PUQajax('{{route('admin.api.Product.puqSampleProduct.simple_api_request.get')}}', null, 5000, $(this), 'GET', null);
            });
            $('#post').on('click', function () {
                PUQajax('{{route('admin.api.Product.puqSampleProduct.simple_api_request.post')}}', {data: 'sample_data'}, 5000, $(this), 'POST', null);
            });
            $('#put').on('click', function () {
                PUQajax('{{route('admin.api.Product.puqSampleProduct.simple_api_request.put')}}', {data: 'sample_data'}, 5000, $(this), 'PUT', null);
            });
            $('#delete').on('click', function () {
                PUQajax('{{route('admin.api.Product.puqSampleProduct.simple_api_request.delete')}}', null, 5000, $(this), 'DELETE', null);
            });
        });
    </script>
@endsection
