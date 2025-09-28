<div class="row py-2 border-bottom">
    <div class="col-6 text-muted"><strong>{{__('main.Name')}}</strong></div>
    <div class="col-6">{{ $client->firstname }} {{ $client->lastname }}</div>
</div>

<div class="row py-2 border-bottom">
    <div class="col-6 text-muted"><strong>{{__('main.Company Name')}}</strong></div>
    <div class="col-6">{{ $client->company_name }}</div>
</div>

<div class="row py-2 border-bottom">
    <div class="col-6 text-muted"><strong>{{__('main.Tax ID')}}</strong></div>
    <div class="col-6">{{ $client->tax_id }}</div>
</div>

<div class="row py-2 border-bottom">
    <div class="col-6 text-muted"><strong>{{__('main.Email')}}</strong></div>
    <div class="col-6">{{ $owner->email }}</div>
</div>

<div class="row py-2 border-bottom">
    <div class="col-6 text-muted"><strong>{{__('main.Email Verified')}}</strong></div>
    <div class="col-6">
                <span class="badge {{ $owner->email_verified ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ $owner->email_verified ? __('main.Verified') : __('main.Not Verified') }}
                </span>
    </div>
</div>

<div class="row py-2 border-bottom">
    <div class="col-6 text-muted"><strong>{{__('main.Language')}}</strong></div>
    <div class="col-6">{{ $language['name'] }}</div>
</div>

<div class="row py-2 border-bottom">
    <div class="col-12 d-flex justify-content-center">
        <div class="widget-content p-0">
            <div class="widget-content-wrapper">
                <div class="widget-content-left me-3">
                    <div class="avatar-icon-wrapper">
                        <div class="badge badge-bottom"></div>
                        <div class="flag {{$billingAddress->country->code}} large mx-auto"></div>
                    </div>
                </div>
                <div class="widget-content-left">
                    <div class="widget-heading">
                        {{$billingAddress->address_1}}
                        {{$billingAddress->address_2 ? ', ' . $billingAddress->address_2 : ''}},
                        {{$billingAddress->postcode}}
                    </div>
                    <div class="widget-subheading">
                        {{$billingAddress->city}}, {{$billingAddress->region->name}}, {{$billingAddress->country->name}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row py-2 border-bottom">
    <div class="col-6 text-muted"><strong>{{__('main.Created At')}}</strong></div>
    <div class="col-6">{{ $client->created_at->format('Y-m-d H:i') }}</div>
</div>


