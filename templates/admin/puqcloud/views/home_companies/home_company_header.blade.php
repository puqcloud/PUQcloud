<div id="header" class="app-page-title app-page-title-simple p-0">
    <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div>
                <div class="page-title-head center-elem m-0">
                                            <span class="d-inline-block pe-2">
                                                <i class="fa fa-cogs"></i>
                                            </span>{{ __('main.Product') }}
                    <span data-key="home_company" class="d-inline-block"></span>
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
                                <a href="{{route('admin.web.home_companies')}}">{{ __('main.Home Companies') }}</a>
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
                'general' => __('main.General'),
                'images' => __('main.Images'),
                'email_settings' => __('main.Email Settings'),
                'tax_settings' => __('main.Tax Settings'),
                'invoice_settings' => __('main.Invoice Settings'),
                'proforma_template' => __('main.Proforma Template'),
                'invoice_template' => __('main.Invoice Template'),
                'credit_note_template' => __('main.Credit Note Template'),
                'payment_gateways' => __('main.Payment Gateways'),
            ];
        @endphp

        @foreach($tabs as $key => $label)
            <li class="nav-item">
                <a
                    role="tab"
                    class="nav-link {{ $tab === $key ? 'active show' : '' }}"
                    href="{{ route('admin.web.home_company.tab', ['uuid' => $uuid, 'tab' => $key]) }}"
                    aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
                    <span>{{ $label }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>

@section('js')
    @parent

@endsection
