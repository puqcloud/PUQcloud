<div class="row">
    <h1>{{$config['name']}}</h1>
    <h4>{{$module_type}}/{{$module_name}}/{{$product_uuid}}</h4>
    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="test1">{{__('Product.puqSampleProduct.Test1')}}</label>
            <div>
                <input type="text" class="form-control input-mask-trigger" id="test1" name="test1" value="{{$product_data['test1']}}"
                       inputmode="text" placeholder="{{__('Product.puqSampleProduct.Test1')}}">
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="test2">{{__('Product.puqSampleProduct.Test2')}}</label>
            <div>
                <input type="text" class="form-control input-mask-trigger" id="test2" name="test2" value="{{$product_data['test2']}}"
                       inputmode="text" placeholder="{{__('Product.puqSampleProduct.Test2')}}">
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="test3">{{__('Product.puqSampleProduct.Test3')}}</label>
            <div>
                <input type="text" class="form-control input-mask-trigger" id="test3" name="test3" value="{{$product_data['test3']}}"
                       inputmode="text" placeholder="{{__('Product.puqSampleProduct.Test3')}}">
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-12 col-md-6 col-lg mb-1">
        <div class="mb-3">
            <label class="form-label" for="test4">{{__('Product.puqSampleProduct.Test4')}}</label>
            <div>
                <input type="text" class="form-control input-mask-trigger" id="test4" name="test4" value="{{$product_data['test4']}}"
                       inputmode="text" placeholder="{{__('Product.puqSampleProduct.Test4')}}">
            </div>
        </div>
    </div>
</div>
