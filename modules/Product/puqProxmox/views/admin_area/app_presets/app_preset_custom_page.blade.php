@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('header')

@endsection

@section('buttons')

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
            id="save">
        <i class="fa fa-save"></i>
        {{ __('Product.puqProxmox.Save') }}
    </button>

@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.app_presets.app_preset_header')

    <div id="container">
        <form id="customPageForm" method="POST" action="" novalidate="novalidate">
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
                        <div class="form-group">
                            <label for="custom_page">{{__('Product.puqProxmox.Custom Page')}}</label>
                            <textarea name="custom_page" id="custom_page" class="form-control" rows="20"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function loadFormData(locale) {
                blockUI('customPageForm');
                const $form = $('#customPageForm');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.Product.puqProxmox.app_preset.custom_page.get', $uuid)}}?locale=' + locale, {}, 50, null, 'GET')
                    .then(function (response) {
                        $("#custom_page").val(response.data?.custom_page);
                        unblockUI('customPageForm');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#customPageForm");
                event.preventDefault();

                const locale = $('.locale.active').data('locale');
                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.Product.puqProxmox.app_preset.custom_page.get', $uuid)}}?locale=' + locale, formData, 5000, $(this), 'PUT', $form)
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
