@php
    $backgroundUrl = $product_group->images['background'] ?? null;
@endphp

@if($backgroundUrl)
    @section('background')
        <style>
            .puq-background-blur {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.5)),
                url('{{ $backgroundUrl }}') no-repeat center center fixed;
                background-size: cover;
                filter: blur(6px);
                z-index: 0;
            }
        </style>
    @endsection
@endif

<div id="header" class="app-page-title">
    <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div class="page-title-icon">
                <i class="fa fa-key icon-gradient bg-ripe-malin"></i>
            </div>
            <div>
                <div class="page-title-head center-elem">
                    <span class="d-inline-block">
                        {{ __('Product.puqProxmox.Manage SSH Keys') }}
                    </span>
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
                                <a href="{{ route('client.web.panel.cloud.group', $product_group->uuid) }}">{{ $title }}</a>
                            </li>
                            <li class="active breadcrumb-item" aria-current="page">
                                {{ __('Product.puqProxmox.Manage SSH Keys') }}
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        <div class="page-title-actions">

            <a href="{{ route('client.web.panel.cloud.group', $product_group->uuid) }}"
               class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i>
                {{ __('Product.puqProxmox.Back') }}
            </a>

            <button type="button"
                    class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                    id="add">
                <i class="fa fa-plus"></i>
                {{__('Product.puqProxmox.Add Public SSH Key')}}
            </button>
        </div>
    </div>
</div>

<div class="container px-0">
    <div class="main-card card">
        <div class="card-body">
            <table style="width: 100%;" id="ssh_public_keys"
                   class="table table-hover table-striped table-bordered w-100">
                <thead class="table-light">
                <tr>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
                    <th>{{__('Product.puqProxmox.Info')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            blockUI('mainInner');
            var $table = $('#ssh_public_keys');
            var ajaxUrl = '{{ route('client.api.cloud.group.list.get',['uuid'=>$product_group->uuid,'method'=>'GetSshPublicKeys']) }}';
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
                    data: "info",
                    name: "info",
                    render: function (data, type, row) {
                        if (!data) return '';

                        var icon = '';
                        switch(data.type) {
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
                    title: '',
                    className: "center",
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                },
            ];

            var $dataTable = initializeDataTable($table, ajaxUrl, columnsConfig, DataTableAddData, {});

            function DataTableAddData() {
                return {};
            }

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{__('Product.puqProxmox.Are you sure you want to delete this record?')}}')) {
                    PUQajax(modelUrl, null, 1000, null, 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $('#add').on('click', function () {

                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text('{{__('Product.puqProxmox.Create')}}');

                var formHtml = `
            <form id="addForm" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <label class="form-label" for="name">{{__('Product.puqProxmox.Name')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="name" name="name" placeholder="{{__('Product.puqProxmox.Name')}}">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="public_key" class="form-label">{{ __('Product.puqProxmox.SSH Public Key') }}</label>
                        <textarea name="public_key" id="public_key" class="form-control" rows="8" ></textarea>
                </div>
            </form>`;
                $modalBody.html(formHtml);
                $('#universalModal').modal('show');
            });


            $('#modalConfirmButton').on('click', function (event) {
                event.preventDefault();

                if ($('#addForm').length) {
                    var $form = $('#addForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('client.api.cloud.group.list.post',['uuid'=>$product_group->uuid,'method'=>'PostSshPublicKey']) }}', formData, 1000, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

            unblockUI('mainInner');

        });
    </script>
@endsection
