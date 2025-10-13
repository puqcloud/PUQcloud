@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')

    <div class="app-page-title app-page-title-simple">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div>
                    <div class="page-title-head center-elem">
                                            <span class="d-inline-block pe-2">
                                                <i class="fas fa-dollar-sign"></i>
                                            </span>
                        <span class="d-inline-block">{{__('main.Currencies')}}</span>
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
                                    {{__('main.Currencies')}}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                @if($admin->hasPermission('currencies-management'))
                    <button type="button"
                            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                            id="create">
                        <i class="fa fa-plus"></i>
                        {{__('main.Create')}}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="countries"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Code')}}</th>
                    <th>{{__('main.Prefix')}}</th>
                    <th>{{__('main.Suffix')}}</th>
                    <th>{{__('main.Default')}}</th>
                    <th>{{__('main.Format')}}</th>
                    <th>{{__('main.Base Conv. Rate')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Code')}}</th>
                    <th>{{__('main.Prefix')}}</th>
                    <th>{{__('main.Suffix')}}</th>
                    <th>{{__('main.Default')}}</th>
                    <th>{{__('main.Format')}}</th>
                    <th>{{__('main.Base Conv. Rate')}}</th>
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

            var tableId = '#countries';
            var ajaxUrl = '{{ route('admin.api.currencies.get') }}';
            var columnsConfig = [
                {data: "code", name: "code"},
                {data: "prefix", name: "prefix"},
                {data: "suffix", name: "suffix"},
                {
                    data: "default", name: "default",
                    render: function (data, type, row) {
                        return renderStatus(!data);
                    }
                },
                {data: "format", name: "format"},
                {data: "exchange_rate", name: "exchange_rate"},

                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';

                        if (row.urls.put) {
                            btn = btn + renderEditButton(row.urls.put);
                        }

                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }

                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig);

            $('#create').on('click', function () {

                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text(translate('Create'));

                var formHtml = `
            <form id="createForm" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <label class="form-label" for="code">${translate('Code')}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="code" name="code" placeholder="${translate('Code')}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="prefix ">${translate('Prefix')}</label>
                    <div>
                        <input type="text" class="form-control" id="prefix" name="prefix" placeholder="${translate('Prefix')}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="suffix">${translate('Suffix')}</label>
                    <div>
                        <input type="text" class="form-control" id="suffix" name="suffix" placeholder="${translate('Suffix')}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="exchange_rate">${translate('Exchange Rate')}</label>
                    <div>
                        <input type="number" class="form-control" id="exchange_rate" name="exchange_rate" placeholder="${translate('Exchange Rate')}">
                    </div>
                </div>
                <div class="mb-3">
                    <div class="position-relative mb-3">
                        <div>
                            <label for="format" class="form-label">${translate('Format')}</label>
                            <select name="format" id="format" class="form-select mb-2 form-control">
                                <option value="1234.56">1234.56</option>
                                <option value="1,234.56">1,234.56</option>
                                <option value="1.234,56">1.234,56</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="position-relative form-check">
                    <input name="default" id="default" type="checkbox" class="form-check-input">
                    <label for="default" class="form-label form-check-label">${translate('Default')}</label>
                </div>

            </form>`;

                $modalBody.html(formHtml);

                $('#universalModal').modal('show');

            });

            $dataTable.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();

                var modelUrl = $(this).data('model-url');
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                var $modalSaveButton = $('#modalSaveButton');
                $modalSaveButton.data('modelUrl', modelUrl);

                $modalTitle.text(translate('Edit'));

                const formHtml = `
    <form id="editForm" class="col-md-10 mx-auto">
        <div class="mb-3">
            <label for="code" class="form-label">${translate('Code')}</label>
            <div>
                <input type="text" class="form-control" id="code" name="code" placeholder="${translate('Code')}">
            </div>
        </div>
        <div class="mb-3">
            <label for="prefix" class="form-label">${translate('Prefix')}</label>
            <div>
                <input type="text" class="form-control" id="prefix" name="prefix" placeholder="${translate('Prefix')}">
            </div>
        </div>
        <div class="mb-3">
            <label for="suffix" class="form-label">${translate('Suffix')}</label>
            <div>
                <input type="text" class="form-control" id="suffix" name="suffix" placeholder="${translate('Suffix')}">
            </div>
        </div>
        <div class="mb-3">
            <label for="exchange_rate" class="form-label">${translate('Exchange Rate')}</label>
            <div>
                <input type="number" class="form-control" id="exchange_rate" name="exchange_rate" placeholder="${translate('Exchange Rate')}">
            </div>
        </div>
        <div class="mb-3">
            <div class="position-relative mb-3">
                <label for="format" class="form-label">${translate('Format')}</label>
                <select name="format" id="format" class="form-select mb-2 form-control">
                    <option value="1234.56">1234.56</option>
                    <option value="1,234.56">1,234.56</option>
                    <option value="1.234,56">1.234,56</option>
                </select>
            </div>
        </div>
        <div class="position-relative form-check">
            <input name="default" id="default" type="checkbox" class="form-check-input">
            <label for="default" class="form-label form-check-label">${translate('Default')}</label>
        </div>
    </form>
    `;

                $modalBody.html(formHtml);

                PUQajax(modelUrl, {}, 50, $(this), 'GET')
                    .then(function (response) {
                        $('#code').val(response.data.code);
                        $('#prefix').val(response.data.prefix);
                        $('#suffix').val(response.data.suffix);
                        $('#exchange_rate').val(response.data.exchange_rate);
                        $('#format').val(response.data.format);
                        $('#default').prop('checked', response.data.default);
                        $('#universalModal').modal('show');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            });

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

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.currency.post')}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

                if ($('#editForm').length) {
                    var $form = $('#editForm');
                    var formData = serializeForm($form);
                    var modelUrl = $(this).data('model-url');

                    PUQajax(modelUrl, formData, 500, $(this), 'PUT', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

        });
    </script>
@endsection
