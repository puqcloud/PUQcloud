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

        {{-- Localized Content --}}
        <div class="card mb-3 product-section-card">
            <div class="card-header">
                <i class="fa fa-language"></i>
                <h5>{{__('main.Localized Content')}}</h5>
            </div>
            <div class="tabs-lg-alternate card-header p-0">
                <ul class="nav nav-justified">
                    @php($i=0)
                    @foreach($locales as $key => $locale)
                        <li class="nav-item">
                            <a data-bs-toggle="tab" href="#tab-{{$i}}"
                               class="nav-link locale @if($i === 0) active @endif"
                               data-locale="{{ $key }}">
                                <div class="widget-number">
                                    <div class="fi fi-{{$locale['flag']}} large mx-auto"></div>
                                </div>
                                <div class="tab-subheading">{{$locale['name']}}</div>
                            </a>
                        </li>
                        @php($i++)
                    @endforeach
                </ul>
            </div>
            <div class="tab-content">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <div class="mb-3">
                                <label class="form-label" for="name">{{__('main.Name')}}</label>
                                <input type="text" class="form-control input-mask-trigger" id="name"
                                       name="name" placeholder="{{__('main.Name')}}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="short_description">{{__('main.Short Description')}}</label>
                                <input type="text" class="form-control input-mask-trigger"
                                       id="short_description" name="short_description"
                                       placeholder="{{__('main.Short Description')}}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label for="description" class="form-label">{{__('main.Description')}}</label>
                            <textarea name="description" id="description" class="form-control" rows="6"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="card mb-3 product-section-card">
            <div class="card-header">
                <i class="fa fa-sticky-note"></i>
                <h5>{{__('main.Notes')}}</h5>
            </div>
            <div class="card-body">
                <textarea name="notes" id="notes" class="form-control" rows="3"
                          placeholder="{{__('main.Internal notes visible to admins only')}}"></textarea>
            </div>
        </div>

    </form>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            $(".input-mask-trigger").inputmask();

            function loadFormData(locale) {
                blockUI('product_group');
                const $form = $('#product_group');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.product_group.get', $uuid)}}?locale=' + locale, {}, 50, null, 'GET')
                    .then(function (response) {
                        $.each(response.data, function (key, value) {
                            const $element = $form.find(`[name="${key}"]`);
                            if (!$element.length) return;

                            if ($element.is('textarea')) {
                                if (value !== null) {
                                    $element.val(value);
                                }
                                if (key === 'description') {
                                    $element.textareaAutoSize().trigger('autosize');
                                }
                                return;
                            }

                            $element.val(value);
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

                const locale = $('.locale.active').data('locale');
                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.product_group.put', $uuid)}}?locale=' + locale, formData, 5000, $(this), 'PUT', $form)
                    .then(function () {
                        loadFormData(locale);
                    });
            });

            loadFormData($('.locale.active').data('locale'));

            $('.locale').on('click', function () {
                const locale = $(this).data('locale');
                loadFormData(locale);
            });
        });
    </script>
@endsection
