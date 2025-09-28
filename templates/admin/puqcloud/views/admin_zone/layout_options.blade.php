@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('content')

    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="pe-7s-paint icon-gradient bg-arielle-smile"></i>
                </div>
                <div> {{ __('admin_template.Layout Options') }}
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

        <form id="layout_options">
            <input type="hidden" id="header_color_scheme" name="header_color_scheme" value="{{ setting('layoutOptionHeaderColorScheme') }}">
            <input type="hidden" id="sidebar_color_scheme" name="sidebar_color_scheme" value="{{ setting('layoutOptionSidebarColorScheme') }}">
            <div class="main-card mb-3 card">
                <div class="card-header">
                    {{__('admin_template.Layout Options')}}
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="widget-content p-0">
                                <div class="widget-content-wrapper">
                                    <div class="widget-content-left me-3">
                                        <div class="switch has-switch switch-container-class" data-class="fixed-header">
                                            <div class="switch-animate switch-on">
                                                <input type="checkbox"
                                                       @if(!empty(setting('layoutOptionFixed_header')))
                                                           checked
                                                       @endif
                                                       id="fixed_header" name="fixed_header" data-toggle="toggle"
                                                       data-onstyle="success">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="widget-content-left">
                                        <div class="widget-heading">{{__('admin_template.Fixed Header')}}</div>
                                        <div
                                            class="widget-subheading">{{__('admin_template.Makes the header top fixed, always visible!')}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="widget-content p-0">
                                <div class="widget-content-wrapper">
                                    <div class="widget-content-left me-3">
                                        <div class="switch has-switch switch-container-class"
                                             data-class="fixed-sidebar">
                                            <div class="switch-animate switch-on">
                                                <input type="checkbox"
                                                       @if(!empty(setting('layoutOptionFixed_sidebar')))
                                                           checked
                                                       @endif
                                                       id="fixed_sidebar" name="fixed_sidebar"
                                                       data-toggle="toggle" data-onstyle="success">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="widget-content-left">
                                        <div class="widget-heading">{{__('admin_template.Fixed Sidebar')}}</div>
                                        <div
                                            class="widget-subheading">{{__('admin_template.Makes the sidebar left fixed, always visible!')}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="main-card mb-3 card">
            <div class="card-header">
                {{__('admin_template.Header Options')}}
                <div class="btn-actions-pane-right">
                    <button type="button"
                            class="btn-pill btn-shadow btn-wide ms-auto btn btn-focus btn-sm switch-header-cs-class"
                            data-class="">
                        {{__('admin_template.Restore Default')}}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <h5 class="pb-2">{{__('admin_template.Choose Color Scheme')}}</h5>
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


        <div class="main-card mb-3 card">
            <div class="card-header">
                {{__('admin_template.Sidebar Options')}}
                <div class="btn-actions-pane-right">
                    <button type="button"
                            class="btn-pill btn-shadow btn-wide ms-auto btn btn-focus btn-sm switch-sidebar-cs-class"
                            data-class="">
                        {{__('admin_template.Restore Default')}}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <h5 class="pb-2">{{__('admin_template.Choose Color Scheme')}}</h5>
                        <div class="theme-settings-swatches">
                            <div class="swatch-holder bg-primary switch-sidebar-cs-class"
                                 data-class="bg-primary sidebar-text-light"></div>
                            <div class="swatch-holder bg-secondary switch-sidebar-cs-class"
                                 data-class="bg-secondary sidebar-text-light"></div>
                            <div class="swatch-holder bg-success switch-sidebar-cs-class"
                                 data-class="bg-success sidebar-text-dark"></div>
                            <div class="swatch-holder bg-info switch-sidebar-cs-class"
                                 data-class="bg-info sidebar-text-dark"></div>
                            <div class="swatch-holder bg-warning switch-sidebar-cs-class"
                                 data-class="bg-warning sidebar-text-dark"></div>
                            <div class="swatch-holder bg-danger switch-sidebar-cs-class"
                                 data-class="bg-danger sidebar-text-light"></div>
                            <div class="swatch-holder bg-light switch-sidebar-cs-class"
                                 data-class="bg-light sidebar-text-dark"></div>
                            <div class="swatch-holder bg-dark switch-sidebar-cs-class"
                                 data-class="bg-dark sidebar-text-light"></div>
                            <div class="swatch-holder bg-focus switch-sidebar-cs-class"
                                 data-class="bg-focus sidebar-text-light"></div>
                            <div class="swatch-holder bg-alternate switch-sidebar-cs-class"
                                 data-class="bg-alternate sidebar-text-light"></div>
                            <div class="divider"></div>
                            <div class="swatch-holder bg-vicious-stance switch-sidebar-cs-class"
                                 data-class="bg-vicious-stance sidebar-text-light"></div>
                            <div class="swatch-holder bg-midnight-bloom switch-sidebar-cs-class"
                                 data-class="bg-midnight-bloom sidebar-text-light"></div>
                            <div class="swatch-holder bg-night-sky switch-sidebar-cs-class"
                                 data-class="bg-night-sky sidebar-text-light"></div>
                            <div class="swatch-holder bg-slick-carbon switch-sidebar-cs-class"
                                 data-class="bg-slick-carbon sidebar-text-light"></div>
                            <div class="swatch-holder bg-asteroid switch-sidebar-cs-class"
                                 data-class="bg-asteroid sidebar-text-light"></div>
                            <div class="swatch-holder bg-royal switch-sidebar-cs-class"
                                 data-class="bg-royal sidebar-text-light"></div>
                            <div class="swatch-holder bg-warm-flame switch-sidebar-cs-class"
                                 data-class="bg-warm-flame sidebar-text-dark"></div>
                            <div class="swatch-holder bg-night-fade switch-sidebar-cs-class"
                                 data-class="bg-night-fade sidebar-text-dark"></div>
                            <div class="swatch-holder bg-sunny-morning switch-sidebar-cs-class"
                                 data-class="bg-sunny-morning sidebar-text-dark"></div>
                            <div class="swatch-holder bg-tempting-azure switch-sidebar-cs-class"
                                 data-class="bg-tempting-azure sidebar-text-dark"></div>
                            <div class="swatch-holder bg-amy-crisp switch-sidebar-cs-class"
                                 data-class="bg-amy-crisp sidebar-text-dark"></div>
                            <div class="swatch-holder bg-heavy-rain switch-sidebar-cs-class"
                                 data-class="bg-heavy-rain sidebar-text-dark"></div>
                            <div class="swatch-holder bg-mean-fruit switch-sidebar-cs-class"
                                 data-class="bg-mean-fruit sidebar-text-dark"></div>
                            <div class="swatch-holder bg-malibu-beach switch-sidebar-cs-class"
                                 data-class="bg-malibu-beach sidebar-text-light"></div>
                            <div class="swatch-holder bg-deep-blue switch-sidebar-cs-class"
                                 data-class="bg-deep-blue sidebar-text-dark"></div>
                            <div class="swatch-holder bg-ripe-malin switch-sidebar-cs-class"
                                 data-class="bg-ripe-malin sidebar-text-light"></div>
                            <div class="swatch-holder bg-arielle-smile switch-sidebar-cs-class"
                                 data-class="bg-arielle-smile sidebar-text-light"></div>
                            <div class="swatch-holder bg-plum-plate switch-sidebar-cs-class"
                                 data-class="bg-plum-plate sidebar-text-light"></div>
                            <div class="swatch-holder bg-happy-fisher switch-sidebar-cs-class"
                                 data-class="bg-happy-fisher sidebar-text-dark"></div>
                            <div class="swatch-holder bg-happy-itmeo switch-sidebar-cs-class"
                                 data-class="bg-happy-itmeo sidebar-text-light"></div>
                            <div class="swatch-holder bg-mixed-hopes switch-sidebar-cs-class"
                                 data-class="bg-mixed-hopes sidebar-text-light"></div>
                            <div class="swatch-holder bg-strong-bliss switch-sidebar-cs-class"
                                 data-class="bg-strong-bliss sidebar-text-light"></div>
                            <div class="swatch-holder bg-grow-early switch-sidebar-cs-class"
                                 data-class="bg-grow-early sidebar-text-light"></div>
                            <div class="swatch-holder bg-love-kiss switch-sidebar-cs-class"
                                 data-class="bg-love-kiss sidebar-text-light"></div>
                            <div class="swatch-holder bg-premium-dark switch-sidebar-cs-class"
                                 data-class="bg-premium-dark sidebar-text-light"></div>
                            <div class="swatch-holder bg-happy-green switch-sidebar-cs-class"
                                 data-class="bg-happy-green sidebar-text-light"></div>
                        </div>
                    </li>
                </ul>

            </div>
        </div>

    </div>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(() => {

            $(".switch-container-class").on("click", function () {
                var classToSwitch = $(this).attr("data-class");
                var containerElement = ".app-container";
                $(containerElement).toggleClass(classToSwitch);

                $(this).parent().find(".switch-container-class").removeClass("active");
                $(this).addClass("active");
            });

            $(".switch-theme-class").on("click", function () {
                var classToSwitch = $(this).attr("data-class");
                var containerElement = ".app-container";

                if (classToSwitch == "app-theme-white") {
                    $(containerElement).removeClass("app-theme-gray");
                    $(containerElement).addClass(classToSwitch);
                }

                if (classToSwitch == "app-theme-gray") {
                    $(containerElement).removeClass("app-theme-white");
                    $(containerElement).addClass(classToSwitch);
                }

                if (classToSwitch == "body-tabs-line") {
                    $(containerElement).removeClass("body-tabs-shadow");
                    $(containerElement).addClass(classToSwitch);
                }

                if (classToSwitch == "body-tabs-shadow") {
                    $(containerElement).removeClass("body-tabs-line");
                    $(containerElement).addClass(classToSwitch);
                }

                $(this).parent().find(".switch-theme-class").removeClass("active");
                $(this).addClass("active");
            });

            $(".switch-header-cs-class").on("click", function () {
                var classToSwitch = $(this).attr("data-class");
                var containerElement = ".app-header";

                $(".switch-header-cs-class").removeClass("active");
                $(this).addClass("active");

                $(containerElement).attr("class", "app-header");
                $(containerElement).addClass("header-shadow " + classToSwitch);

                $("#header_color_scheme").val(classToSwitch);

            });

            $(".switch-sidebar-cs-class").on("click", function () {
                var classToSwitch = $(this).attr("data-class");
                var containerElement = ".app-sidebar";

                $(".switch-sidebar-cs-class").removeClass("active");
                $(this).addClass("active");

                $(containerElement).attr("class", "app-sidebar");
                $(containerElement).addClass("sidebar-shadow " + classToSwitch);

                $("#sidebar_color_scheme").val(classToSwitch);

            });

            $("#save").on("click", function (event) {
                const $form = $("#layout_options");
                event.preventDefault();
                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.admin_template.layout_options')}}', formData, 5000, $(this), 'PUT', $form);
            });

        });
    </script>
@endsection
