<div class="row" id="module">
    @if($http_client_available)
        <div class="no-results">
            <div class="swal2-icon swal2-success swal2-animate-success-icon">
                <div class="swal2-success-circular-line-left" style="background-color: rgb(255, 255, 255);"></div>
                <span class="swal2-success-line-tip"></span>
                <span class="swal2-success-line-long"></span>
                <div class="swal2-success-ring"></div>
                <div class="swal2-success-fix" style="background-color: rgb(255, 255, 255);"></div>
                <div class="swal2-success-circular-line-right" style="background-color: rgb(255, 255, 255);"></div>
            </div>
            <div class="results-subtitle mt-4">{{__('Notification.puqTraccarSMS.HTTP_Client')}}</div>
            <div class="results-title">{{__('Notification.puqTraccarSMS.Laravel_HTTP_Client_Available')}}</div>
            <div class="mt-3 mb-3"></div>
        </div>
    @else
        <div class="no-results">
            <div class="swal2-icon swal2-error swal2-animate-error-icon">
                <span class="swal2-x-mark-line-left"></span>
                <span class="swal2-x-mark-line-right"></span>
            </div>
            <div class="results-subtitle mt-4">{{__('Notification.puqTraccarSMS.HTTP_Client')}}</div>
            <div class="results-title">{{__('Notification.puqTraccarSMS.Laravel_HTTP_Client_Not_Available')}}</div>
            <div class="mt-3 mb-3"></div>
        </div>
    @endif

    <input type="hidden" id="uuid" name="uuid" value="{{$uuid}}">
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="token">{{__('Notification.puqTraccarSMS.Token')}}</label>
            <div>
                <input type="text" class="form-control" id="token" name="token" value="{{$token}}"
                       placeholder="{{__('Notification.puqTraccarSMS.Enter_Your_Token')}}">
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="url">{{__('Notification.puqTraccarSMS.URL')}}</label>
            <div>
                <input type="text" class="form-control" id="url" name="url" value="{{$url}}"
                       placeholder="{{__('Notification.puqTraccarSMS.Enter_Your_URL')}}">
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="phone_number">{{__('Notification.puqTraccarSMS.Phone_Number_For_Test_SMS')}}</label>
            <div>
                <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{$phone_number}}"
                       placeholder="{{__('Notification.puqTraccarSMS.Enter_Phone_Number_For_Test_SMS')}}">
            </div>
        </div>
    </div>
</div>

@if($admin->hasPermission('Notification-puqTraccarSMS-test-connection'))
    <button id="test_connection" type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary">
        <i class="fa fa-plug"></i> {{__('Notification.puqTraccarSMS.Test Connection')}}
    </button>
@endif

<script>
    $(document).on("click", "#test_connection", function (event) {
        const $form = $("#notification_sender");
        blockUI('module');
        const formData = serializeForm($form);
        try { console.log('TraccarSMS: test_connection click', formData); } catch(e) {}
        PUQajax('{{ route('admin.api.Notification.puqTraccarSMS.test_connection.post',$uuid) }}', formData, 5000, $(this), 'POST', $form)
            .then(function (resp) {
                // Show explicit success feedback
                try {
                    const isSuccess = resp && (resp.status === 'success');
                    const msg = resp && (resp.message || resp.errors);
                    const text = msg ? (Array.isArray(msg) ? msg.join('\n') : (typeof msg === 'object' ? JSON.stringify(msg) : String(msg))) : (isSuccess ? '{{ __('message.Successfully') }}' : 'Unknown error');
                    Swal.fire(isSuccess ? 'Success' : 'Error', text, isSuccess ? 'success' : 'error');
                } catch (e) {}
                unblockUI('module');
            })
            .catch(function (error) {
                // Show explicit error feedback
                try {
                    let errText = 'Request failed';
                    if (error) {
                        if (error.responseJSON && (error.responseJSON.message || error.responseJSON.errors)) {
                            errText = JSON.stringify(error.responseJSON.message || error.responseJSON.errors);
                        } else if (error.responseText) {
                            errText = error.responseText;
                        } else if (error.statusText) {
                            errText = error.statusText;
                        }
                    }
                    Swal.fire('Error', errText, 'error');
                } catch (e) {}
                unblockUI('module');
            });
    });
</script>


