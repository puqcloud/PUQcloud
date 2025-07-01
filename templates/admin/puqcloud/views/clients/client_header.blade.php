<div id="header" class="app-page-title app-page-title-simple p-0">
    <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div>
                <div class="page-title-head center-elem m-0">
                                            <span class="d-inline-block pe-2">
                                                <i class="fas fa-user"></i>
                                            </span>
                    <span data-key="client" class="d-inline-block"></span>
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
                            <li class="breadcrumb-item">
                                <a href="{{route('admin.web.clients')}}">{{ __('main.Clients') }}</a>
                            </li>
                            <li class="active breadcrumb-item" aria-current="page">
                                {{$uuid}}
                            </li>
                            <li class="active breadcrumb-item" aria-current="page">
                                {{$title}}
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        <div class="page-title-actions">
            @yield('buttons')
        </div>
    </div>
</div>

<div class="p-0">
    <ul class="body-tabs body-tabs-layout tabs-animated body-tabs-animated nav p-0">
        @php
            $tabs = [
                'summary' => __('main.Summary'),
                'profile' => __('main.Profile'),
                'users' => __('main.Users'),
                'services' => __('main.Services'),
                'invoices' => __('main.Invoices'),
                //'domains' => __('main.Domains'),
                'transactions' => __('main.Transactions'),
                //'tickets' => __('main.Tickets'),
                //'notifications' => __('main.Notifications'),
                //'session-log' => __('main.Session Log'),
            ];
        @endphp

        @foreach($tabs as $key => $label)
            <li class="nav-item">
                <a
                    role="tab"
                    class="nav-link {{ $tab === $key ? 'active show' : '' }}"
                    href="{{ route('admin.web.client.tab', ['uuid' => $uuid, 'tab' => $key]) }}"
                    aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
                    <span>{{ $label }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>


@section('js')
    @parent
    <script>
        function loadData() {
            blockUI('appMainInner');
            PUQajax('{{route('admin.api.client.get',$uuid)}}', {}, 50, null, 'GET')
                .then(function (response) {
                    var $spanElement = $('[data-key="client"]');
                    if ($spanElement.length) {

                        status = `<div style="margin-left: 10px;" class="badge bg-${getClientStatusLabelClass(response.data.status)}">${translate(response.data.status)}</div>`;
                        if (response.data.company_name) {
                            $spanElement.text(response.data.company_name);
                        } else {
                            $spanElement.text(response.data.firstname + ' ' + response.data.lastname);
                        }
                        var $nextElement = $spanElement.next();
                        if ($nextElement.hasClass('badge')) {
                            $nextElement.remove();
                        }
                        balance = `
<div style="margin-left: 10px; width: 100%; max-width: 200px;">
  <div style="margin-bottom: 3px; font-size: 12px; text-align: center; line-height: 1;">
    ${response.data.balance} / ${response.data.credit_limit} ${response.data.currency_data.text}
  </div>
  <div class="progress-bar-xs progress" style="height: 8px;">
    ${
                            response.data.balance < 0
                                ? `
        <div class="progress-bar bg-danger" role="progressbar"
             style="width: ${response.data.credit_limit != 0 ? Math.min(Math.abs(response.data.balance) / response.data.credit_limit * 100, 100) : 0}%"
             aria-valuenow="${Math.abs(response.data.balance)}" aria-valuemin="0" aria-valuemax="${response.data.credit_limit}">
        </div>
        <div class="progress-bar bg-success" role="progressbar"
             style="width: ${response.data.credit_limit != 0 ? Math.max(100 - Math.abs(response.data.balance) / response.data.credit_limit * 100, 0) : 100}%"
             aria-valuenow="${response.data.credit_limit - Math.abs(response.data.balance)}" aria-valuemin="0" aria-valuemax="${response.data.credit_limit}">
        </div>`
                                : `
        <div class="progress-bar bg-success" role="progressbar"
             style="width: 100%;" aria-valuenow="${response.data.balance}" aria-valuemin="0" aria-valuemax="${response.data.credit_limit}">
        </div>`
                        }
  </div>
</div>
`;
                        $spanElement.after(balance);
                        $spanElement.after(status);

                    }
                    unblockUI('appMainInner');
                })
                .catch(function (error) {
                    console.error('Error loading form data:', error);
                });
        }

        $(document).ready(function () {
            loadData();
        });
    </script>
@endsection
