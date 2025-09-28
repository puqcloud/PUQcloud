<div class="row">
    <h1>{{$config['name']}}</h1>
    <h4>{{$module_type}}/{{$module_name}}</h4>
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="login">{{__('Product.puqSampleProduct.Login')}}</label>
            <div>
                <input type="text" class="form-control input-mask-trigger" id="login" name="login"
                       value="{{$service_data['login']}}"
                       inputmode="text" placeholder="{{__('Product.puqSampleProduct.Login')}}">
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="password">{{__('Product.puqSampleProduct.Password')}}</label>
            <div>
                <input type="text" class="form-control input-mask-trigger" id="password" name="password"
                       value="{{$service_data['password']}}"
                       inputmode="text" placeholder="{{__('Product.puqSampleProduct.Password')}}">
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="domain">{{__('Product.puqSampleProduct.Domain')}}</label>
            <div>
                <input type="text" class="form-control input-mask-trigger" id="domain" name="domain"
                       value="{{$service_data['domain']}}"
                       inputmode="text" placeholder="{{__('Product.puqSampleProduct.Domain')}}">
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="ip">{{__('Product.puqSampleProduct.IP')}}</label>
            <div>
                <input type="text" class="form-control input-mask-trigger" id="ip" name="ip"
                       value="{{$service_data['ip']}}"
                       inputmode="text" placeholder="{{__('Product.puqSampleProduct.IP')}}">
            </div>
        </div>
    </div>
</div>
