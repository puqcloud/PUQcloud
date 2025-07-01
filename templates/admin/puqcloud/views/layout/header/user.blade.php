<div class="widget-content p-0">
    <div class="widget-content-wrapper">
        <div class="widget-content-left">
            <div class="btn-group">
                <a data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                   class="p-0 btn">
                    <img width="42" class="avatar-icon border-dark" src="{{ get_gravatar($admin->email, 100) }}" alt="" style="background: #ffffff">
                </a>
                <div tabindex="-1" role="menu" aria-hidden="true"
                     class="rm-pointers dropdown-menu-lg dropdown-menu dropdown-menu-right">
                    <div class="dropdown-menu-header">
                        <div class="dropdown-menu-header-inner bg-primary">
                            <div class="menu-header-image"
                                 style="background-image: url('{{url('puqcloud/images/dropdown-header/city2.jpg')}}');"></div>
                            <div class="menu-header-content text-start">
                                <div class="widget-content p-0">
                                    <div class="widget-content-wrapper">
                                        <div class="widget-content-left me-3">
                                            <img width="42" class="avatar-icon rounded"
                                                 src="{{ get_gravatar($admin->email, 100) }}" alt="" style="background: #ffffff">
                                        </div>
                                        <div class="widget-content-left">
                                            <div class="widget-heading">{{$admin->firstname}} {{$admin->lastname}}</div>
                                            <div class="widget-subheading opacity-8">{{$admin->email}}</div>
                                        </div>
                                        <div class="widget-content-right me-2">
                                            <button id="logout"  class="btn-icon btn-square btn btn-secondary">
                                                <i class="fas fa-sign-out-alt"></i> {{__('main.Logout')}}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="scroll-area-xs">
                        <div class="scrollbar-container ps">
                            <ul class="nav flex-column">
                                <li class="p-0 list-group-item">
                                    <div class="grid-menu grid-menu-2col">
                                        <div class="g-0 row">
                                            <div class="col-sm-6">
                                                <a href="{{ route('admin.web.my_account') }}" class="btn-icon-vertical btn-square btn-transition btn btn-outline-link">
                                                    <i class="lnr-license btn-icon-wrapper btn-icon-lg mb-3"></i>
                                                    {{ __('main.My Account') }}
                                                </a>
                                            </div>
                                            <div class="col-sm-6">
                                                <a href="https://puqcloud.com/" target="_blank" class="btn-icon-vertical btn-square btn-transition btn btn-outline-link">
                                                    <img src="{{asset_admin('images/PUQ_cloud_d.png')}}" width="80" class="btn-icon-lg mb-3">
                                                    puqcloud.com
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
