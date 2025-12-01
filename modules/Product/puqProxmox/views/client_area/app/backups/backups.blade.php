<div class="main-card mb-2 card">
    <div class="card-body">
        <table style="width: 100%;" id="backups"
               class="table table-hover table-striped table-bordered">
            <thead>
            <tr>
                <th>{{__('Product.puqProxmox.Note')}}</th>
                <th>{{__('Product.puqProxmox.Date')}}</th>
                <th>{{__('Product.puqProxmox.Size')}}</th>
                <th>{{__('Product.puqProxmox.Actions')}}</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            var tableId = '#backups';
            var ajaxUrl = '{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getAppBackups']) }}';
            var columnsConfig = [
                {
                    data: "note",
                    name: "note",
                },
                {
                    data: 'date',
                    render: function (data) {
                        if (!data) return '';
                        var date = new Date(data * 1000);
                        return date.toLocaleString();
                    }
                },
                {
                    data: 'size',
                    render: function (data) {
                        if (!data) return '0 GB';
                        return (data / (1024 ** 3)).toFixed(2) + ' GB';
                    }
                },

                {
                    data: 'urls',
                    className: "text-center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btns = '';
                        if (row.urls?.restore) {
                            btns += `<button class="btn-icon btn-2x btn btn-success restore-btn mb-1 me-1"
                           data-url="${row.urls.restore}"
                           data-size="${row.size}"
                           data-date="${row.date}">
                        <i class="fas fa-undo-alt me-1"></i>{{__('Product.puqProxmox.Restore')}}
                            </button>`;
                        }
                        if (row.urls?.delete) {
                            btns += `<button class="btn-icon btn-2x btn btn-danger delete-btn mb-1 me-1"
                           data-url="${row.urls.delete}"
                           data-size="${row.size}"
                           data-date="${row.date}">
                        <i class="fas fa-trash-alt me-1"></i>{{__('Product.puqProxmox.Delete')}}
                            </button>`;
                        }
                        return btns;
                    }
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig, function () {
                return {};
            }, {
                paging: false,
                searching: false,
                lengthChange: false,
                ordering: false,
                info: false
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('url');

                if (confirm('{{__('Product.puqProxmox.Are you sure you want to delete this backup?')}}')) {
                    PUQajax(modelUrl, {
                        size: $(this).data('size'),
                        date: $(this).data('date'),
                    }, 2000, $(this), 'POST')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                                loadInfoData();
                            }
                        });
                }
            });


            $dataTable.on('click', 'button.restore-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('url');

                if (confirm('{{__('Product.puqProxmox.Are you sure you want to restore this backup?')}}')) {
                    PUQajax(modelUrl, {
                        size: $(this).data('size'),
                        date: $(this).data('date'),
                    }, 2000, $(this), 'POST')
                        .then(function (response) {
                            if (response.status === "success") {
                                loadInfoData();
                            }
                        });
                }
            });

        });

    </script>
@endsection

