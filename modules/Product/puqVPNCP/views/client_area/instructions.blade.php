@section('content')
    @parent
    <div class="container px-0">
        <div class="card shadow-sm mb-2">
            <div class="card-body">

                @if(!empty($product_data['instructions_url']))
                    <div class="mb-3">
                        <iframe src="{{ $product_data['instructions_url'] }}" style="width: 100%; height: 600px; border: 1px solid #ddd; border-radius: 4px;"></iframe>
                    </div>
                @else

                    <ul class="nav nav-tabs" id="instructionsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="windows-tab" data-bs-toggle="tab" data-bs-target="#windows" type="button" role="tab">
                                <i class="fab fa-windows me-1"></i>Windows
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="macos-tab" data-bs-toggle="tab" data-bs-target="#macos" type="button" role="tab">
                                <i class="fab fa-apple me-1"></i>macOS
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="linux-tab" data-bs-toggle="tab" data-bs-target="#linux" type="button" role="tab">
                                <i class="fab fa-linux me-1"></i>Linux
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ios-tab" data-bs-toggle="tab" data-bs-target="#ios" type="button" role="tab">
                                <i class="fab fa-apple me-1"></i>iOS
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="android-tab" data-bs-toggle="tab" data-bs-target="#android" type="button" role="tab">
                                <i class="fab fa-android me-1"></i>Android
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content p-3" id="instructionsTabContent">

                        {{-- Windows --}}
                        <div class="tab-pane fade show active" id="windows" role="tabpanel">
                            <h5>{{ __('Product.puqVPNCP.Windows Setup') }}</h5>
                            <h6>WireGuard</h6>
                            <ol>
                                <li>{{ __('Product.puqVPNCP.Download WireGuard from') }} <a href="https://www.wireguard.com/install/" target="_blank">wireguard.com/install</a></li>
                                <li>{{ __('Product.puqVPNCP.Install and open the application') }}</li>
                                <li>{{ __('Product.puqVPNCP.Click Import tunnel(s) from file') }}</li>
                                <li>{{ __('Product.puqVPNCP.Select the downloaded .conf file') }}</li>
                                <li>{{ __('Product.puqVPNCP.Click Activate to connect') }}</li>
                            </ol>
                            <h6>OpenVPN</h6>
                            <ol>
                                <li>{{ __('Product.puqVPNCP.Download OpenVPN Connect from') }} <a href="https://openvpn.net/client/" target="_blank">openvpn.net/client</a></li>
                                <li>{{ __('Product.puqVPNCP.Import the .ovpn profile') }}</li>
                                <li>{{ __('Product.puqVPNCP.Click Connect') }}</li>
                            </ol>
                        </div>

                        {{-- macOS --}}
                        <div class="tab-pane fade" id="macos" role="tabpanel">
                            <h5>{{ __('Product.puqVPNCP.macOS Setup') }}</h5>
                            <h6>WireGuard</h6>
                            <ol>
                                <li>{{ __('Product.puqVPNCP.Install WireGuard from the App Store') }}</li>
                                <li>{{ __('Product.puqVPNCP.Click Import tunnel(s) from file') }}</li>
                                <li>{{ __('Product.puqVPNCP.Select the downloaded .conf file') }}</li>
                                <li>{{ __('Product.puqVPNCP.Allow VPN configuration when prompted') }}</li>
                                <li>{{ __('Product.puqVPNCP.Click Activate to connect') }}</li>
                            </ol>
                        </div>

                        {{-- Linux --}}
                        <div class="tab-pane fade" id="linux" role="tabpanel">
                            <h5>{{ __('Product.puqVPNCP.Linux Setup') }}</h5>
                            <h6>WireGuard</h6>
                            <pre class="bg-light p-3 rounded">sudo apt install wireguard
sudo cp downloaded.conf /etc/wireguard/wg0.conf
sudo wg-quick up wg0</pre>
                            <h6>OpenVPN</h6>
                            <pre class="bg-light p-3 rounded">sudo apt install openvpn
sudo openvpn --config downloaded.ovpn</pre>
                        </div>

                        {{-- iOS --}}
                        <div class="tab-pane fade" id="ios" role="tabpanel">
                            <h5>{{ __('Product.puqVPNCP.iOS Setup') }}</h5>
                            <ol>
                                <li>{{ __('Product.puqVPNCP.Install WireGuard from the App Store') }}</li>
                                <li>{{ __('Product.puqVPNCP.Scan the QR code from the General tab or import the config file') }}</li>
                                <li>{{ __('Product.puqVPNCP.Allow VPN configuration when prompted') }}</li>
                                <li>{{ __('Product.puqVPNCP.Toggle the connection on') }}</li>
                            </ol>
                        </div>

                        {{-- Android --}}
                        <div class="tab-pane fade" id="android" role="tabpanel">
                            <h5>{{ __('Product.puqVPNCP.Android Setup') }}</h5>
                            <ol>
                                <li>{{ __('Product.puqVPNCP.Install WireGuard from Google Play') }}</li>
                                <li>{{ __('Product.puqVPNCP.Scan the QR code from the General tab or import the config file') }}</li>
                                <li>{{ __('Product.puqVPNCP.Allow VPN configuration when prompted') }}</li>
                                <li>{{ __('Product.puqVPNCP.Toggle the connection on') }}</li>
                            </ol>
                        </div>

                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection
