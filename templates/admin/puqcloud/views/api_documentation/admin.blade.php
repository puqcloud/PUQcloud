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
                        <i class="fa fa-store"></i>
                    </span>
                        <span class="d-inline-block">{{__('main.Admin API Documentation')}}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav aria-label="breadcrumb">
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
                                    {{__('main.Admin API Documentation')}}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="api_documentations" class="main-card mb-3 card" style="min-height: 400px;">
        <div class="card-body">
            <input type="text" id="apiSearch" class="form-control mb-3" placeholder="Search API...">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle" id="apiDocsTable">
                    <thead class="table-light">
                    <tr>
                        <th>Module</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Version</th>
                        <th>Link</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function renderDocs(data) {
                const tbody = $('#apiDocsTable tbody');
                tbody.empty();

                data.forEach(doc => {
                    const row = `
                        <tr class="api-card">
                            <td>${doc.module}</td>
                            <td>${doc.info.title}</td>
                            <td>${doc.info.description}</td>
                            <td><span class="badge bg-secondary">${doc.info.version}</span></td>
                            <td>
                                <a href="${doc.web_url}" class="btn btn-sm btn-primary" target="_blank">Docs</a>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }

            $('#apiSearch').on('keyup', function () {
                const v = $(this).val().toLowerCase();
                $('.api-card').each(function () {
                    $(this).toggle($(this).text().toLowerCase().includes(v));
                });
            });

            function loadData() {
                blockUI('api_documentations');
                PUQajax('{{route('admin.api.api_documentations.admin.get')}}', {}, 50, null, 'GET')
                    .then(function (response) {
                        renderDocs(response.data);
                        unblockUI('api_documentations');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                        unblockUI('api_documentations');
                    });
            }

            loadData();
        });
    </script>
@endsection
