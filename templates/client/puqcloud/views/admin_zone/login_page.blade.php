@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')

    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="pe-7s-paint icon-gradient bg-arielle-smile"></i>
                </div>
                <div> {{ __('client_template.Login Page') }}
                    <small class="text-muted"></small></div>
            </div>
            <div class="page-title-actions">
                <button id="save" type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                    <i class="fa fa-save"></i> {{__('main.Save')}}
                </button>
            </div>
        </div>
    </div>

    <div class="theme-settings__options-wrapper">

        <form id="login_page">

            <input type="hidden" id="client_area_login_page_header_color_scheme" name="client_area_login_page_header_color_scheme"
                   value="{{ setting('clientAreaLoginPageHeaderColorScheme') }}">

            <div class="main-card mb-3 card">
                <div class="card-header">
                    {{__('client_template.Login Page')}}
                </div>
                <div class="card-body">
                    <div class="row" id="filepond-container"></div>
                </div>
            </div>
        </form>

        <div class="row">
            <div class="col-md-6 col-12">
                <div class="main-card mb-3 card">
                    <div class="card-header {{ setting('clientAreaLoginPageHeaderColorScheme') }}"
                         id="header_color">
                        {{__('client_template.Header Options')}}
                        <div class="btn-actions-pane-right">
                            <button type="button"
                                    class="btn-pill btn-shadow btn-wide ms-auto btn btn-focus btn-sm switch-header-cs-class"
                                    data-class="">
                                {{__('client_template.Restore Default')}}
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item">
                                <h5 class="pb-2">{{__('client_template.Choose Color Scheme')}}</h5>
                                <div class="theme-settings-swatches">
                                    <div class="swatch-holder bg-primary switch-header-cs-class"
                                         data-class="bg-primary header-text-light"></div>
                                    <div class="swatch-holder bg-secondary switch-header-cs-class"
                                         data-class="bg-secondary header-text-light"></div>
                                    <div class="swatch-holder bg-success switch-header-cs-class"
                                         data-class="bg-success header-text-light"></div>
                                    <div class="swatch-holder bg-info switch-header-cs-class"
                                         data-class="bg-info header-text-light"></div>
                                    <div class="swatch-holder bg-warning switch-header-cs-class"
                                         data-class="bg-warning header-text-dark"></div>
                                    <div class="swatch-holder bg-danger switch-header-cs-class"
                                         data-class="bg-danger header-text-light"></div>
                                    <div class="swatch-holder bg-light switch-header-cs-class"
                                         data-class="bg-light header-text-dark"></div>
                                    <div class="swatch-holder bg-dark switch-header-cs-class"
                                         data-class="bg-dark header-text-light"></div>
                                    <div class="swatch-holder bg-focus switch-header-cs-class"
                                         data-class="bg-focus header-text-light"></div>
                                    <div class="swatch-holder bg-alternate switch-header-cs-class"
                                         data-class="bg-alternate header-text-light"></div>
                                    <div class="divider"></div>
                                    <div class="swatch-holder bg-vicious-stance switch-header-cs-class"
                                         data-class="bg-vicious-stance header-text-light"></div>
                                    <div class="swatch-holder bg-midnight-bloom switch-header-cs-class"
                                         data-class="bg-midnight-bloom header-text-light"></div>
                                    <div class="swatch-holder bg-night-sky switch-header-cs-class"
                                         data-class="bg-night-sky header-text-light"></div>
                                    <div class="swatch-holder bg-slick-carbon switch-header-cs-class"
                                         data-class="bg-slick-carbon header-text-light"></div>
                                    <div class="swatch-holder bg-asteroid switch-header-cs-class"
                                         data-class="bg-asteroid header-text-light"></div>
                                    <div class="swatch-holder bg-royal switch-header-cs-class"
                                         data-class="bg-royal header-text-light"></div>
                                    <div class="swatch-holder bg-warm-flame switch-header-cs-class"
                                         data-class="bg-warm-flame header-text-dark"></div>
                                    <div class="swatch-holder bg-night-fade switch-header-cs-class"
                                         data-class="bg-night-fade header-text-dark"></div>
                                    <div class="swatch-holder bg-sunny-morning switch-header-cs-class"
                                         data-class="bg-sunny-morning header-text-dark"></div>
                                    <div class="swatch-holder bg-tempting-azure switch-header-cs-class"
                                         data-class="bg-tempting-azure header-text-dark"></div>
                                    <div class="swatch-holder bg-amy-crisp switch-header-cs-class"
                                         data-class="bg-amy-crisp header-text-dark"></div>
                                    <div class="swatch-holder bg-heavy-rain switch-header-cs-class"
                                         data-class="bg-heavy-rain header-text-dark"></div>
                                    <div class="swatch-holder bg-mean-fruit switch-header-cs-class"
                                         data-class="bg-mean-fruit header-text-dark"></div>
                                    <div class="swatch-holder bg-malibu-beach switch-header-cs-class"
                                         data-class="bg-malibu-beach header-text-light"></div>
                                    <div class="swatch-holder bg-deep-blue switch-header-cs-class"
                                         data-class="bg-deep-blue header-text-dark"></div>
                                    <div class="swatch-holder bg-ripe-malin switch-header-cs-class"
                                         data-class="bg-ripe-malin header-text-light"></div>
                                    <div class="swatch-holder bg-arielle-smile switch-header-cs-class"
                                         data-class="bg-arielle-smile header-text-light"></div>
                                    <div class="swatch-holder bg-plum-plate switch-header-cs-class"
                                         data-class="bg-plum-plate header-text-light"></div>
                                    <div class="swatch-holder bg-happy-fisher switch-header-cs-class"
                                         data-class="bg-happy-fisher header-text-dark"></div>
                                    <div class="swatch-holder bg-happy-itmeo switch-header-cs-class"
                                         data-class="bg-happy-itmeo header-text-light"></div>
                                    <div class="swatch-holder bg-mixed-hopes switch-header-cs-class"
                                         data-class="bg-mixed-hopes header-text-light"></div>
                                    <div class="swatch-holder bg-strong-bliss switch-header-cs-class"
                                         data-class="bg-strong-bliss header-text-light"></div>
                                    <div class="swatch-holder bg-grow-early switch-header-cs-class"
                                         data-class="bg-grow-early header-text-light"></div>
                                    <div class="swatch-holder bg-love-kiss switch-header-cs-class"
                                         data-class="bg-love-kiss header-text-light"></div>
                                    <div class="swatch-holder bg-premium-dark switch-header-cs-class"
                                         data-class="bg-premium-dark header-text-light"></div>
                                    <div class="swatch-holder bg-happy-green switch-header-cs-class"
                                         data-class="bg-happy-green header-text-light"></div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-12">

            </div>
        </div>

    </div>
@endsection

@section('js')
    @parent

    <script>
        $(document).ready(() => {

            FilePond.registerPlugin(FilePondPluginImagePreview);
            window.translations['Favicon'] = '{{__('client_template.Favicon')}}';
            window.translations['Logo'] = '{{__('client_template.Logo')}}';
            window.translations['Background'] = '{{__('client_template.Background')}}';

            function loadFormData() {
                PUQajax('{{ route('admin.api.client_template.login_layout_options.get') }}', {}, 50, null, 'GET')
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

            $('.switch-header-cs-class[data-class]').on('click', function () {
                const newClass = $(this).data('class');
                $('#header_color').attr('class', `card-header ${newClass}`);
                $('#client_area_login_page_header_color_scheme').val(newClass);
            });

            $('.switch-header-cs-class[data-class=""]').on('click', function () {
                $('#header_color').attr('class', 'card-header');
                $('#client_area_login_page_header_color_scheme').val('');
            });

            $("#save").on("click", function (event) {
                const $form = $("#login_page");
                event.preventDefault();
                const $button = $(this);

                serializeForm($form, true).then(function (formData) {
                    PUQajax(
                        '{{route('admin.api.client_template.login_layout_options.put')}}',
                        formData,
                        1000,
                        $button,
                        'PUT',
                        $form
                    );
                });
            });


        });
    </script>
@endsection
