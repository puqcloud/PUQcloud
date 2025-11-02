<div class="row" id="module">
    <input type="hidden" id="uuid" name="uuid" value="{{$uuid}}">
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="server">{{__('DnsServer.puqHestiaDNS.Server')}}</label>
            <div>
                <input type="text" class="form-control" id="server" name="server" value="{{$server}}"
                       placeholder="hestia.example.com">
                <small class="form-text text-muted">
                    {{ __('DnsServer.puqHestiaDNS.Server URL or IP address. Protocol and port will be added automatically') }}
                </small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="username">{{__('DnsServer.puqHestiaDNS.Username')}}</label>
            <div>
                <input type="text" class="form-control" id="username" name="username" value="{{$username}}"
                       placeholder="admin">
                <small class="form-text text-muted">
                    {{ __('DnsServer.puqHestiaDNS.HestiaCP user account with DNS management permissions') }}
                </small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="access_key">{{__('DnsServer.puqHestiaDNS.Access Key')}}</label>
            <div>
                <input type="text" class="form-control" id="access_key" name="access_key" value="{{$access_key}}"
                       placeholder="ACCESS_KEY">
                <small class="form-text text-muted">
                    {{ __('DnsServer.puqHestiaDNS.Access key generated in HestiaCP') }}
                </small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="secret_key">{{__('DnsServer.puqHestiaDNS.Secret Key')}}</label>
            <div>
                <input type="password" class="form-control" id="secret_key" name="secret_key" value="{{$secret_key}}"
                       placeholder="SECRET_KEY">
                <small class="form-text text-muted">
                    {{ __('DnsServer.puqHestiaDNS.Secret key generated in HestiaCP') }}
                </small>
            </div>
        </div>
    </div>
</div>

