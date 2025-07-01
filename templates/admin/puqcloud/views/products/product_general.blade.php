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

    <form id="product" class="mx-auto" novalidate="novalidate">

        <div class="card mb-3">

            <div class="card-body">
                <div class="row">

                    <div class="col-12 col-sm-12 col-md-3 col-lg">
                        <label class="form-label" for="key">{{__('main.Key')}}</label>
                        <div>
                            <input type="text" class="form-control input-mask-trigger"
                                   id="key"
                                   name="key"
                                   placeholder="{{__('main.Key')}}">
                        </div>
                    </div>

                    <div class="col-12 col-sm-12 col-md-2 col-lg">
                        <label class="form-label" for="cancellation_delay_hours">{{__('main.Cancellation Delay')}}</label>
                            <div class="input-group">
                                <div class="input-group-text">
                                    <div class="">
                                        <i class="fa fa-calendar-alt"></i>
                                    </div>
                                </div>
                                <input class="form-control input-mask-trigger"
                                       id="cancellation_delay_hours"
                                       name="cancellation_delay_hours"
                                       data-inputmask="'alias': 'integer', 'groupSeparator': ',', 'autoGroup': true" im-insert="true"
                                       inputmode="numeric"
                                       style="text-align: right;">
                            </div>
                            <small class="form-text text-muted">
                                {{__('main.Hours')}}
                            </small>
                    </div>

                    <div class="col-12 col-sm-12 col-md-2 col-lg">
                        <label class="form-label" for="termination_delay_hours">{{__('main.Termination Delay')}}</label>
                        <div class="input-group">
                            <div class="input-group-text">
                                <div class="">
                                    <i class="fa fa-calendar-alt"></i>
                                </div>
                            </div>
                            <input class="form-control input-mask-trigger"
                                   id="termination_delay_hours"
                                   name="termination_delay_hours"
                                   data-inputmask="'alias': 'integer', 'groupSeparator': ',', 'autoGroup': true" im-insert="true"
                                   inputmode="numeric"
                                   style="text-align: right;">
                        </div>
                        <small class="form-text text-muted">
                            {{__('main.Hours')}}
                        </small>
                    </div>

                    <div class="col-12 col-sm-3 col-md-3 col-lg-2 col-xl-2">
                        <label class="form-label" for="stock_control">{{__('main.Stock Control')}}</label>
                        <div class="d-flex gap-1">
                            <input type="checkbox" data-toggle="toggle" data-on="{{__('main.Yes')}}"
                                   id="stock_control"
                                   name="stock_control"
                                   data-off="{{__('main.No')}}"
                                   data-onstyle="info"
                                   data-offstyle="success">
                            <input id="quantity" name="quantity" type="number" min="0" step="1"
                                   class="form-control rounded-start-0">
                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-12 col-sm-3 col-md-3 col-lg-2 col-xl-2">
                        <label class="form-label" for="hidden">{{__('main.Hourly Billing')}}</label>
                        <div>
                            <input type="checkbox" data-toggle="toggle" data-on="{{__('main.Yes')}}"
                                   id="hourly_billing"
                                   name="hourly_billing"
                                   data-off="{{__('main.No')}}"
                                   data-onstyle="info"
                                   data-offstyle="success">
                        </div>
                    </div>

                    <div class="col-12 col-sm-3 col-md-3 col-lg-2 col-xl-2">
                        <label class="form-label" for="hidden">{{__('main.Allow Idle')}}</label>
                        <div>
                            <input type="checkbox" data-toggle="toggle" data-on="{{__('main.Yes')}}"
                                   id="allow_idle"
                                   name="allow_idle"
                                   data-off="{{__('main.No')}}"
                                   data-onstyle="info"
                                   data-offstyle="success">
                        </div>
                    </div>

                    <div class="col-12 col-sm-3 col-md-3 col-lg-2 col-xl-2">
                        <label class="form-label" for="hidden">{{__('main.Convert Price')}}</label>
                        <div>
                            <input type="checkbox" data-toggle="toggle" data-on="{{__('main.Yes')}}"
                                   id="convert_price"
                                   name="convert_price"
                                   data-off="{{__('main.No')}}"
                                   data-onstyle="info"
                                   data-offstyle="success">
                        </div>
                    </div>

                    <div class="col-12 col-sm-3 col-md-3 col-lg-2 col-xl-2">
                        <label class="form-label" for="hidden">{{__('main.Hidden')}}</label>
                        <div>
                            <input type="checkbox" data-toggle="toggle" data-on="{{__('main.Yes')}}"
                                   id="hidden"
                                   name="hidden"
                                   data-off="{{__('main.No')}}"
                                   data-onstyle="danger"
                                   data-offstyle="success">
                        </div>
                    </div>

                    <div class="col-12 col-sm-3 col-md-3 col-lg-2 col-xl-2">
                        <label class="form-label" for="retired">{{__('main.Retired')}}</label>
                        <div>
                            <input type="checkbox" data-toggle="toggle" data-on="{{__('main.Yes')}}"
                                   id="retired"
                                   name="retired"
                                   data-off="{{__('main.No')}}"
                                   data-onstyle="danger"
                                   data-offstyle="success">
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="card mb-3 ">
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
                                <div class="mb-3">
                                    <label class="form-label" for="name">{{__('main.Name')}}</label>
                                    <div>
                                        <input type="text" class="form-control input-mask-trigger" id="name"
                                               name="name"
                                               placeholder="{{__('main.Name')}}">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"
                                           for="short_description">{{__('main.Short Description')}}</label>
                                    <div>
                                        <input type="text" class="form-control input-mask-trigger"
                                               id="short_description"
                                               name="short_description"
                                               placeholder="{{__('main.Short Description')}}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-12 col-md-12 col-lg-6 mb-1">
                                <div class="form-group">
                                    <label for="description">{{__('main.Description')}}</label>
                                    <textarea name="description" id="description" class="form-control"
                                              rows="5"></textarea>
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

            $(".input-mask-trigger").inputmask();

            function loadFormData(locale) {
                blockUI('product');
                const $form = $('#product');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.product.get', $uuid)}}?locale=' + locale, {}, 50, null, 'GET')
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
                            unblockUI('product');
                        }


                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#product");
                event.preventDefault();

                const locale = $('.locale.active').data('locale');
                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.product.put', $uuid)}}?locale=' + locale, formData, 5000, $(this), 'PUT', $form)
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
