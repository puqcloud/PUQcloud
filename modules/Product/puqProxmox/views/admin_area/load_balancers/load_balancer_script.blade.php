@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('buttons')

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
            id="save">
        <i class="fa fa-save"></i>
        {{ __('Product.puqProxmox.Save') }}
    </button>

@endsection
@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.load_balancers.load_balancer_header')

    <div id="container">
        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <div class="row">
                    <!-- Editor -->
                    <div class="col-lg-8 mb-3 mb-lg-0">
                        <form id="lxcOsTemplateForm" action="" novalidate>
                            <div class="form-group mb-0">
                                <textarea name="script" id="script" class="form-control"
                                          rows="25"></textarea>
                            </div>
                        </form>
                    </div>

                    <!-- Variables -->
                    <div class="col-lg-4">
                        <div class="border rounded bg-light p-3 h-100 overflow-auto" style="max-height: 600px">
                            <div class="mb-2 fw-bold"><i
                                    class="fas fa-list me-1"></i>{{ __('Product.puqProxmox.Available Variables') }}
                            </div>
                            <div class="d-flex flex-column gap-2 small">
                                @foreach($variables as $var)
                                    <div class="d-flex justify-content-between align-items-start">
                                        <a href="#" class="insert-variable text-decoration-none"
                                           data-value="{{ '{' . $var['name'] . '}' }}">
                                            <code class="text-primary">{{ '{' . $var['name'] . '}' }}</code>
                                        </a>
                                        <div class="text-muted ms-3 text-end flex-grow-1">
                                            {{ $var['description'] }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('js')
    @parent

    <script>
        $(document).ready(function () {

            window.editor = null;
            $(document).on('loadFormDataEvent', function() {
                loadFormData();
            });
            function loadFormData() {
                blockUI('container');

                PUQajax('{{ route('admin.api.Product.puqProxmox.load_balancer.script.get', ['uuid'=>$uuid,'type'=>$type]) }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        const scriptValue = response.data?.script || '';
                        if (window.editor) {
                            window.editor.setValue(scriptValue);
                        } else {
                            document.getElementById('script').value = scriptValue;
                            window.editor = CodeMirror.fromTextArea(document.getElementById('script'), {
                                mode: "shell",
                                theme: "default",
                                lineNumbers: true,
                                matchBrackets: true,
                                autoCloseBrackets: true,
                                indentUnit: 4,
                                indentWithTabs: true,
                                lineWrapping: true,
                                scrollbarStyle: "native"
                            });

                            const offsetTop = document.getElementById('script').getBoundingClientRect().top;
                            const windowHeight = window.innerHeight;
                            const editorHeight = windowHeight - offsetTop - 300;
                            window.editor.setSize('100%', `${editorHeight}px`);
                        }

                        unblockUI('container');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }


            $("#save").on("click", function (event) {
                event.preventDefault();
                const $form = $("#lxcOsTemplateForm");
                if (window.editor) {
                    window.editor.save();
                }
                const formData = serializeForm($form);
                console.log(formData);

                PUQajax('{{ route('admin.api.Product.puqProxmox.load_balancer.script.put', ['uuid'=>$uuid,'type'=>$type]) }}', formData, 3000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            $(document).on('click', '.insert-variable', function (e) {
                e.preventDefault();
                const variable = $(this).data('value');
                if (window.editor) {
                    const doc = window.editor.getDoc();
                    const cursor = doc.getCursor();
                    doc.replaceRange(variable, cursor);
                    window.editor.focus();
                }
            });

            loadFormData();
        });
    </script>
@endsection
