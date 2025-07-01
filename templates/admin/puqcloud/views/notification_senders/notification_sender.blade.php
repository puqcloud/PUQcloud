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
                                                <i class="fas fa-address-card"></i>
                                            </span>
                        <span class="d-inline-block">{{__('main.Edit Notification Sender')}}</span>
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
                                    <a href="{{route('admin.web.notification_senders')}}">{{__('main.Notification Senders')}}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{ request()->route('uuid') }}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                @if($admin->hasPermission('notification-senders-management'))
                    <button id="save" type="button"
                            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                        <i class="fa fa-save"></i> {{__('main.Save')}}
                    </button>
                @endif
            </div>
        </div>
    </div>
    <div id="mainCard" class="main-card mb-3 card">
        <div class="card-body">
            <form id="notification_sender" class="col-md-10 mx-auto" novalidate="novalidate">
                <div class="row">
                    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
                            <label class="form-label" for="name">{{__('main.Name')}}</label>
                            <div>
                                <input type="text" class="form-control input-mask-trigger" id="name" name="name"
                                       placeholder="{{__('main.Name')}}">
                            </div>
                    </div>
                    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
                        <label class="form-label" for="description">{{__('main.Description')}}</label>
                        <div>
                            <textarea name="description" id="description" class="form-control" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <hr>
                <div id="module_html"></div>
            </form>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function loadFormData() {
                blockUI('notification_sender');
                const $form = $('#notification_sender');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.notification_sender.get',$uuid)}}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $.each(response.data, function (key, value) {
                            const $element = $form.find(`[name="${key}"]`);
                            if ($element.length) {
                                $element.val(value);
                            }
                        });
                        $('#module_html').html(response.data.module_html);
                        unblockUI('notification_sender');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#notification_sender");
                event.preventDefault();

                const formData = serializeForm($form);

                PUQajax('{{route('admin.api.notification_sender.put',$uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });
            loadFormData();
        });
    </script>
@endsection
