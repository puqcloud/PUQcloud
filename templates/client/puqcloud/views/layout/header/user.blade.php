<div class="widget-content p-0">
    <div class="widget-content-wrapper">
        <div class="widget-content-left">
            <div class="btn-group">
                <a data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                   class="p-0 btn">
                    <img width="42" class="avatar-icon border-dark" src="{{ get_gravatar($user->email, 100) }}" alt=""
                         style="background: #ffffff">
                </a>
                <div tabindex="-1" role="menu" aria-hidden="true"
                     class="dropdown-menu-rounded dropdown-menu-lg rm-pointers dropdown-menu">
                    <div class="dropdown-menu-header">
                        <div class="dropdown-menu-header-inner bg-premium-dark">
                            <div class="menu-header-content text-start">
                                <div class="widget-content p-0">
                                    <div class="widget-content-wrapper">
                                        <div class="widget-content-left me-3">
                                            <img width="42" class="avatar-icon rounded"
                                                 src="{{ get_gravatar($user->email, 100) }}" alt=""
                                                 style="background: #ffffff">
                                        </div>
                                        <div class="widget-content-left">
                                            <div class="widget-heading">{{$user->firstname}} {{$user->lastname}}</div>
                                            <div class="widget-subheading opacity-8">{{$user->email}}</div>
                                        </div>
                                        <div class="widget-content-right me-2">
                                            <button id="logout" class="btn-icon btn-square btn btn-secondary">
                                                <i class="fas fa-sign-out-alt"></i> {{__('main.Logout')}}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @foreach ($navigation->getUserMenu() as $item)
                        @if(empty($item['divider']))
                            <a href="{{ $item['url'] }}" tabindex="0" class="dropdown-item">
                                <i class="{{ $item['icon'] }} dropdown-icon"></i>
                                {{ $item['label'] }}
                            </a>
                        @else
                            <div tabindex="-1" class="dropdown-divider"></div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
