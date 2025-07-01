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
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('Product.puqNextcloud.Dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.Product.puqNextcloud.servers')}}">{{ __('Product.puqNextcloud.Servers') }}</a>
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
                        id="test_connection"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary">
                    <i class="fa fa-plug"></i> {{__('Product.puqNextcloud.Test Connection')}}
                </button>

                <button type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                        id="save">
                    <i class="fa fa-save"></i>
                    {{__('Product.puqNextcloud.Save')}}
                </button>
            </div>

        </div>
    </div>

    <div id="container">
        <div class="card mb-3">
            <div class="card-body">
                <form id="serverForm" method="POST" action="" novalidate="novalidate">
                    @csrf
                    <div class="row">
                        <div class="col-12">
                            <div id="test_connection_data"></div>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-3">
                            <label for="name" class="form-label">{{ __('Product.puqNextcloud.Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name">
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-2 col-xxl-2 mb-3">
                            <label for="group_uuid" class="form-label">{{ __('Product.puqNextcloud.Group') }}</label>
                            <select name="group_uuid" id="group_uuid" class="form-select mb-2 form-control"></select>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-2 col-xxl-2 mb-3">
                            <label class="form-label" for="default">{{ __('Product.puqNextcloud.Default') }}</label>
                            <div>
                                <input type="checkbox"
                                       data-toggle="toggle"
                                       id="default"
                                       name="default"
                                       data-off="{{ __('Product.puqNextcloud.No') }}"
                                       data-on="{{ __('Product.puqNextcloud.Yes') }}"
                                       data-onstyle="success"
                                       data-offstyle="danger">
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-3">
                            <label for="max_accounts"
                                   class="form-label">{{ __('Product.puqNextcloud.Max Accounts') }}</label>
                            <input type="number" class="form-control" id="max_accounts" name="max_accounts" min="0"
                                   value="0">
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-1 col-xxl-1 mb-3">
                            <label class="form-label" for="active">{{ __('Product.puqNextcloud.Active') }}</label>
                            <div>
                                <input type="checkbox"
                                       data-toggle="toggle"
                                       id="active"
                                       name="active"
                                       data-off="{{ __('Product.puqNextcloud.No') }}"
                                       data-on="{{ __('Product.puqNextcloud.Yes') }}"
                                       data-onstyle="success"
                                       data-offstyle="danger">
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-3">
                            <label for="host" class="form-label">{{ __('Product.puqNextcloud.Host') }}</label>
                            <input type="text" class="form-control" id="host" name="host">
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-3">
                            <label for="username" class="form-label">{{ __('Product.puqNextcloud.Username') }}</label>
                            <input type="text" class="form-control" id="username" name="username">
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-3">
                            <label for="password" class="form-label">{{ __('Product.puqNextcloud.Password') }}</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-2 col-xxl-2 mb-3">
                            <label class="form-label" for="ssl">{{ __('Product.puqNextcloud.Use SSL') }}</label>
                            <div>
                                <input type="checkbox"
                                       data-toggle="toggle"
                                       id="ssl"
                                       name="ssl"
                                       data-off="{{ __('Product.puqNextcloud.No') }}"
                                       data-on="{{ __('Product.puqNextcloud.Yes') }}"
                                       data-onstyle="success"
                                       data-offstyle="danger">
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-1 col-xxl-1 mb-3">
                            <label for="port" class="form-label">{{ __('Product.puqNextcloud.Port') }}</label>
                            <input type="number" class="form-control" id="port" name="port" value="443">
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            function loadFormData() {
                blockUI('container');

                PUQajax('{{route('admin.api.Product.puqNextcloud.server.get',$uuid)}}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $("#name").val(response.data?.name);
                        $("#host").val(response.data?.host);
                        $("#username").val(response.data?.username);
                        $("#password").val(response.data?.password);
                        $("#max_accounts").val(response.data?.max_accounts);
                        $("#port").val(response.data?.port);

                        $("#ssl").prop('checked', !!response.data?.ssl).trigger('click');
                        $("#active").prop('checked', !!response.data?.active).trigger('click');
                        $("#default").prop('checked', !!response.data?.default).trigger('click');

                        initializeSelect2($("#group_uuid"), '{{route('admin.api.Product.puqNextcloud.server_groups.select.get')}}', response.data?.group_data, 'GET', 1000, {});
                        testConnection($("#test_connection"));
                        unblockUI('container');
                    })

                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#serverForm");
                event.preventDefault();

                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.Product.puqNextcloud.server.put', $uuid)}}', formData, 1000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            $("#test_connection").on("click", function (event) {
                testConnection($(this));
            });

            function testConnection($b = null) {
                const $form = $("#serverForm");
                event.preventDefault();
                blockUI('test_connection_data');

                const formData = serializeForm($form);
                PUQajax('{{ route('admin.api.Product.puqNextcloud.server.test_connection.post', $uuid) }}', formData, 1000, $b, 'POST', $form)
                    .then(function (response) {
                        let html = '';

                        if (response.status === 'success') {
                            html += `
                    <div class="alert alert-success" role="alert">
                        ✅ <strong>{{ __('Product.puqNextcloud.Test Connection Success') }}</strong>
                        {{ __('Product.puqNextcloud.Test Connection Success Desc') }}
                            </div>
`;
                            html += '<div class="row">';
                            html += renderTestConnection(response.data);
                            html += '</div>';
                        }
                        $("#test_connection_data").html(html);
                        unblockUI('test_connection_data');

                    })
                    .catch(function (error) {
                        $("#test_connection_data").html(`<div class="alert alert-danger" role="alert">
                ❌ <strong>{{ __('Product.puqNextcloud.Test Connection Failed') }}</strong>
                {{ __('Product.puqNextcloud.Test Connection Failed Desc') }}
                            </div>`
                        );
                        unblockUI('test_connection_data');
                    });
            }

            function renderTestConnection(data) {
                const formatBytes = (bytes) => {
                    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                    if (bytes === 0) return '0 B';
                    const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)), 10);
                    return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`;
                };

                const icons = {
                    system: 'fas fa-server',
                    storage: 'fas fa-hdd',
                    shares: 'fas fa-share-alt',
                    server: 'fas fa-cogs',
                    php: 'fab fa-php',
                    database: 'fas fa-database',
                    activeUsers: 'fas fa-users'
                };

                const col = (title, icon, rows) => `
        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-4 col-xxl-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <i class="${icon} me-2"></i><strong>${title}</strong>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush small">
                        ${rows.map(r => `<li class="list-group-item d-flex justify-content-between"><span>${r[0]}</span><span class="text-end">${r[1]}</span></li>`).join('')}
                    </ul>
                </div>
            </div>
        </div>
    `;

                const rowsSystem = [
                    ['{{ __("Product.puqNextcloud.Version") }}', data.nextcloud.system.version],
                    ['{{ __("Product.puqNextcloud.Theme") }}', data.nextcloud.system.theme],
                    ['{{ __("Product.puqNextcloud.Avatars") }}', data.nextcloud.system.enable_avatars],
                    ['{{ __("Product.puqNextcloud.Previews") }}', data.nextcloud.system.enable_previews],
                    ['{{ __("Product.puqNextcloud.Free Space") }}', formatBytes(data.nextcloud.system.freespace)],
                    ['{{ __("Product.puqNextcloud.CPU Load") }}', data.nextcloud.system.cpuload.join(', ')],
                    ['{{ __("Product.puqNextcloud.CPU Cores") }}', data.nextcloud.system.cpunum],
                    ['{{ __("Product.puqNextcloud.RAM Total") }}', formatBytes(data.nextcloud.system.mem_total * 1024)],
                    ['{{ __("Product.puqNextcloud.RAM Free") }}', formatBytes(data.nextcloud.system.mem_free * 1024)],
                ];

                const rowsServer = [
                    ['{{ __("Product.puqNextcloud.Webserver") }}', data.server.webserver],
                    ['{{ __("Product.puqNextcloud.PHP Version") }}', data.server.php.version],
                    ['{{ __("Product.puqNextcloud.PHP Mem Limit") }}', formatBytes(data.server.php.memory_limit)],
                    ['{{ __("Product.puqNextcloud.Max Exec Time") }}', data.server.php.max_execution_time + 's'],
                    ['{{ __("Product.puqNextcloud.Upload Limit") }}', formatBytes(data.server.php.upload_max_filesize)],
                    ['{{ __("Product.puqNextcloud.DB Type") }}', data.server.database.type],
                    ['{{ __("Product.puqNextcloud.DB Version") }}', data.server.database.version],
                    ['{{ __("Product.puqNextcloud.DB Size") }}', formatBytes(parseInt(data.server.database.size))],
                ];

                const rowsUsers = Object.entries(data.activeUsers).map(([k, v]) => [
                    `{{ __("Product.puqNextcloud.Active") }} ${k}`,
                    v
                ]);

                return `
        ${col('{{ __("Product.puqNextcloud.System Info") }}', icons.system, rowsSystem)}
        ${col('{{ __("Product.puqNextcloud.Server Info") }}', icons.server, rowsServer)}
        ${col('{{ __("Product.puqNextcloud.Active Users") }}', icons.activeUsers, rowsUsers)}
    `;
            }

            loadFormData();
        });
    </script>
@endsection
