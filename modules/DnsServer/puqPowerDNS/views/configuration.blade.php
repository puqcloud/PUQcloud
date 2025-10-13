<div class="row" id="module">
    <input type="hidden" id="uuid" name="uuid" value="{{$uuid}}">
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="server">{{__('DnsServer.puqPowerDNS.Server')}}</label>
            <div>
                <input type="text" class="form-control" id="server" name="server" value="{{$server}}"
                       placeholder="{{__('DnsServer.puqPowerDNS.Server')}}">
                <small class="form-text text-muted">
                    {{ __('DnsServer.puqPowerDNS.Enter the full server address including http(s) and port, e.g. http://127.0.0.1:8081') }}
                </small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="api_key">{{__('DnsServer.puqPowerDNS.API Key')}}</label>
            <div>
                <input type="text" class="form-control" id="api_key" name="api_key" value="{{$api_key}}"
                       placeholder="{{__('DnsServer.puqPowerDNS.API Key')}}">
            </div>
        </div>
    </div>
</div>

