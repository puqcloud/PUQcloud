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

    <div id="mainCard" class="main-card mb-3 card">
        <div class="card-body">
            <form id="payment_gateway" class="col-md-10 mx-auto" novalidate="novalidate">
                <div class="row">
                    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
                        <label class="form-label" for="key">{{__('main.Key')}}</label>
                        <div>
                            <input type="text" class="form-control input-mask-trigger" id="key" name="key"
                                   placeholder="{{__('main.Key')}}">
                        </div>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
                        <div class="position-relative mb-3">
                            <div>
                                <label for="currencies" class="form-label">{{__('main.Currencies')}}</label>
                                <select multiple name="currencies" id="currencies"
                                        class="form-select mb-2 form-control"></select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3" id="filepond-container"></div>

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
                                <div class="col-12 col-sm-12 col-md-6 col-lg-6 mb-1">
                                    <label class="form-label" for="name">{{__('main.Name')}}</label>
                                    <div>
                                        <input type="text" class="form-control input-mask-trigger"
                                               id="name"
                                               name="name"
                                               placeholder="{{__('main.Name')}}">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-12 col-md-6 col-lg-6 mb-1">
                                    <label class="form-label"
                                           for="description">{{__('main.Description')}}</label>
                                    <div>
                                        <input type="text" class="form-control input-mask-trigger"
                                               id="description"
                                               name="description"
                                               placeholder="{{__('main.Description')}}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>
                <div id="module_html"></div>
            </form>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            FilePond.registerPlugin(FilePondPluginImagePreview);

            function loadFormData(locale) {
                blockUI('payment_gateway');
                const $form = $('#payment_gateway');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.payment_gateway.get',request()->get('edit'))}}?locale=' + locale, {}, 50, null, 'GET')
                    .then(function (response) {

                        renderImageFields(response.data.images, 'col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-4 col-xxl-4');

                        $.each(response.data, function (key, value) {
                            const $element = $form.find(`[name="${key}"]`);
                            if ($element.length) {
                                if ($element.is('select')) {
                                    if (key === 'currencies') {
                                        var currencies_selected = response.data[key + '_data'];
                                        initializeSelect2($element[0], '{{route('admin.api.currencies.select.get')}}', currencies_selected, 'GET', 1000, {});
                                    }
                                    return;
                                }
                                $element.val(value);
                            }
                        });
                        $('#module_html').html(response.data.module_html);
                        unblockUI('payment_gateway');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#payment_gateway");
                event.preventDefault();

                const locale = $('.locale.active').data('locale');
                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.payment_gateway.put', request()->get('edit'))}}?locale=' + locale, formData, 5000, $(this), 'PUT', $form)
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
