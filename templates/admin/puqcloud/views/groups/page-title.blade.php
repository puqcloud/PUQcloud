<div class="app-page-title app-page-title-simple">
    <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div>
                <div class="page-title-head center-elem">
                                            <span class="d-inline-block pe-2">
                                                <i class="fas fa-address-card"></i>
                                            </span>
                    <span class="d-inline-block">{{__('main.Edit Group')}}</span>
                </div>
                <div class="page-title-subheading opacity-10">
                    <nav class="" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a>
                                    <i aria-hidden="true" class="fa fa-home"></i>
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{route('admin.web.dashboard')}}">{{ __('main.Dashboard') }}</a>
                            </li>
                            <li class="active breadcrumb-item" aria-current="page">
                                <a href="{{route('admin.web.groups')}}">{{__('main.Groups')}}</a>
                            </li>
                            <li class="active breadcrumb-item" aria-current="page">
                                {{ request()->route('uuid') }}
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        <div class="page-title-actions">
            @if($admin->hasPermission('groups-edit'))
                <button id="save" type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                    <i class="fa fa-save"></i> {{__('main.Save')}}
                </button>
            @endif
        </div>
    </div>
</div>
