<div class="row" id="module">
    <input type="hidden" id="uuid" name="uuid" value="{{$uuid}}">
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="email">{{__('Notification.puqSMTP.Email')}}</label>
            <div>
                <input type="text" class="form-control input-mask-trigger" id="email" name="email" value="{{$email}}"
                       inputmode="email" placeholder="{{__('Notification.puqSMTP.Email')}}">
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="server">{{__('Notification.puqSMTP.Server')}}</label>
            <div>
                <input type="text" class="form-control" id="server" name="server" value="{{$server}}"
                       placeholder="{{__('Notification.puqSMTP.Server')}}">
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="sender_name">{{__('Notification.puqSMTP.Sender Name')}}</label>
            <div>
                <input type="text" class="form-control" id="sender_name" name="sender_name" value="{{$sender_name}}"
                       placeholder="{{__('Notification.puqSMTP.Sender Name')}}">
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <div class="form-group">
                <label class="form-label" for="port">{{__('Notification.puqSMTP.Port')}}</label>
                <input type="number" id="port" min="1" name="port" class="form-control" value="{{$port}}">
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <div class="form-group">
                <label for="encryption">{{__('Notification.puqSMTP.Encryption')}}</label>
                <select name="encryption" class="form-select form-control">
                    <option value="none" {{ $encryption == 'none' ? 'selected' : '' }}>None</option>
                    <option value="ssl" {{ $encryption == 'ssl' ? 'selected' : '' }}>SSL</option>
                    <option value="tls" {{ $encryption == 'tls' ? 'selected' : '' }}>TLS</option>
                    <option value="starttls" {{ $encryption == 'starttls' ? 'selected' : '' }}>STARTTLS</option>
                </select>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="username">{{__('Notification.puqSMTP.Username')}}</label>
            <div>
                <input type="text" class="form-control" id="username" name="username" value="{{$username}}"
                       placeholder="{{__('Notification.puqSMTP.Username')}}">
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="password">{{__('Notification.puqSMTP.Password')}}</label>
            <input type="password" class="form-control" id="password" name="password" value="{{$password}}"
                   placeholder="{{__('Notification.puqSMTP.Password')}}">
        </div>
    </div>
</div>

@if($admin->hasPermission('Notification-puqSMTP-test-connection'))
    <button id="test_connection" type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary">
        <i class="fa fa-plug"></i> {{__('Notification.puqSMTP.Test Connection')}}
    </button>
@endif

<script>
    $("#test_connection").on("click", function (event) {
        const $form = $("#notification_sender");
        blockUI('module');
        const formData = serializeForm($form);
        PUQajax('{{ route('admin.api.Notification.puqSMTP.test_connection.post',$uuid) }}', formData, 5000, $(this), 'POST', $form)
            .then(function () {
                unblockUI('module');
            })
            .catch(function (error) {
                unblockUI('module');
            });
    });
</script>

