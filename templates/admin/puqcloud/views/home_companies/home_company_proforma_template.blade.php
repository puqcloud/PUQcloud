@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent

    <style>
        .combined-mode .editor-col {
            width: 50%;
            display: block;
        }

        .combined-mode .preview-col {
            width: 50%;
            display: block;
        }

        .code-mode .editor-col {
            width: 100%;
            display: block;
        }

        .code-mode .preview-col {
            display: none;
        }

        .preview-mode .editor-col {
            display: none;
        }

        .preview-mode .preview-col {
            width: 100%;
            max-width: none;
            display: flex;
            justify-content: center;
        }

        .combined-mode .editor-col {
            width: 50%;
            display: block;
        }

        .combined-mode .preview-col {
            width: 50%;
            display: block;
        }

        .code-mode .editor-col {
            width: 100%;
            display: block;
        }

        .code-mode .preview-col {
            display: none;
        }

        .preview-mode .editor-col {
            display: none;
        }

        .code-mode .editor-col {
            width: 100%;
            display: block;
        }

        .code-mode .preview-col {
            display: none;
        }

        .preview-mode .preview-col {
            width: 100%;
            max-width: none;
            display: flex;
            justify-content: center;
        }

        #html_preview.preview-a4,
        #html_preview.preview-letter {
            background-color: white;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            padding: 20mm;
        }

        #html_preview.preview-a4 {
            width: 210mm;
            min-height: 297mm;
        }

        #html_preview.preview-letter {
            width: 216mm; /* 8.5 inches */
            min-height: 279mm; /* 11 inches */
        }
    </style>

@endsection


@section('buttons')
    @parent
    @if($admin->hasPermission('finance-edit'))
        <div class="dropdown d-inline-block">
            <button id="templateDropdownMenuButton" type="button" aria-haspopup="true" aria-expanded="false"
                    data-bs-toggle="dropdown"
                    class="mb-2 me-2 btn-icon btn-outline-2x dropdown-toggle btn btn-outline-info">
                <i class="fas fa-file-code me-1"></i> {{__('main.Load Template')}}
            </button>
            <div id="templateDropdownMenu" tabindex="-1" role="menu" aria-hidden="true" class="dropdown-menu">
            </div>
        </div>

        <button id="save" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
            <i class="fa fa-save"></i> {{__('main.Save')}}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.home_companies.home_company_header')
    <form id="home_company" class="mx-auto" novalidate="novalidate">
        <input type="hidden" class="form-control" id="name" name="name">

        <button id="combined_mode" class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-success">
            <i class="lnr-screen btn-icon-wrapper"></i>
            {{__('main.Combined Mode')}}
        </button>

        <button id="code_mode" class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-secondary">
            <i class="lnr-code btn-icon-wrapper"></i>
            {{__('main.Code Mode')}}
        </button>

        <button id="preview_a4" class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-info">
            <i class="lnr-printer btn-icon-wrapper"></i>
            {{__('main.A4 Preview')}}
        </button>

        <button id="preview_letter" class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-info">
            <i class="lnr-printer btn-icon-wrapper"></i>
            {{__('main.Letter Preview')}}
        </button>

        <div class="mb-3 card">
            <div class="tab-content mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="row">
                            <div
                                class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 mb-1 order-2 order-lg-1 editor-col">
                                <div class="form-group">
                                    <label for="proforma_template">{{__('main.Template')}}</label>
                                    <textarea name="proforma_template" id="proforma_template" class="form-control"
                                              rows="20"></textarea>
                                </div>
                            </div>
                            <div
                                class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 mb-1 order-1 order-lg-2 preview-col">
                                <div class="form-group">
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
        </div>
    </form>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            let editor;

            function loadFormData() {
                blockUI('home_company');
                const $form = $('#home_company');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.home_company.get', $uuid)}}', {}, 1500, null, 'GET')
                    .then(function (response) {
                        $("#name").val(response.data?.name);
                        $("#proforma_template").val(response.data?.proforma_template);

                        if (!editor) {
                            editor = CodeMirror.fromTextArea(document.getElementById('proforma_template'), {
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
                            editor.setValue(response.data.proforma_template || '');
                        }

                        updateHtmlPreview(response.data.proforma_template || '');
                        if (response.data) {
                            unblockUI('home_company');
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            function updateHtmlPreview(html) {
                $('#html_preview').html(html);
            }

            function switchToCombinedMode() {
                const $row = $(".row");
                $row.addClass("combined-mode");
                $row.removeClass("code-mode preview-mode");

                const $preview = $("#html_preview");
                $preview.removeClass("preview-a4 preview-letter");
            }

            function switchToCodeMode() {
                const $row = $(".row");
                $row.addClass("code-mode");
                $row.removeClass("combined-mode preview-mode");
            }

            function switchToPreviewLetterMode() {
                const $row = $(".row");
                $row.addClass("preview-mode");
                $row.removeClass("combined-mode code-mode");
                const $preview = $("#html_preview");
                $preview.removeClass("preview-a4");
                $preview.addClass("preview-letter");
            }

            function switchToPreviewA4Mode() {
                const $row = $(".row");
                $row.addClass("preview-mode");
                $row.removeClass("combined-mode code-mode");
                const $preview = $("#html_preview");
                $preview.removeClass("preview-letter");
                $preview.addClass("preview-a4");
            }

            $("#combined_mode").on("click", function (event) {
                event.preventDefault();
                switchToCombinedMode();
                editor.setSize('100%', 'auto');
                editor.refresh();
            });

            $("#code_mode").on("click", function (event) {
                event.preventDefault();
                switchToCodeMode();
                editor.setSize('100%', 'auto');
                editor.refresh();
            });

            $("#preview_a4").on("click", function (event) {
                event.preventDefault();
                switchToPreviewA4Mode();
                editor.setSize('100%', 'auto');
                editor.refresh();
            });

            $("#preview_letter").on("click", function (event) {
                event.preventDefault();
                switchToPreviewLetterMode();
                editor.setSize('100%', 'auto');
                editor.refresh();
            });

            $("#save").on("click", function (event) {
                const $form = $("#home_company");
                event.preventDefault();
                if (editor) {
                    editor.save();
                }
                const formData = serializeForm($form);

                PUQajax('{{route('admin.api.home_company.put', $uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            function loadTemplateContent(templateName) {
                let $button = $('#templateDropdownMenuButton');
                PUQajax(`{{route('admin.api.home_company.invoice_template.get', 'proforma')}}?name=${templateName}`, {}, 1500, $button, 'GET')
                    .then(function (response) {
                        if (editor) {
                            editor.setValue(response.data || '');
                        }
                        updateHtmlPreview(response.data || '');
                    })
                    .catch(function (error) {
                        console.error('Error loading template content:', error);
                    });
            }

            function loadInvoiceTemplates() {
                PUQajax('{{ route('admin.api.home_company.invoice_templates.get', 'proforma') }}', {}, 1500, null, 'GET')
                    .then(function (response) {
                        let $menu = $('#templateDropdownMenu');
                        $menu.empty();

                        $.each(response.data, function (index, templateName) {
                            let $btn = $('<button>', {
                                type: 'button',
                                tabindex: 0,
                                class: 'dropdown-item',
                                text: templateName,
                                click: function () {
                                    loadTemplateContent(templateName);
                                }
                            });

                            $menu.append($btn);
                        });
                    })
                    .catch(function (error) {
                        console.error('Error loading templates:', error);
                    });
            }

            loadInvoiceTemplates();

            loadFormData();
        });
    </script>
@endsection

