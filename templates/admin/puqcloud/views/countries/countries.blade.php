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
                                                <i class="fas fa-globe"></i>
                                            </span>
                        <span class="d-inline-block">{{__('main.Countries')}}</span>
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
                                    {{__('main.Countries')}}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="countries"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Flag')}}</th>
                    <th>{{__('main.Code')}}</th>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Calling Code')}}</th>
                    <th>{{__('main.Regions')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Flag')}}</th>
                    <th>{{__('main.Code')}}</th>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Calling Code')}}</th>
                    <th>{{__('main.Regions')}}</th>
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
            var ajaxUrl = '{{ route('admin.api.countries.get') }}';
            var columnsConfig = [
                {
                    data: "code", name: "code",
                    render: function (data, type, row) {
                        return `
                        <div class="flag ${row.code} large mx-auto"></div>
                        `;
                    }
                },
                {data: "code", name: "code"},
                {
                    data: "name",
                    name: "name",
                    render: function (data, type, row) {
                        return `
                            ${row.name} (${row.native_name})`;
                    }
                },
                {data: "calling_code", name: "calling_code"},
                {data: "regions_count", name: "regions_count"},
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';

                        if (row.urls.regions) {
                            btn = btn + renderViewButton(row.urls.regions);
                        }

                        if (row.urls.post) {
                            btn = btn + renderEditButton(row.urls.post);
                        }

                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }

                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig);

            $dataTable.on('click', 'button.view-btn', function (e) {
                e.preventDefault();
                const modelUrl = $(this).data('model-url');
                PUQajax(modelUrl, null, 500, $(this), 'GET', null)
                    .then(function (response) {
                        displayModalData(response.data);
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            });

            function displayModalData(data) {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $('#universalModal #modalSaveButton').remove();
                $('#universalModal .modal-dialog').css({
                    'min-width': '90%',
                    'width': '90%'
                });
                $modalTitle.text(translate('Regions'));

                let formattedData = `
        <div class="row mb-2">
            <div class="col-5 font-weight-bold"><b>${translate('Region Name')}</b></div>
            <div class="col-5 font-weight-bold"><b>${translate('Native Name')}</b></div>
            <div class="col-2 font-weight-bold"><b>${translate('Code')}</b></div>
        </div>
    `;
                data.forEach(region => {
                    formattedData += `
        <div class="list-group-item">
            <div class="row mb-2">
                <div class="col-5">${region.name}</div>
                <div class="col-5">${region.native_name}</div>
                <div class="col-2">${region.code}</div>
            </div>
        </div>
        `;
                });
                $modalBody.html(formattedData);
                $('#universalModal').modal('show');
            }

        });
    </script>
@endsection
