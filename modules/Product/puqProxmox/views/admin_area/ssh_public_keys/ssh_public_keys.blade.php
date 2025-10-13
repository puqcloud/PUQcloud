@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('content')

    <div class="app-page-title app-page-title-simple">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div>
                    <div class="page-title-head center-elem">
                                            <span class="d-inline-block pe-2">
                                                <i class="fas fa-server"></i>
                                            </span>
                        <span class="d-inline-block">{{ $title }}</span>
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
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('Product.puqProxmox.Dashboard') }}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{ $title }}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="page-title-actions">
                <button type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                        id="create">
                    <i class="fa fa-plus"></i>
                    {{__('Product.puqProxmox.Create')}}
                </button>
            </div>

        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="ssh_public_keys"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
                    <th>{{__('Product.puqProxmox.Client')}}</th>
                    <th>{{__('Product.puqProxmox.Info')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
                    <th>{{__('Product.puqProxmox.Client')}}</th>
                    <th>{{__('Product.puqProxmox.Info')}}</th>
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

            var tableId = '#ssh_public_keys';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.ssh_public_keys.get') }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name",
                    render: function (data, type, row) {
                        return `
            <span style="
                color: #2b2d30;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 0.875rem;
                font-weight: 500;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                display: inline-block;
                ">
                <i class="fas fa-key me-1"></i>${data}
            </span>
        `;
                    }
                },
                {
                    data: 'client_uuid',
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="widget-heading">${row.client_firstname} ${row.client_lastname} ${row.client_company_name ? '(' + row.client_company_name + ')' : ''}</div>
                            <div class="widget-subheading">
                                    ${linkify(`client:${row.client_uuid}`, true)}
                            </div>
                        </div>
                    </div>
                </div>`;
                    }
                },
                {
                    data: "info",
                    name: "info",
                    render: function (data, type, row) {
                        if (!data) return '';

                        var icon = '';
                        switch (data.type) {
                            case 'ssh-rsa':
                            case 'ssh-dss':
                                icon = '<i class="fas fa-key"></i>';
                                break;
                            case 'ssh-ed25519':
                                icon = '<i class="fas fa-lock"></i>';
                                break;
                            case 'ecdsa-sha2-nistp256':
                            case 'ecdsa-sha2-nistp384':
                            case 'ecdsa-sha2-nistp521':
                                icon = '<i class="fas fa-shield-alt"></i>';
                                break;
                            default:
                                icon = '<i class="fas fa-question"></i>';
                        }

                        var typeBadge = `<span style="
            background-color: #212121;
            color: #fff;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 4px;
            display: inline-block;
        ">${icon} ${data.type}</span>`;

                        var fingerprintBadge = `<span style="
            background-color: #000;
            color: #f44336;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 4px;
            display: inline-block;
        ">${data.fingerprint}</span>`;

                        var commentBadge = data.comment ? `<span style="
            background-color: #212121;
            color: #9e9e9e;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        ">${data.comment}</span>` : '';

                        return typeBadge + fingerprintBadge + commentBadge;
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
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
                $modalTitle.text('{{__('Product.puqProxmox.Create')}}');

                var formHtml = `
            <form id="createForm" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <label class="form-label" for="name">{{__('Product.puqProxmox.Name')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="name" name="name" placeholder="{{__('Product.puqProxmox.Name')}}">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="client_uuid" class="form-label">{{__('main.Client')}}</label>
                    <select name="client_uuid" id="client_uuid" class="form-select mb-2 form-control"></select>
                </div>

                <div class="mb-3">
                    <label for="public_key" class="form-label">{{ __('Product.puqProxmox.SSH Public Key') }}</label>
                        <textarea name="public_key" id="public_key" class="form-control" rows="8" ></textarea>
                </div>

            </form>`;
                $modalBody.html(formHtml);

                initializeSelect2(
                    $("#client_uuid"),
                    '{{route('admin.api.clients.select.get')}}',
                    {},
                    'GET',
                    1000,
                    {
                        dropdownParent: $('#universalModal')
                    },
                    {}
                );

                $('#universalModal').modal('show');
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{__('Product.puqProxmox.Are you sure you want to delete this record?')}}')) {
                    PUQajax(modelUrl, null, 1000, $(this), 'DELETE')
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

                    PUQajax('{{route('admin.api.Product.puqProxmox.ssh_public_key.post')}}', formData, 1000, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

        });
    </script>
@endsection
