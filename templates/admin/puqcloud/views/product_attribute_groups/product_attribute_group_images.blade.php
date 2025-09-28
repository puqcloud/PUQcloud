@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')
    @include(config('template.admin.view') .'.product_attribute_groups.product_attribute_group_header')
    <div class="row" id="filepond-container"></div>
@endsection

@section('js')
    @parent

    <script>
        $(document).ready(function () {

            FilePond.registerPlugin(FilePondPluginImagePreview);

            function loadFormData() {
                PUQajax('{{ route('admin.api.product_attribute_group.get', $uuid) }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        if (response.data) {
                            renderImageFields(response.data.images, 'col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-4 col-xxl-4');
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            loadFormData();
        });
    </script>

@endsection
