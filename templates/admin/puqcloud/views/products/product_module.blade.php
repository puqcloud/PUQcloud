@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('products-management'))
        <button id="save" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
            <i class="fa fa-save"></i> {{__('main.Save')}}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.products.product_header')
    <form id="module" class="mx-auto" novalidate="novalidate">
        <div class="card mb-1">
            <div class="card-body">
                <label class="form-label" for="key">{{__('main.Module')}}</label>
                <div style="width: 500px;">
                    <select name="module_uuid" id="module_uuid" class="form-select mb-2 form-control"></select>
                </div>
            </div>
        </div>

        <div class="card mb-1">
            <div class="card-body">
                <div id="module_html"></div>
            </div>
        </div>

    </form>
@endsection

@section('js')
    @parent
    <script>

        function loadModulesSelect() {
            const $module_select = $("#module_uuid");
            PUQajax(`{{ route('admin.api.product.module.select.get', $uuid) }}`, {}, 1000, null, 'GET')
                .then(response => {
                    const results = response.data.results;
                    const selectedId = response.data.selected;
                    const selectedModule = results.find(m => m.id === selectedId);

                    initializeSelect2(
                        $module_select,
                        '{{ route('admin.api.product.module.select.get', $uuid) }}',
                        selectedModule,
                        'GET',
                        1000
                    );

                    if (selectedModule) {
                        const newOption = new Option(selectedModule.text, selectedModule.id, true, true);
                        $module_select.append(newOption).trigger('change');
                    }
                })
                .catch(error => {
                    console.error(error);
                });
        }

        function loadModuleHtml(){
            blockUI('module');
            PUQajax('{{route('admin.api.product.module.get',$uuid)}}', {}, 50, null, 'GET')
                .then(function (response) {
                    $('#module_html').html(response.data);
                    unblockUI('module');
                })
                .catch(function (error) {
                    console.error('Error loading form data:', error);
                    unblockUI('module');
                });
        }

        loadModulesSelect();
        loadModuleHtml();

        $("#save").on("click", function (event) {
            const $form = $("#module");
            event.preventDefault();
            const formData = serializeForm($form);
            PUQajax('{{route('admin.api.product.module.put', $uuid)}}', formData, 5000, $(this), 'PUT', $form)
                .then(function (response) {
                    loadModulesSelect();
                    loadModuleHtml();
                });
        });

    </script>
@endsection
