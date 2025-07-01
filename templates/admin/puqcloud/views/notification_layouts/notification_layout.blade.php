@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
    <link rel="stylesheet" href="{{ asset_admin('vendors/codemirror/lib/codemirror.css') }}">
    <script src="{{ asset_admin('vendors/codemirror/lib/codemirror.js') }}"></script>
    <script src="{{ asset_admin('vendors/codemirror/mode/htmlmixed/htmlmixed.js') }}"></script>
    <script src="{{ asset_admin('vendors/codemirror/mode/php/php.js') }}"></script>
    <script src="{{ asset_admin('vendors/codemirror/mode/xml/xml.js') }}"></script>
    <script src="{{ asset_admin('vendors/codemirror/mode/css/css.js') }}"></script>
    <script src="{{ asset_admin('vendors/codemirror/mode/clike/clike.js') }}"></script>
@endsection


@section('content')
    <div class="app-page-title app-page-title-simple">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div>
                    <div class="page-title-head center-elem">
                    <span class="d-inline-block pe-2">
                        <i class="fas fa-address-card"></i>
                    </span>
                        <span class="d-inline-block">{{__('main.Edit Notification Layout')}}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav class="" aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a>
                                        <i aria-hidden="true" class="fa fa-home"></i>
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('main.Dashboard') }}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    <a href="{{route('admin.web.notification_layouts')}}">{{__('main.Notification Layouts')}}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{ request()->route('uuid') }}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                @if($admin->hasPermission('notification-layouts-management'))
                    <button id="save" type="button" class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                        <i class="fa fa-save"></i> {{__('main.Save')}}
                    </button>
                @endif
            </div>
        </div>
    </div>


    <form id="notification_layout" class="mx-auto" novalidate="novalidate">
        <div class="mb-3 card">
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
                        <div class="mb-3">
                            <label class="form-label" for="name">{{__('main.Name')}}</label>
                            <div>
                                <input type="text" class="form-control input-mask-trigger" id="name"
                                       name="name"
                                       placeholder="{{__('main.Name')}}">
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
                        <div class="mb-3">
                            <label class="form-label" for="description">{{__('main.Description')}}</label>
                            <div>
                                <input type="text" class="form-control input-mask-trigger" id="description"
                                       name="description"
                                       placeholder="{{__('main.Description')}}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-3 card">
            <div class="tabs-lg-alternate card-header">
                <ul class="nav nav-justified">
                    @php($i=0)
                    @foreach($locales as $key => $locale)
                        <li class="nav-item">
                            <a data-bs-toggle="tab" href="#tab-{{$i}}" class="nav-link @if($i === 0) active @endif"
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
                            <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 mb-1 order-2 order-lg-1">
                                <div class="form-group">
                                    <label for="layout">{{__('main.Layout')}}</label>
                                    <textarea name="layout" id="layout" class="form-control" rows="20"></textarea>
                                </div>
                            </div>
                            <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 mb-1 order-1 order-lg-2">
                                <label for="html_preview">{{__('main.HTML Preview')}}</label>
                                <div id="html_preview" class="border p-3"
                                     style="position: sticky; top: 100px; background-color: #fff;">
                                </div>
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
            let editor;

            function loadFormData(locale) {
                blockUI('notification_layout');
                const $form = $('#notification_layout');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.notification_layout.get', $uuid)}}?locale=' + locale, {}, 50, null, 'GET')
                    .then(function (response) {
                        $.each(response.data, function (key, value) {
                            const $element = $form.find(`[name="${key}"]`);
                            if ($element.length) {
                                if ($element.is('select')) {
                                    $element.find(`option[value="${value}"]`).prop('selected', true);
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
                            unblockUI('notification_layout');
                        }

                        if (!editor) {
                            editor = CodeMirror.fromTextArea(document.getElementById('layout'), {
                                mode: "application/x-httpd-php",
                                lineNumbers: true,
                                matchBrackets: true,
                                autoCloseTags: true,
                                autoCloseBrackets: true,
                                indentUnit: 4,
                                indentWithTabs: true,
                                theme: "default",
                                lineWrapping: true,
                                scrollbarStyle: "native"
                            });

                            editor.setSize('100%', 'auto');

                            editor.on('change', function () {
                                editor.save();
                                updateHtmlPreview(editor.getValue());
                            });
                        } else {
                            editor.setValue(response.data.layout || '');
                        }

                        updateHtmlPreview(response.data.layout || '');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            function updateHtmlPreview(html) {
                $('#html_preview').html(html);
            }

            $("#save").on("click", function (event) {
                const $form = $("#notification_layout");
                event.preventDefault();

                if (editor) {
                    editor.save();
                }

                const locale = $('.nav-link.active').data('locale');
                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.notification_layout.put', $uuid)}}?locale=' + locale, formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData(locale);
                    });
            });

            loadFormData($('.nav-link.active').data('locale'));

            $('.nav-link').on('click', function () {
                const locale = $(this).data('locale');
                loadFormData(locale);
            });
        });
    </script>
@endsection
