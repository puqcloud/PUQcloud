<div class="dropdown">
    <button type="button" aria-haspopup="true" aria-expanded="false" data-bs-toggle="dropdown" class="p-0 me-2 btn btn-link">
                                    <span class="icon-wrapper icon-wrapper-alt rounded-circle">
                                        <span class="icon-wrapper-bg bg-primary"></span>
<i class="fas fa-handshake text-primary"></i>
                                    </span>
    </button>
    <div tabindex="-1" role="menu" aria-hidden="true" class="dropdown-menu-xl rm-pointers dropdown-menu dropdown-menu-right" style="">
        <div class="dropdown-menu-header">
            <div class="dropdown-menu-header-inner bg-plum-plate">
                <div class="menu-header-image"></div>
                <div class="menu-header-content text-white">
                    <h5 class="menu-header-title">
                        {{ $client->company_name ?: $client->firstname . ' ' . $client->lastname }}
                    </h5>
                    <h6 class="menu-header-subtitle">
                        {{ $client->company_name ? $client->firstname . ' ' . $client->lastname : '' }}
                    </h6>
                </div>

            </div>
        </div>
        <div class="grid-menu grid-menu-xl grid-menu-3col">
            <div class="g-0 row">
                @foreach ($navigation->getClientMenu() as $item)
                    <div class="col-4">
                        <a href="{{ $item['url'] }}" class="btn-icon-vertical btn-square btn-transition btn btn-outline-link">
                            <i class="{{ $item['icon'] }} icon-gradient bg-night-fade btn-icon-wrapper btn-icon-lg d-none d-md-inline"><br></i>
                            <i class="{{ $item['icon'] }} icon-gradient bg-night-fade d-inline d-md-none"><br></i>
                            {{ $item['label'] }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
