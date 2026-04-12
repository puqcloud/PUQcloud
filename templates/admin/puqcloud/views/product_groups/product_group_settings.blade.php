@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
    <style>
        .product-section-card .card-header {
            background: linear-gradient(90deg, rgba(13, 110, 253, 0.04), transparent);
            border-bottom: 1px solid rgba(0, 0, 0, 0.075);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .product-section-card .card-header i {
            color: #0d6efd;
        }

        .product-section-card .card-header h5 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .toggle-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 0.5rem;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .toggle-card:hover {
            border-color: rgba(13, 110, 253, 0.3);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .toggle-card .form-check {
            margin: 0;
            padding: 0;
            min-height: 0;
        }

        .toggle-card .form-check-input {
            width: 2.75em;
            height: 1.5em;
            margin: 0;
            cursor: pointer;
            flex-shrink: 0;
        }

        .toggle-card .toggle-info {
            flex: 1;
            min-width: 0;
        }

        .toggle-card .toggle-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #212529;
        }

        .toggle-card .toggle-hint {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.1rem;
        }

        .toggle-card.danger-toggle .form-check-input:checked {
            background-color: #dc3545;
            border-color: #dc3545;
        }
    </style>
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('product-groups-management'))
        <button id="save" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
            <i class="fa fa-save"></i> {{__('main.Save')}}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.product_groups.product_group_header')

    <form id="product_group" class="mx-auto" novalidate="novalidate">

        {{-- Basic Information --}}
        <div class="card mb-3 product-section-card">
            <div class="card-header">
                <i class="fa fa-cube"></i>
                <h5>{{__('main.Basic Information')}}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="key">{{__('main.Key')}}</label>
                        <input type="text" class="form-control input-mask-trigger"
                               id="key" name="key" placeholder="{{__('main.Key')}}">
                        <small class="form-text text-muted">{{__('main.Internal identifier')}}</small>
                    </div>

                    <div class="col-12 col-md-4">
                        @include(config('template.admin.view') .'.elements.icon_picker')
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="toggle-card danger-toggle">
                            <div class="toggle-info">
                                <div class="toggle-label">{{__('main.Hidden')}}</div>
                                <div class="toggle-hint">{{__('main.Hide from client area')}}</div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       id="hidden" name="hidden" value="1">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Service View Templates --}}
        <div class="card mb-3 product-section-card">
            <div class="card-header">
                <i class="fa fa-layer-group"></i>
                <h5>{{__('main.Service View Templates')}}</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    {{__('main.Templates that control how services in this group render in the client area')}}
                </p>
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-lg-4 col-xl">
                        <label for="list_template" class="form-label">
                            <i class="fa fa-list text-primary me-1"></i>
                            {{__('main.List Template')}}
                        </label>
                        <select name="list_template" id="list_template" class="form-select"></select>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-4 col-xl">
                        <label for="order_template" class="form-label">
                            <i class="fa fa-shopping-cart text-primary me-1"></i>
                            {{__('main.Order Template')}}
                        </label>
                        <select name="order_template" id="order_template" class="form-select"></select>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-4 col-xl">
                        <label for="manage_template" class="form-label">
                            <i class="fa fa-cogs text-primary me-1"></i>
                            {{__('main.Manage Template')}}
                        </label>
                        <select name="manage_template" id="manage_template" class="form-select"></select>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-4 col-xl">
                        <label for="upgrade_template" class="form-label">
                            <i class="fa fa-arrow-up text-primary me-1"></i>
                            {{__('main.Upgrade Template')}}
                        </label>
                        <select name="upgrade_template" id="upgrade_template" class="form-select"></select>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-4 col-xl">
                        <label for="proration_template" class="form-label">
                            <i class="fa fa-calendar-alt text-primary me-1"></i>
                            {{__('main.Proration Template')}}
                        </label>
                        <select name="proration_template" id="proration_template" class="form-select"></select>
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

            // Template dropdowns — each initialized lazily from a select2 AJAX source.
            const templateFields = [
                { name: 'list_template',      route: '{{route('admin.api.product_group.list_templates.select.get')}}' },
                { name: 'order_template',     route: '{{route('admin.api.product_group.order_templates.select.get')}}' },
                { name: 'manage_template',    route: '{{route('admin.api.product_group.manage_templates.select.get')}}' },
                { name: 'upgrade_template',   route: '{{route('admin.api.product_group.upgrade_templates.select.get')}}' },
                { name: 'proration_template', route: '{{route('admin.api.product_group.proration_templates.select.get')}}' },
            ];

            function loadFormData() {
                blockUI('product_group');
                const $form = $('#product_group');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.product_group.get', $uuid)}}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $.each(response.data, function (key, value) {
                            const $element = $form.find(`[name="${key}"]`);
                            if (!$element.length) return;

                            if ($element.is(':checkbox')) {
                                $element.prop('checked', !!value).trigger('change');
                                return;
                            }

                            // Template select2 fields are initialized below in a single pass.
                            if (templateFields.some(f => f.name === key)) return;

                            $element.val(value);
                        });

                        // Initialize every template dropdown with its current value as preselected,
                        // or null if the value is missing (fresh record). One call per field keeps
                        // the logic simple and avoids the "might not be initialized" race the old
                        // code had.
                        templateFields.forEach(function (field) {
                            const current = response.data[field.name];
                            const $el = $form.find(`[name="${field.name}"]`);
                            if (!$el.length) return;
                            initializeSelect2(
                                $el[0],
                                field.route,
                                current ? {id: current, text: current} : null,
                                'GET', 1000, {}
                            );
                        });

                        unblockUI('product_group');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                        unblockUI('product_group');
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#product_group");
                event.preventDefault();

                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.product_group.put', $uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function () {
                        loadFormData();
                    });
            });

            loadFormData();
        });
    </script>
@endsection
