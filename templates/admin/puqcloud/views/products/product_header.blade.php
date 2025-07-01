<div id="header" class="app-page-title app-page-title-simple p-0">
    <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div>
                <div class="page-title-head center-elem m-0">
                                            <span class="d-inline-block pe-2">
                                                <i class="fa fa-cogs"></i>
                                            </span>{{ __('main.Product') }}
                    <span data-key="product" class="d-inline-block"></span>
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
                                <a href="{{route('admin.web.products')}}">{{ __('main.Products') }}</a>
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
                'pricing' => __('main.Pricing'),
                'attributes' => __('main.Attributes'),
                'options' => __('main.Options'),
                'module' => __('main.Module'),
            ];
        @endphp

        @foreach($tabs as $key => $label)
            <li class="nav-item">
                <a
                    role="tab"
                    class="nav-link {{ $tab === $key ? 'active show' : '' }}"
                    href="{{ route('admin.web.product.tab', ['uuid' => $uuid, 'tab' => $key]) }}"
                    aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
                    <span>{{ $label }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>

@section('js')
    @parent
{{--    <script>--}}
{{--        function loadData() {--}}
{{--            blockUI('appMainInner');--}}
{{--            PUQajax('{{route('admin.api.product.get',$uuid)}}', {}, 50, null, 'GET')--}}
{{--                .then(function (response) {--}}
{{--                    var $spanElement = $('[data-key="product"]');--}}
{{--                    if ($spanElement.length) {--}}

{{--                        $status = `<div style="margin-left: 10px;" class="badge bg-${getClientStatusLabelClass(response.data.status)}">${response.data.status}</div>`;--}}
{{--                        if (response.data.company_name) {--}}
{{--                            $spanElement.text(response.data.company_name);--}}
{{--                        } else {--}}
{{--                            $spanElement.text(response.data.firstname + ' ' + response.data.lastname);--}}
{{--                        }--}}
{{--                        var $nextElement = $spanElement.next();--}}
{{--                        if ($nextElement.hasClass('badge')) {--}}
{{--                            $nextElement.remove();--}}
{{--                        }--}}
{{--                        $spanElement.after($status);--}}
{{--                    }--}}
{{--                    unblockUI('appMainInner');--}}
{{--                })--}}
{{--                .catch(function (error) {--}}
{{--                    console.error('Error loading form data:', error);--}}
{{--                });--}}
{{--        }--}}

{{--        $(document).ready(function () {--}}
{{--            loadData();--}}
{{--        });--}}
{{--    </script>--}}
@endsection
