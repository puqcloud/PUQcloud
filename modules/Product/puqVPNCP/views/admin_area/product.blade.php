<div class="row">
    <h1>{{$config['name']}}</h1>
    <h4>{{$module_type}}/{{$module_name}}</h4>

    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-2 col-xxl-2 mb-3">
        <label for="location_product_option_group_uuid" class="form-label">
            {{ __('Product.puqVPNCP.Location Option Group') }}
        </label>
        <select name="location_product_option_group_uuid" id="location_product_option_group_uuid" class="form-select">
            <option value="">—</option>
            @foreach ($product->productOptionGroups as $group)
                <option value="{{ $group['uuid'] }}"
                        @if (($product_data['location_product_option_group_uuid'] ?? '') === $group['uuid']) selected @endif>
                    {{ $group->name ?: $group->key }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-2 col-xxl-2 mb-3">
        <label class="form-label" for="b_download">{{ __('Product.puqVPNCP.Download Speed') }}</label>
        <div class="input-group">
            <input type="number"
                   class="form-control input-mask-trigger"
                   id="b_download"
                   name="b_download"
                   min="0"
                   value="{{ $product_data['b_download'] ?? 0 }}">
            <span class="input-group-text">Mbit/s</span>
        </div>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-2 col-xxl-2 mb-3">
        <label class="form-label" for="b_upload">{{ __('Product.puqVPNCP.Upload Speed') }}</label>
        <div class="input-group">
            <input type="number"
                   class="form-control input-mask-trigger"
                   id="b_upload"
                   name="b_upload"
                   min="0"
                   value="{{ $product_data['b_upload'] ?? 0 }}">
            <span class="input-group-text">Mbit/s</span>
        </div>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-2 col-xxl-2 mb-3">
        <label for="protocols_product_attribute_group_uuid" class="form-label">
            {{ __('Product.puqVPNCP.Protocols Attribute Group') }}
        </label>
        <select name="protocols_product_attribute_group_uuid" id="protocols_product_attribute_group_uuid" class="form-select">
            <option value="">—</option>
            @foreach ($product_attribute_groups as $attrGroup)
                <option value="{{ $attrGroup->uuid }}"
                        @if (($product_data['protocols_product_attribute_group_uuid'] ?? '') === $attrGroup->uuid) selected @endif>
                    {{ $attrGroup->name ?: $attrGroup->key }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-4 col-xxl-4 mb-3">
        <label class="form-label" for="instructions_url">{{ __('Product.puqVPNCP.Instructions URL') }}</label>
        <div>
            <input type="text"
                   class="form-control input-mask-trigger"
                   id="instructions_url"
                   name="instructions_url"
                   value="{{ $product_data['instructions_url'] ?? '' }}"
                   placeholder="https://docs.example.com/vpn-setup"
            >
        </div>
    </div>

</div>
