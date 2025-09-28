<div class="row" id="module">
    @if($php_mail_enabled)
        <div class="no-results">
            <div class="swal2-icon swal2-success swal2-animate-success-icon">
                <div class="swal2-success-circular-line-left" style="background-color: rgb(255, 255, 255);"></div>
                <span class="swal2-success-line-tip"></span>
                <span class="swal2-success-line-long"></span>
                <div class="swal2-success-ring"></div>
                <div class="swal2-success-fix" style="background-color: rgb(255, 255, 255);"></div>
                <div class="swal2-success-circular-line-right" style="background-color: rgb(255, 255, 255);"></div>
            </div>
            <div class="results-subtitle mt-4">mail()</div>
            <div class="results-title">{{__('Notification.puqPHPmail.The function is enabled on the server')}}</div>
            <div class="mt-3 mb-3"></div>
        </div>
    @else
        <div class="no-results">
            <div class="swal2-icon swal2-error swal2-animate-error-icon">
                <span class="swal2-x-mark-line-left"></span>
                <span class="swal2-x-mark-line-right"></span>
            </div>

            <div class="results-subtitle mt-4">mail()</div>
            <div class="results-title">{{__('Notification.puqPHPmail.The function is disabled on the server')}}</div>
            <div class="mt-3 mb-3"></div>
        </div>
    @endif

    <input type="hidden" id="uuid" name="uuid" value="{{$uuid}}">
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="email">{{__('Notification.puqPHPmail.Email')}}</label>
            <div>
                <input type="text" class="form-control input-mask-trigger" id="email" name="email" value="{{$email}}"
                       inputmode="email" placeholder="{{__('Notification.puqPHPmail.Email')}}">
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="sender_name">{{__('Notification.puqPHPmail.Sender Name')}}</label>
            <div>
                <input type="text" class="form-control" id="sender_name" name="sender_name" value="{{$sender_name}}"
                       placeholder="{{__('Notification.puqPHPmail.Sender Name')}}">
            </div>
        </div>
    </div>
</div>

@if($admin->hasPermission('Notification-puqPHPmail-test-connection'))
    <button id="test_connection" type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary">
        <i class="fa fa-plug"></i>{{__('Notification.puqPHPmail.Test Connection')}}
    </button>
@endif

<script>
    $("#test_connection").on("click", function (event) {
        const $form = $("#notification_sender");
        blockUI('module');
        const formData = serializeForm($form);
        PUQajax('{{ route('admin.api.Notification.puqPHPmail.test_connection.post',$uuid) }}', formData, 5000, $(this), 'post', $form)
            .then(function () {
                unblockUI('module');
            })
            .catch(function (error) {
                unblockUI('module');
            });
    });
</script>
