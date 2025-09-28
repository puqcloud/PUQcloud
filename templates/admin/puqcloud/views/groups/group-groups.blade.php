@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')
    @include(config('template.admin.view') .'.groups.page-title')

    <div id="mainCard" class="main-card mb-3 card">
        <div class="card-body">
            <form id="group" class="col-md-10 mx-auto" novalidate="novalidate">
                <div class="row">

                    <div class="col-lg-6 col-xl-6">
                        <div class="mb-3">
                            <label class="form-label" for="email">{{__('main.Name')}}</label>
                            <div>
                                <input type="text" class="form-control input-mask-trigger" id="name" name="name"
                                       placeholder="{{__('main.Name')}}">
                            </div>
                        </div>

                        <div class="card mb-3 widget-content" data-widget-key="type_data">
                            <div class="widget-content-wrapper">
                                <div class="widget-content-left">
                                    <div class="widget-heading"></div>
                                    <div class="widget-subheading"></div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="col-lg-6 col-xl-6">
                        <label class="form-label" for="description">{{__('main.Description')}}</label>
                        <div>
                            <textarea name="description" id="description" class="form-control" rows="5"
                                      style="resize: none;"></textarea>
                        </div>
                    </div>

                    <div class="col-12 mb-1">
                        <div class="row" id="all_groups"></div>
                    </div>

                </div>
            </form>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            var $form = $('#group');

            function loadFormData() {
                blockUI('group');

                PUQajax('{{route('admin.api.group.get', $uuid)}}', {}, 50, null, 'GET')
                    .then(function (response) {
                        for (var key in response.data) {
                            var $element = $form.find('[name="' + key + '"]');
                            if ($element.length) {
                                if ($element.prop('tagName').toLowerCase() === 'textarea') {
                                    if (response.data[key] !== null) {
                                        $element.val(response.data[key]);
                                    }
                                    continue;
                                }

                                $element.val(response.data[key]);
                            }
                        }

                        for (var key in response.data) {
                            var widgetData = response.data[key];
                            var $widgetElement = $('[data-widget-key="' + key + '"]');

                            if ($widgetElement.length) {
                                try {
                                    if (widgetData) {
                                        var $heading = $widgetElement.find('.widget-heading');
                                        var $subheading = $widgetElement.find('.widget-subheading');

                                        if (widgetData.name && $heading.length) {
                                            $heading.text(widgetData.name);
                                        }
                                        if (widgetData.description && $subheading.length) {
                                            $subheading.text(widgetData.description);
                                        }
                                    }
                                } catch (e) {
                                    console.error('Error parsing JSON:', e);
                                }
                            }
                        }

                        if (response.data.all_groups) {
                            var $allGroupsDiv = $('#all_groups');
                            $allGroupsDiv.empty();

                            response.data.all_groups.forEach(function (group) {
                                var checked = group.is_linked ? 'checked' : '';

                                var checkboxHtml = `
    <div class="col-lg-6 col-xl-4">
        <div class="card mb-3 widget-content">
            <div class="widget-content-wrapper">
                <div class="widget-content-left me-3"></div>
                <div class="widget-content-left">
                    <div class="widget-heading">${group.name}</div>
                    <div class="widget-subheading">
                        ${group.description ? group.description : '***'}
                    </div>
                </div>
                <div class="widget-content-right">
                    <div class="widget-numbers text-primary">
                        <span class="count-up-wrapper">
                            <input type="checkbox" id="checkbox_${group.uuid}" name="groups[${group.uuid}]"
                                   data-toggle="toggle" data-on="{{__('main.On')}}" data-off="{{__('main.Off')}}"
                                   data-onstyle="success" data-offstyle="danger" ${checked}>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
`;

                                $allGroupsDiv.append(checkboxHtml);
                            });
                        }
                        $('[data-toggle="toggle"]').bootstrapToggle();

                        if (response.data) {
                            unblockUI('group');
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            function validategroup($form) {
                $form.data('validator', null);
                $form.validate({
                    rules: {
                        name: {
                            required: true,
                        },
                    },
                    messages: {
                        name: translate('Please enter Name'),
                    },
                    errorElement: "em",
                    errorPlacement: function (error, element) {
                        error.addClass("invalid-feedback");
                        if (element.prop("type") === "checkbox") {
                            error.insertAfter(element.next("label"));
                        } else {
                            error.insertAfter(element);
                        }
                    },
                    highlight: function (element, errorClass, validClass) {
                        $(element).addClass("is-invalid").removeClass("is-valid");
                    },
                    unhighlight: function (element, errorClass, validClass) {
                        $(element).removeClass("is-invalid").addClass("is-valid");
                    }
                });
                $form.validate().resetForm();
                return $form.valid();
            }

            $('#save').on('click', function (event) {
                event.preventDefault();
                if (!validategroup($form)) {
                    return;
                }

                var formData = serializeForm($form);

                PUQajax('{{route('admin.api.group.put', $uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            $form.on('keydown', function (event) {
                if (event.key === 'Enter' && !$(event.target).is('textarea')) {
                    event.preventDefault();
                }
            });

            loadFormData();

        });

    </script>
@endsection
