<style>
    table.dataTable tbody tr {
        transition: transform 0.3s ease, background-color 0.3s ease;
    }

    table.dataTable tbody tr.dt-rowReorder-moving {
        background-color: #d1ecf1 !important;
        opacity: 0.9;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        cursor: grabbing !important;
    }
</style>

<div class="main-card mb-2 card pt-0 mt-0">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{__('Product.puqProxmox.Firewall Rules') }}</h5>
        <button id="add" class="btn btn-primary"><i class="fas fa-plus me-1"></i>{{__('Product.puqProxmox.Create') }}
        </button>
    </div>
    <div class="card-body pt-0 mt-0">
        <table style="width: 100%;" id="firewallRules"
               class="table table-hover table-striped table-bordered align-middle">
            <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>{{__('Product.puqProxmox.Direction') }}</th>
                <th>{{__('Product.puqProxmox.Action') }}</th>
                <th>{{__('Product.puqProxmox.Protocol') }}</th>
                <th>{{__('Product.puqProxmox.Source') }}</th>
                <th>{{__('Product.puqProxmox.S.Port') }}</th>
                <th>{{__('Product.puqProxmox.Destination') }}</th>
                <th>{{__('Product.puqProxmox.D.Port') }}</th>
                <th>{{__('Product.puqProxmox.Comment') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            var tableId = '#firewallRules';
            var ajaxUrl = '{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getLxcFirewallRules']) }}';

            var columnsConfig = [
                {
                    data: "pos",
                    className: "text-center align-middle",
                    render: function (data) {
                        return `<div class="d-flex justify-content-center align-items-center">
                    <i class="fas fa-grip-vertical text-secondary me-2" style="cursor: grab;"></i>
                    <span class="fw-bold">${data}</span>
                </div>`;
                    }
                },
                {
                    data: "type",
                    className: "text-center",
                    render: function (data) {
                        if (data === "in") return `<span class="badge bg-success"><i class="fas fa-sign-in-alt me-1"></i>IN</span>`;
                        return `<span class="badge bg-primary"><i class="fas fa-sign-out-alt me-1"></i>OUT</span>`;
                    }
                },
                {
                    data: "action",
                    className: "text-center",
                    render: function (data) {
                        if (!data) return `<span class="badge bg-secondary">-</span>`;
                        if (data === "ACCEPT") return `<span class="badge bg-success"><i class="fas fa-check me-1"></i>ACCEPT</span>`;
                        if (data === "DROP") return `<span class="badge bg-danger"><i class="fas fa-ban me-1"></i>DROP</span>`;
                        if (data === "REJECT") return `<span class="badge bg-warning"><i class="fas fa-times-circle me-1"></i>REJECT</span>`;
                        return `<span class="badge bg-secondary">${data}</span>`;
                    }
                },
                {
                    data: "proto",
                    className: "text-center",
                    render: function (data) {
                        if (!data) return `<span class="badge bg-secondary"><i class="fas fa-asterisk me-1"></i>ANY</span>`;
                        if (data.toLowerCase() === "tcp") return `<span class="badge bg-primary"><i class="fas fa-network-wired me-1"></i>TCP</span>`;
                        if (data.toLowerCase() === "udp") return `<span class="badge bg-info"><i class="fas fa-network-wired me-1"></i>UDP</span>`;
                        if (data.toLowerCase() === "icmp") return `<span class="badge bg-warning"><i class="fas fa-bullseye me-1"></i>ICMP</span>`;
                        return `<span class="badge bg-dark"><i class="fas fa-network-wired me-1"></i>${data.toUpperCase()}</span>`;
                    }
                },
                {
                    data: "source",
                    render: function (data) {
                        if (!data) return `<span class="text-muted"><i class="fas fa-asterisk me-1"></i>ANY</span>`;
                        return `<i class="fas fa-map-marker-alt text-warning me-1"></i>${data}`;
                    }
                },
                {
                    data: "sport",
                    className: "text-center",
                    render: function (data) {
                        return data ? `<span class="badge bg-secondary"><i class="fas fa-arrow-up me-1"></i>${data}</span>` : `<span class="text-muted"><i class="fas fa-asterisk me-1"></i>ANY</span>`;
                    }
                },
                {
                    data: "dest",
                    render: function (data) {
                        if (!data) return `<span class="text-muted"><i class="fas fa-asterisk me-1"></i>ANY</span>`;
                        return `<i class="fas fa-server text-info me-1"></i>${data}`;
                    }
                },
                {
                    data: "dport",
                    className: "text-center",
                    render: function (data) {
                        return data ? `<span class="badge bg-secondary"><i class="fas fa-arrow-down me-1"></i>${data}</span>` : `<span class="text-muted"><i class="fas fa-asterisk me-1"></i>ANY</span>`;
                    }
                },
                {
                    data: "comment",
                    render: function (data) {
                        return data ? `<i class="fas fa-comment text-muted me-1"></i>${data}` : `<span class="text-muted">-</span>`;
                    }
                },
                {
                    data: "urls",
                    className: "text-center",
                    orderable: false,
                    render: function (data, type, row) {
                        if (row.urls?.delete) return `<button class="btn-icon-only btn-outline-2x btn btn-danger delete-btn" data-pos="${row.pos}" data-url="${row.urls.delete}"><i class="fas fa-trash-alt"></i></button>`;
                        return '';
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
                info: false,
                rowReorder: {
                    selector: 'i.fa-grip-vertical',
                    snapX: true,
                    update: false
                }
            });

            let oldPosMap = [];

            $dataTable.on('pre-row-reorder', function () {
                oldPosMap = $dataTable.rows({order: 'applied'}).data().toArray().map(r => r.pos);
            });

            $dataTable.on('row-reorder', function (e, details) {
                if (!details.length) return;
                let movedItems = details.filter(item => Math.abs(item.oldPosition - item.newPosition) > 1);
                let item = movedItems.length ? movedItems[0] : details[0];
                let oldPos = oldPosMap[item.oldPosition];
                let newPos = oldPosMap[item.newPosition];
                if (item.newPosition > item.oldPosition) newPos += 1;
                blockUI('firewallRules');
                PUQajax('{{ route('client.api.cloud.service.module.post', ['uuid' => $service_uuid, 'method' => 'postLxcFirewallRuleUpdateOrder']) }}', {
                    pos: oldPos,
                    moveto: newPos
                }, 50, null, 'POST').then(function (response) {
                    unblockUI('firewallRules');
                    if (response.status === "success") $dataTable.ajax.reload(null, false);
                });
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('url');
                if (confirm('{{__('Product.puqProxmox.Are you sure you want to delete this rule?') }}')) {
                    PUQajax(modelUrl, {pos: $(this).data('pos')}, 2000, $(this), 'POST').then(function (response) {
                        if (response.status === "success") $dataTable.ajax.reload(null, false);
                    });
                }
            });

            $('#add').on('click', function () {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text('{{__('Product.puqProxmox.Create Firewall Rule')}}');
                var formHtml = `
                <form id="addForm" class="col-md-10 mx-auto">
                    <div class="mb-3">
                        <label class="form-label" for="type"><i class="fas fa-exchange-alt text-success me-1"></i>{{__('Product.puqProxmox.Direction')}}</label>
                        <select class="form-select" id="type" name="type">
                            <option value="in">{{__('Product.puqProxmox.IN')}}</option>
                            <option value="out">{{__('Product.puqProxmox.OUT')}}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="action"><i class="fas fa-check-circle text-success me-1"></i>{{__('Product.puqProxmox.Action')}}</label>
                        <select class="form-select" id="action" name="action">
                            <option value="ACCEPT">{{__('Product.puqProxmox.ACCEPT')}}</option>
                            <option value="DROP">{{__('Product.puqProxmox.DROP')}}</option>
                            <option value="REJECT">{{__('Product.puqProxmox.REJECT')}}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="proto"><i class="fas fa-network-wired text-info me-1"></i>{{__('Product.puqProxmox.Protocol')}}</label>
                        <select class="form-select" id="proto" name="proto">
                            <option value="">ANY</option>
                            <option value="tcp">TCP</option>
                            <option value="udp">UDP</option>
                            <option value="icmp">ICMP</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="source"><i class="fas fa-map-marker-alt text-warning me-1"></i>{{__('Product.puqProxmox.Source')}}</label>
                        <input type="text" class="form-control" id="source" name="source" placeholder="{{__('Product.puqProxmox.Source IP')}}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="sport"><i class="fas fa-arrow-up text-secondary me-1"></i>{{__('Product.puqProxmox.Source Port')}}</label>
                        <input type="text" class="form-control" id="sport" name="sport" placeholder="{{__('Product.puqProxmox.Source Port')}}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="dest"><i class="fas fa-server text-info me-1"></i>{{__('Product.puqProxmox.Destination')}}</label>
                        <input type="text" class="form-control" id="dest" name="dest" placeholder="{{__('Product.puqProxmox.Destination IP')}}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="dport"><i class="fas fa-arrow-down text-secondary me-1"></i>{{__('Product.puqProxmox.Destination Port')}}</label>
                        <input type="text" class="form-control" id="dport" name="dport" placeholder="{{__('Product.puqProxmox.Destination Port')}}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="comment"><i class="fas fa-comment text-muted me-1"></i>{{__('Product.puqProxmox.Comment')}}</label>
                        <textarea class="form-control" id="comment" name="comment" rows="3" placeholder="{{__('Product.puqProxmox.Optional comment')}}"></textarea>
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

                    PUQajax('{{ route('client.api.cloud.service.module.post', ['uuid' => $service_uuid, 'method' => 'postLxcFirewallRuleCreate']) }}', formData, 1000, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });


        });
    </script>
@endsection
