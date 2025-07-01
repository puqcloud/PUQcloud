<div class="row">
    <h1>{{$config['name']}}</h1>
    <h4>{{$module_type}}/{{$module_name}}</h4>
    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-2 col-xxl-2 mb-3">
        <label class="form-label" for="disk_space_size">{{ __('Product.puqNextcloud.Disk Space Size') }}</label>
        <div class="input-group">
            <input type="text"
                   class="form-control input-mask-trigger"
                   id="disk_space_size"
                   name="disk_space_size"
                   value="{{ $product_data['disk_space_size'] }}">
            <span class="input-group-text">GB</span>
        </div>
    </div>
    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-2 col-xxl-2 mb-3">
        <label class="form-label" for="username_prefix">{{__('Product.puqNextcloud.Username Prefix')}}</label>
        <div>
            <input type="text"
                   class="form-control input-mask-trigger"
                   id="username_prefix"
                   name="username_prefix"
                   value="{{$product_data['username_prefix']}}"
            >
        </div>
    </div>
    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-2 col-xxl-2 mb-3">
        <label class="form-label" for="username_suffix">{{__('Product.puqNextcloud.Username Suffix')}}</label>
        <div>
            <input type="text"
                   class="form-control input-mask-trigger"
                   id="username_suffix"
                   name="username_suffix"
                   value="{{$product_data['username_suffix']}}"
            >
        </div>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-2 col-xxl-2 mb-3">
        <label class="form-label" for="nextcloud_user_group">{{__('Product.puqNextcloud.Nextcloud User Group')}}</label>
        <div>
            <input type="text"
                   class="form-control input-mask-trigger"
                   id="nextcloud_user_group"
                   name="nextcloud_user_group"
                   value="{{$product_data['nextcloud_user_group']}}"
            >
        </div>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-2 col-xxl-2 mb-3">
        <label for="group_uuid" class="form-label">{{ __('Product.puqNextcloud.Server Group') }}</label>
        <select name="group_uuid" id="group_uuid" class="form-select mb-2 form-control">
            @foreach($groups as $uuid => $name)
                <option value="{{ $uuid }}" {{ $product_data['group_uuid'] == $uuid ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>
    </div>

</div>
