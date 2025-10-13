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
            <form id="group" class="col-md-10 mx-auto" method="post" action="" novalidate="novalidate">
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
                            <textarea name="description" id="description" class="form-control" rows="5"></textarea>
                        </div>
                    </div>
                    <div>
                        @if($admin->hasPermission('admins-create'))
                            <button type="button"
                                    class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success" id="create">
                                <i class="fa fa-plus"></i>
                                {{__('main.Create')}}
                            </button>

                            <button type="button"
                                    class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                                    id="mass_creation">
                                <i class="fa fa-plus"></i>
                                {{__('main.Mass Creation')}}
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="notification"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Category')}}</th>
                    <th>{{__('main.Notification')}}</th>
                    <th>{{__('main.Layout')}}</th>
                    <th>{{__('main.Template')}}</th>
                    <th>{{__('main.Sender')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Category')}}</th>
                    <th>{{__('main.Notification')}}</th>
                    <th>{{__('main.Layout')}}</th>
                    <th>{{__('main.Template')}}</th>
                    <th>{{__('main.Sender')}}</th>
                    <th></th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            var $tableId = $('#notification');
            var ajaxUrl = '{{route('admin.api.group.notification_rules.get', $uuid)}}';
            var selectedCategoryId;
            var columnsConfig = [
                {
                    data: "category", name: "category",
                    render: function (data, type, row) {
                        if (row.category_data && row.category_data.text) {
                            return translate(row.category_data.text);
                        }
                        return data;
                    },
                },
                {data: "notification", name: "notification"},
                {data: "notification_layout", name: "notification_layout"},
                {data: "notification_template", name: "notification_template"},
                {
                    data: "notification_senders", name: "notification_senders",
                    render: function (data, type, row) {
                        if (Array.isArray(data)) {
                            return data.map(item => `<div class="badge bg-alternate ms-2">${item}</div>`).join(" ");
                        }
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.get) {
                            btn = btn + renderEditButton(row.urls.get);
                        }
                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];
            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig);

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm(translate('Are you sure you want to delete this record?'))) {
                    PUQajax(modelUrl, null, 3000, $(this), 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $dataTable.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                var $modalSaveButton = $('#modalSaveButton');
                $modalSaveButton.data('modelUrl', modelUrl);

                $modalTitle.text(translate('Edit Notification Rule'));
                const formattedData = `
    <form id="editRule" class="col-md-10 mx-auto">
        <div class="mb-3">
            <label for="category" class="form-label">` + translate('Category') + `</label>
            <select class="form-select mb-2 form-control" name="category" id="category" disabled></select>
        </div>
        <div class="mb-3">
            <label for="notification" class="form-label">` + translate('Notification') + `</label>
            <select class="form-select mb-2 form-control" name="notification" id="notification" disabled></select>
        </div>
        <div class="mb-3">
            <label for="notification_layout_uuid" class="form-label">` + translate('Layout') + `</label>
            <select class="form-select mb-2 form-control" name="notification_layout_uuid" id="notification_layout_uuid"></select>
        </div>
        <div class="mb-3">
            <label for="notification_template_uuid" class="form-label">` + translate('Template') + `</label>
            <select class="form-select mb-2 form-control" name="notification_template_uuid" id="notification_template_uuid"></select>
        </div>
        <div class="mb-3">
            <label for="senders" class="form-label">${translate('Senders')}</label>
            <select multiple name="senders" id="senders" class="form-select mb-2 form-control"></select>
        </div>
    </form>
    `;
                $modalBody.html(formattedData);
                var $elementCategory = $modalBody.find('[name="category"]');
                var $elementNotification = $modalBody.find('[name="notification"]');
                var $elementLayout = $modalBody.find('[name="notification_layout_uuid"]');
                var $elementTemplate = $modalBody.find('[name="notification_template_uuid"]');
                var $elementSenders = $modalBody.find('[name="senders"]');
                PUQajax(modelUrl, {}, 50, $(this), 'GET')
                    .then(function (response) {

                        initializeSelect2($elementCategory, '{{route('admin.api.notification_categories.select.get')}}',
                            response.data.category_data,
                            'GET', 1000, {
                                dropdownParent: $('#universalModal')
                            });

                        initializeSelect2($elementNotification, '{{route('admin.api.notification_category_notifications.select.get')}}',
                            response.data.notification_data,
                            'GET', 1000, {
                                dropdownParent: $('#universalModal')
                            });

                        initializeSelect2($elementLayout, '{{route('admin.api.notification_layouts.select.get')}}',
                            response.data.notification_layout_data,
                            'GET', 1000, {
                                dropdownParent: $('#universalModal')
                            });

                        initializeSelect2($elementTemplate, '{{route('admin.api.notification_templates.select.get')}}',
                            response.data.notification_template_data,
                            'GET', 1000, {
                                dropdownParent: $('#universalModal')
                            }, {
                                selectedCategoryId: response.data.category_data.id
                            });

                        initializeSelect2($elementSenders, '{{route('admin.api.notification_senders.select.get')}}',
                            response.data.notification_senders_data,
                            'GET', 1000, {
                            dropdownParent: $('#universalModal')
                        });


                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
                $('#universalModal').modal('show');
            });

            function loadFormData() {
                blockUI('group');
                var $form = $('#group');

                PUQajax('{{route('admin.api.group.get', $uuid)}}', {}, 50, null, 'GET')
                    .then(function (response) {
                        for (var key in response.data) {
                            var $element = $form.find('[name="' + key + '"]');
                            if ($element.length) {
                                if ($element.prop('tagName').toLowerCase() === 'textarea') {
                                    if (response.data[key] !== null) {
                                        $element.val(response.data[key]);
                                    }
                                    $element.textareaAutoSize();
                                    $element.trigger('autosize');
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

                        if (response.data.system_permissions) {
                            var $systemPermissionsDiv = $('#system_permissions');
                            $systemPermissionsDiv.empty();

                            var groupedPermissions = {};

                            response.data.system_permissions.forEach(function (system_permission) {
                                var group = system_permission.key_group;

                                if (!groupedPermissions[group]) {
                                    groupedPermissions[group] = [];
                                }

                                groupedPermissions[group].push(system_permission);
                            });

                            Object.keys(groupedPermissions).forEach(function (group) {
                                var permissions = groupedPermissions[group];
                                var cardHtml = `
            <div class="col-lg-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <strong>${group}</strong>
                    </div>
                    <div class="card-body row">
            `;

                                permissions.forEach(function (system_permission) {
                                    var checked = system_permission.is_linked ? 'checked' : '';

                                    cardHtml += `
                    <div class="col-lg-6 col-xl-4">
                        <div class="widget-content mb-3 card-shadow-primary border-primary">
                            <div class="widget-content-wrapper">
                                <div class="widget-content-left me-3"></div>
                                <div class="widget-content-left">
                                    <div class="widget-heading">${system_permission.name}</div>
                                    <div class="widget-subheading">
                                        ${system_permission.description ? system_permission.description : '***'}
                                    </div>
                                </div>
                                <div class="widget-content-right">
                                    <div class="widget-numbers text-primary">
                                        <span class="count-up-wrapper">
                                            <input type="checkbox" id="checkbox_${system_permission.key}" name="system_permissions[${system_permission.key}]"
                                                   data-toggle="toggle" data-on="{{__('main.On')}}" data-off="{{__('main.Off')}}"
                                                   data-onstyle="success" data-offstyle="danger" ${checked}>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                                });
                                cardHtml += `
                    </div>
                </div>
            </div>
            `;

                                $systemPermissionsDiv.append(cardHtml);
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
                var $form = $('#group');
                if (!validategroup($form)) {
                    return;
                }

                var formData = serializeForm($form);

                PUQajax('{{route('admin.api.group.put', $uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            $('#create').on('click', function (event) {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text(translate('Create Notification Rule'));

                const formattedData = `
    <form id="createRule" class="col-md-10 mx-auto">
        <input type="hidden" name="group_uuid" value="{{$uuid}}">
        <div class="mb-3">
            <label for="category" class="form-label">` + translate('Category') + `</label>
            <select class="form-select mb-2 form-control" name="category" id="category"></select>
        </div>
        <div class="mb-3">
            <label for="notification" class="form-label">` + translate('Notification') + `</label>
            <select class="form-select mb-2 form-control" name="notification" id="notification"></select>
        </div>
        <div class="mb-3">
            <label for="notification_layout_uuid" class="form-label">` + translate('Layout') + `</label>
            <select class="form-select mb-2 form-control" name="notification_layout_uuid" id="notification_layout_uuid"></select>
        </div>
        <div class="mb-3">
            <label for="notification_template_uuid" class="form-label">` + translate('Template') + `</label>
            <select class="form-select mb-2 form-control" name="notification_template_uuid" id="notification_template_uuid"></select>
        </div>

        <div class="mb-3">
            <label for="senders" class="form-label">${translate('Senders')}</label>
            <select multiple name="senders" id="senders" class="form-select mb-2 form-control"></select>
        </div>
    </form>
    `;

                $modalBody.html(formattedData);
                var $elementCategory = $modalBody.find('[name="category"]');
                var $elementNotification = $modalBody.find('[name="notification"]');
                var $elementLayout = $modalBody.find('[name="notification_layout_uuid"]');
                var $elementTemplate = $modalBody.find('[name="notification_template_uuid"]');


                var $elementSenders = $modalBody.find('[name="senders"]');

                initializeSelect2($elementCategory, '{{route('admin.api.notification_categories.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                function updateNotificationOptions() {
                    $elementNotification.empty().trigger('change');
                    initializeSelect2($elementNotification, '{{route('admin.api.notification_category_notifications.select.get')}}', '', 'GET', 1000, {
                        dropdownParent: $('#universalModal')
                    }, {
                        selectedCategoryId: function () {
                            return selectedCategoryId
                        }
                    });
                }

                initializeSelect2($elementLayout, '{{route('admin.api.notification_layouts.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                function updateTemplateOptions() {
                    $elementTemplate.empty().trigger('change');
                    initializeSelect2($elementTemplate, '{{route('admin.api.notification_templates.select.get')}}', '', 'GET', 1000, {
                        dropdownParent: $('#universalModal')
                    }, {
                        selectedCategoryId: function () {
                            return selectedCategoryId
                        }
                    });
                }

                $elementCategory.on('change', function () {
                    selectedCategoryId = $(this).val();
                    updateNotificationOptions();
                    updateTemplateOptions();
                });

                initializeSelect2($elementSenders, '{{route('admin.api.notification_senders.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                $('#universalModal').modal('show');

            });

            $('#mass_creation').on('click', function (event) {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text(translate('Notification Rules Mass Creation'));

                const formattedData = `
    <form id="massCreation" class="col-md-10 mx-auto">
        <input type="hidden" name="group_uuid" value="{{$uuid}}">
        <div class="mb-3">
            <label for="category" class="form-label">` + translate('Category') + `</label>
            <select class="form-select mb-2 form-control" name="category" id="category"></select>
        </div>
        <div class="mb-3">
            <label for="notification_layout_uuid" class="form-label">` + translate('Layout') + `</label>
            <select class="form-select mb-2 form-control" name="notification_layout_uuid" id="notification_layout_uuid"></select>
        </div>
        <div class="mb-3">
            <label for="senders" class="form-label">${translate('Senders')}</label>
            <select multiple name="senders" id="senders" class="form-select mb-2 form-control"></select>
        </div>
    </form>
    `;

                $modalBody.html(formattedData);
                var $elementCategory = $modalBody.find('[name="category"]');
                var $elementLayout = $modalBody.find('[name="notification_layout_uuid"]');
                var $elementSenders = $modalBody.find('[name="senders"]');

                initializeSelect2($elementCategory, '{{route('admin.api.notification_categories.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                initializeSelect2($elementLayout, '{{route('admin.api.notification_layouts.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                initializeSelect2($elementSenders, '{{route('admin.api.notification_senders.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                $('#universalModal').modal('show');

            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createRule').length) {
                    var $form = $('#createRule');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.notification_rule.post')}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

                if ($('#massCreation').length) {
                    var $form = $('#massCreation');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.notification_rule.mass_creation.post')}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

                if ($('#editRule').length) {
                    var $form = $('#editRule');
                    var formData = serializeForm($form);
                    var modelUrl = $(this).data('model-url');

                    PUQajax(modelUrl, formData, 500, $(this), 'PUT', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

            });

            loadFormData();
        });
    </script>
@endsection


