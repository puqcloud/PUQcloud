@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')
    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="fas fa-globe icon-gradient bg-primary"></i>
                </div>
                <div>
                    {{__('main.DNS Zones')}}
                    <div class="page-title-subheading">
                        {{__('main.This is where you configure the DNS Zones')}}</div>
                </div>
            </div>
            <div class="page-title-actions">
                    <button type="button"
                            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                            data-bs-toggle="modal" data-bs-target="#universalModal">
                        <i class="fa fa-plus"></i>
                        {{__('main.Create')}}
                    </button>
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="dns_zones" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{ __('main.Name') }}</th>
                    <th>{{ __('main.Record Count') }}</th>
                    <th>{{ __('main.Server Group') }}</th>
                    <th>{{ __('main.Primary NS') }}</th>
                    <th>{{ __('main.Admin Email') }}</th>
                    <th>{{ __('main.Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{ __('main.Name') }}</th>
                    <th>{{ __('main.Record Count') }}</th>
                    <th>{{ __('main.Server Group') }}</th>
                    <th>{{ __('main.Primary NS') }}</th>
                    <th>{{ __('main.Admin Email') }}</th>
                    <th>{{ __('main.Actions') }}</th>
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
            var $tableId = $('#dns_zones');
            var ajaxUrl = '{{ route('admin.api.dns_zones.get') }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name",
                    render: function(data, type, row) {
                        return '<strong class="highlight-name">' + data + '</strong>';
                    }
                },
                { data: "dns_record_count", name: "dns_record_count" },
                { data: "dns_server_group.name", name: "dns_server_group.name" },
                { data: "soa_primary_ns", name: "soa_primary_ns" },
                { data: "soa_admin_email", name: "soa_admin_email" },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.edit) {
                            btn += renderEditButton(row.urls.edit);
                        }
                        if (row.urls.delete) {
                            btn += renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                },
            ];


            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig);

            $tableId.on('click', 'button.delete-btn', function (e) {
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

            $tableId.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();
                window.location.href = $(this).data('model-url');
            });

            $('#universalModal').on('show.bs.modal', function (event) {
                var $modalTitle = $(this).find('.modal-title');
                var $modalBody = $(this).find('.modal-body');

                $modalTitle.text(translate('Create DNS Zone'));

                var formHtml = `
<form id="createDnsZone" class="col-md-10 mx-auto">

    <!-- Name -->
    <div class="mb-3">
        <label class="form-label" for="name">${translate('Name')}</label>
        <input type="text" class="form-control" id="name" name="name" placeholder="${translate('example.com')}">
    </div>

    <!-- DNS Server Group -->
    <div class="mb-3">
        <label class="form-label" for="dns_server_group_uuid">${translate('DNS Server Group')}</label>
        <select name="dns_server_group_uuid" id="dns_server_group_uuid" class="form-select">
            <option value="">${translate('Select group')}</option>
        </select>
    </div>

    <!-- SOA Admin Email -->
    <div class="mb-3">
        <label class="form-label" for="soa_admin_email">${translate('SOA Admin Email')}</label>
        <input type="email" class="form-control" id="soa_admin_email" name="soa_admin_email" placeholder="${translate('admin@example.com')}">
    </div>

    <!-- SOA TTL -->
    <div class="mb-3">
        <label class="form-label" for="soa_ttl">${translate('SOA TTL (seconds)')}</label>
        <input type="number" class="form-control" id="soa_ttl" name="soa_ttl" value="3600">
    </div>

    <!-- SOA Refresh -->
    <div class="mb-3">
        <label class="form-label" for="soa_refresh">${translate('SOA Refresh (seconds)')}</label>
        <input type="number" class="form-control" id="soa_refresh" name="soa_refresh" value="86400">
    </div>

    <!-- SOA Retry -->
    <div class="mb-3">
        <label class="form-label" for="soa_retry">${translate('SOA Retry (seconds)')}</label>
        <input type="number" class="form-control" id="soa_retry" name="soa_retry" value="7200">
    </div>

    <!-- SOA Expire -->
    <div class="mb-3">
        <label class="form-label" for="soa_expire">${translate('SOA Expire (seconds)')}</label>
        <input type="number" class="form-control" id="soa_expire" name="soa_expire" value="1209600">
    </div>

    <!-- SOA Minimum -->
    <div class="mb-3">
        <label class="form-label" for="soa_minimum">${translate('SOA Minimum TTL (seconds)')}</label>
        <input type="number" class="form-control" id="soa_minimum" name="soa_minimum" value="3600">
    </div>

</form>`;


                $modalBody.html(formHtml);

                var $elementDnsServerGroup = $modalBody.find('[name="dns_server_group_uuid"]');
                initializeSelect2($elementDnsServerGroup, '{{route('admin.api.dns_server_groups.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                var $form = $('#createDnsZone');
                $form.on('keydown', function (event) {
                    if (event.key === 'Enter' && !$(event.target).is('textarea')) {
                        event.preventDefault();
                    }
                });
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();
                var $form = $('#createDnsZone');

                if ($form.length === 0) {
                    console.error("Form not found");
                    return;
                }

                var formData = serializeForm($form);

                PUQajax('{{route('admin.api.dns_zone.post')}}', formData, 500, $(this), 'POST', $form)
                    .then(function (response) {
                        $('#universalModal').modal('hide');
                    });
            });
        });
    </script>

@endsection
