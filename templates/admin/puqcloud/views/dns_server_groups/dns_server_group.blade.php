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
                        <i class="fas fa-network-wired icon-gradient bg-primary"></i>
                    </span>
                        <span class="d-inline-block">{{ __('main.Edit DNS Server Group') }}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('main.Dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ route('admin.web.dns_server_groups') }}">{{ __('main.DNS Server Groups') }}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{ $uuid }}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <button id="save" type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                    <i class="fa fa-save"></i> {{__('main.Save')}}
                </button>
            </div>
        </div>
    </div>

    <form id="editDnsServerGroupForm" class="mx-auto" novalidate="novalidate">
        <div class="card mb-3">

            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-3">
                        <div class="mb-3">
                            <label class="form-label" for="name">{{ __('main.Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name" value="" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="description">{{ __('main.Description') }}</label>
                            <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-3">

                        <div class="mb-3">
                            <label class="form-label" for="ns_ttl">{{ __('main.NS TTL') }}</label>
                            <input type="number" min="0" step="1" class="form-control" id="ns_ttl" name="ns_ttl" value="" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="ns_domains">{{ __('main.NS Domains') }}</label>
                            <textarea class="form-control" id="ns_domains" name="ns_domains" rows="4"></textarea>
                            <small
                                class="form-text text-muted">{{ __('main.Each domain should be on a new line') }}</small>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-3">
                        <div class="mb-3">
                            <label for="dns_server_uuids" class="form-label">{{ __('main.DNS Servers') }}</label>
                            <select multiple name="dns_server_uuids" id="dns_server_uuids" class="form-select mb-2 form-control"></select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function loadFormData() {
                blockUI('editDnsServerGroupForm');
                const $form = $('#editDnsServerGroupForm');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{ route('admin.api.dns_server_group.get', $uuid) }}', {}, 5000, null, 'GET')
                    .then(function (response) {
                        if (response.data) {
                            const data = response.data;

                            $('#name').val(data.name || '');
                            $('#description').val(data.description || '');

                            $('#ns_ttl').val(data.ns_ttl || 3600);
                            $('#ns_domains').val((data.ns_domains || []).join("\n"));


                            var $elementDnsServer = $('#dns_server_uuids');
                            initializeSelect2($elementDnsServer, '{{route('admin.api.dns_servers.select.get')}}', data.dns_servers, 'GET', 1000, {});

                            unblockUI('editDnsServerGroupForm');
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                        unblockUI('editDnsServerGroupForm');
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#editDnsServerGroupForm");
                event.preventDefault();

                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.dns_server_group.put', $uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            loadFormData();
        });
    </script>
@endsection
