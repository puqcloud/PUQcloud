@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('finance-edit'))
        <button id="save" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
            <i class="fa fa-save"></i> {{__('main.Save')}}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.home_companies.home_company_header')
    <form id="home_company" class="mx-auto" novalidate>
        <input type="hidden" class="form-control" id="name" name="name">

        <div class="card mb-4 shadow-sm">
            <div class="card-header">
                {{ __('main.Email setting') }}
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-12 col-sm-3 col-md-6 col-lg-3 col-xl-3">
                        <label for="group_uuid" class="form-label">{{ __('main.Group') }}</label>
                        <select name="group_uuid" id="group_uuid" class="form-select"></select>
                    </div>

                    <div class="col-12">
                        <div class="border rounded bg-light p-3">
                            <div class="mb-3">
                                <label class="form-label"
                                       for="signature">{{ __('main.Signature') }}</label>
                                <textarea class="form-control" id="signature" name="signature"
                                          rows="3"></textarea>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            $(".input-mask-trigger").inputmask();

            function loadFormData() {
                blockUI('home_company');
                const $form = $('#home_company');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.home_company.get', $uuid)}}', {}, 1500, null, 'GET')
                    .then(function (response) {
                        $.each(response.data, function (key, value) {
                            const $element = $form.find(`[name="${key}"]`);
                            if ($element.length) {

                                if ($element.is('textarea')) {
                                    if (value !== null) {
                                        $element.val(value);
                                    }
                                    if (key === 'signature') {
                                        $element.textareaAutoSize().trigger('autosize');
                                    }
                                    return;
                                }

                                $element.val(value);
                            }
                        });

                        var $element_group = $("#group_uuid");
                        initializeSelect2($element_group, '{{route('admin.api.groups.select.get')}}', response.data.group_data, 'GET', 1000, {});

                        if (response.data) {
                            unblockUI('home_company');
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#home_company");
                event.preventDefault();

                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.home_company.put', $uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            loadFormData();
        });
    </script>
@endsection
