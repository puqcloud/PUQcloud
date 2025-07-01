@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
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
    @include(config('template.admin.view') .'.product_attribute_groups.product_attribute_group_header')

    <form id="product_attribute_group" class="mx-auto" novalidate="novalidate">

        <div class="card mb-3">
            <div class="card-body">
                <div class="row">

                    <div class="col-12 col-sm-6 col-md-6 col-lg mb-1">
                        <label class="form-label" for="key">{{__('main.Key')}}</label>
                        <div>
                            <input type="text" class="form-control input-mask-trigger"
                                   id="key"
                                   name="key"
                                   placeholder="{{__('main.Key')}}">
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-6 col-lg mb-1">
                        <label class="form-label" for="disable">{{__('main.Hidden')}}</label>
                        <div>
                            <input type="checkbox" data-toggle="toggle" data-on="{{__('main.Yes')}}"
                                   id="hidden"
                                   name="hidden"
                                   data-off="{{__('main.No')}}" data-onstyle="danger"
                                   data-offstyle="success">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="tabs-lg-alternate card-header">
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
            <div class="tab-content mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="row">
                            <div class="col-12 col-sm-12 col-md-12 col-lg-6 mb-1">
                                <label class="form-label" for="name">{{__('main.Name')}}</label>
                                <div>
                                    <input type="text" class="form-control input-mask-trigger"
                                           id="name"
                                           name="name"
                                           placeholder="{{__('main.Name')}}">
                                </div>

                                <label class="form-label"
                                       for="short_description">{{__('main.Short Description')}}</label>
                                <div>
                                    <input type="text" class="form-control input-mask-trigger"
                                           id="short_description"
                                           name="short_description"
                                           placeholder="{{__('main.Short Description')}}">
                                </div>

                            </div>
                            <div class="col-12 col-sm-12 col-md-12 col-lg-6 mb-1">
                                <div class="form-group">
                                    <label for="description">{{__('main.Description')}}</label>
                                    <textarea name="description" id="description" class="form-control" rows="5"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-12 mb-1">
                        <label class="form-label" for="notes">{{__('main.Notes')}}</label>
                        <div><textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
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

            function loadFormData(locale) {
                blockUI('product_attribute_group');
                const $form = $('#product_attribute_group');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.product_attribute_group.get', $uuid)}}?locale=' + locale, {}, 50, null, 'GET')
                    .then(function (response) {
                        $.each(response.data, function (key, value) {
                            const $element = $form.find(`[name="${key}"]`);
                            if ($element.length) {

                                if ($element.is(':checkbox')) {
                                    $element.prop('checked', !!value).trigger('click');
                                    return;
                                }

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
                            }
                        });

                        if (response.data) {
                            unblockUI('product_attribute_group');
                        }


                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#product_attribute_group");
                event.preventDefault();

                const locale = $('.locale.active').data('locale');
                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.product_attribute_group.put', $uuid)}}?locale=' + locale, formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
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
