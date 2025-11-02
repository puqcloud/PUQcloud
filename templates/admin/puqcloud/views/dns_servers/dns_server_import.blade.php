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
                        <i class="fa-solid fa-file-import me-1"></i>
                    </span>
                        <span class="d-inline-block">{{ __('main.Import Zones') }}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a><i aria-hidden="true" class="fa fa-home"></i></a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('main.Dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dns_servers')}}">{{__('main.DNS Servers')}}</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dns_server',$uuid)}}">{{$uuid}}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{__('main.Import Zones')}}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions"></div>
        </div>
    </div>

    {{-- Server Info --}}
    <div id="mainCard" class="main-card mb-3 card">
        <div class="card-body">
            <form id="dns_server" class="col-md-10 mx-auto" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-1">
                        <label class="form-label" for="name">{{__('main.Name')}}</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="{{__('main.Name')}}"
                               disabled>
                    </div>
                    <div class="col-md-6 mb-1">
                        <label class="form-label" for="description">{{__('main.Description')}}</label>
                        <textarea name="description" id="description" class="form-control" rows="1" disabled></textarea>
                    </div>
                </div>

                {{-- Import options --}}
                <div class="row mt-3">
                    <div class="col-md-4 mb-2">
                        <label class="form-label" for="import_mode">{{__('main.Import Mode')}}</label>
                        <select id="import_mode" name="import_mode" class="form-select">
                            <option value="add">{{__('main.Add only new records')}}</option>
                            <option value="replace">{{__('main.Replace all zone')}}</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label" for="dns_server_group_uuid">{{__('main.DNS Server Group')}}</label>
                        <select name="dns_server_group_uuid" id="dns_server_group_uuid" class="form-select">
                            <option value="">{{__('main.Select group')}}</option>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-end mb-2">
                        <button type="button" id="btn_import" class="btn btn-primary w-100">
                            <i class="fa-solid fa-download me-2"></i>{{__('main.Import Selected')}}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Zones table --}}
    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="dns_zones"
                   class="table table-hover table-striped table-bordered align-middle">
                <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">
                        <input type="checkbox" id="select_all">
                    </th>
                    <th>{{ __('main.Name') }}</th>
                    <th>{{ __('main.Local') }}</th>
                </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                <tr>
                    <th></th>
                    <th>{{ __('main.Name') }}</th>
                    <th>{{ __('main.Local') }}</th>
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
            const $tableId = $('#dns_zones');
            const ajaxUrl = '{{ route('admin.api.dns_server.dns_zones.get',$uuid) }}';

            const columnsConfig = [
                {
                    data: null,
                    orderable: false,
                    render: function (data, type, row) {
                        return `<input type="checkbox" class="zone-checkbox" value="${row.name}">`;
                    },
                    className: "text-center"
                },
                {
                    data: "name",
                    name: "name",
                    render: data => `<strong class="highlight-name">${data}</strong>`
                },
                {
                    data: "local",
                    name: "local",
                    render: function (data) {
                        if (data === true || data === 1 || data === '1') {
                            return '<span class="badge bg-success">{{__("main.Yes")}}</span>';
                        } else {
                            return '<span class="badge bg-danger">{{__("main.No")}}</span>';
                        }
                    },
                    className: "text-center"
                }
            ];

            const $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig,
                () => ({}),
                {
                    select: false,
                    serverSide: false,
                    paging: false,
                }
            );

            // Select all checkboxes
            $('#select_all').on('change', function () {
                const checked = $(this).prop('checked');
                $('.zone-checkbox').prop('checked', checked);
            });

            function loadFormData() {
                blockUI('dns_server');
                const $form = $('#dns_server');
                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.dns_server.get',$uuid)}}', {}, 50, null, 'GET')
                    .then(response => {
                        $('#name').val(response.data.name || '');
                        $('#description').val(response.data.description || '');
                        initializeSelect2($('#dns_server_group_uuid'), '{{route('admin.api.dns_server_groups.select.get')}}', '', 'GET', 1000);
                        unblockUI('dns_server');
                    })
                    .catch(error => {
                        console.error('Error loading form data:', error);
                    });
            }

            // Import button
            $('#btn_import').on('click', function () {
                const selected = $('.zone-checkbox:checked').map(function () {
                    return $(this).val();
                }).get();

                const $form = $("#dns_server");
                event.preventDefault();

                const formData = serializeForm($form);
                formData.zones = selected;

                blockUI('mainCard');

                PUQajax('{{route('admin.api.dns_server.import_zones.post',$uuid)}}',
                    formData,
                    5000,
                    $(this),
                    'POST',
                    $form
                ).then(response => {
                    unblockUI('mainCard');
                }).catch(error => {
                    unblockUI('mainCard');
                    console.error('Import error:', error);
                });
            });

            loadFormData();
        });
    </script>
@endsection
