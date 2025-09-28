@section('content')
    @parent
    <div class="container px-0">
        <canvas id="mouseCanvas"
                style="position:fixed; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:9999;"></canvas>

        <div class="card mb-2 shadow-sm border-0" style="min-height: 300px; position: relative; z-index: 1;">
            <div class="card-body text-center" id="status">
                <div class="mb-4">
                    <div class="spinner-border text-primary" style="width: 5rem; height: 5rem;" role="status"></div>
                </div>

                <h4 class="fw-bold text-dark" id="status_message">
                    {{ __('Product.puqProxmox.LXCNotReady') }}<br>
                    {{ __('Product.puqProxmox.DeploymentInProgress') }}<br>
                    {{ __('Product.puqProxmox.PleaseWait') }}...
                </h4>

                <p class="small text-muted mt-3" id="sub_message">
                    {{ __('Product.puqProxmox.PreparingEnvironment') }}
                </p>

                <div class="progress mt-4" style="height: 8px; max-width: 400px; margin: 0 auto;">
                    <div id="progress_bar" class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                         role="progressbar" style="width: 0%"></div>
                </div>

                <p class="small text-muted mt-2" id="timer_text">
                    {{ __('Product.puqProxmox.CheckingAgainIn') }} <span
                        id="countdown">5</span> {{ __('Product.puqProxmox.Seconds') }}
                </p>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            let countdown = 5;
            let progress = 0;
            let messages = [
                "{{ __('Product.puqProxmox.PreparingEnvironment') }}",
                "{{ __('Product.puqProxmox.ConfiguringResources') }}",
                "{{ __('Product.puqProxmox.AllocatingMemoryAndCPU') }}",
                "{{ __('Product.puqProxmox.SettingUpNetworking') }}",
                "{{ __('Product.puqProxmox.InitializingStorage') }}",
                "{{ __('Product.puqProxmox.RunningSecurityChecks') }}",
                "{{ __('Product.puqProxmox.ApplyingFinalConfigurations') }}",
                "{{ __('Product.puqProxmox.AlmostReady') }}",
                "{{ __('Product.puqProxmox.JustAMoment') }}",
                "{{ __('Product.puqProxmox.FinalizingSetup') }}"
            ];
            let msgIndex = 0;

            function updateTimer() {
                $("#countdown").text(countdown);
                countdown--;
                if (countdown < 0) {
                    countdown = 5;
                }
            }

            function updateProgress() {
                progress += 20;
                if (progress > 100) {
                    progress = 0;
                }
                $("#progress_bar").css("width", progress + "%");
            }

            function updateMessage() {
                $("#sub_message").fadeOut(400, function () {
                    $(this).text(messages[msgIndex]).fadeIn(400);
                });
                msgIndex = (msgIndex + 1) % messages.length;
            }

            function checkStatus() {
                PUQajax("{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getLxcStatus']) }}", {}, 50, null, 'GET')
                    .then(function (response) {
                        let d = response.data;
                        if (d && d.status) {
                            location.reload();
                        } else {
                            countdown = 5;
                        }
                    })
                    .catch(function () {
                        countdown = 5;
                    });
            }

            setInterval(updateTimer, 1000);
            setInterval(updateProgress, 1000);
            setInterval(updateMessage, 4000);
            setInterval(checkStatus, 5000);

            const canvas = document.getElementById("mouseCanvas");
            const ctx = canvas.getContext("2d");
            let particles = [];

            function resizeCanvas() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }

            window.addEventListener("resize", resizeCanvas);
            resizeCanvas();

            document.addEventListener("mousemove", function (e) {
                for (let i = 0; i < 3; i++) {
                    particles.push({
                        x: e.clientX,
                        y: e.clientY,
                        radius: Math.random() * 6 + 2,
                        alpha: 1,
                        dx: (Math.random() - 0.5) * 2,
                        dy: (Math.random() - 0.5) * 2
                    });
                }
            });

            function animateParticles() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                for (let i = 0; i < particles.length; i++) {
                    let p = particles[i];
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(0, 123, 255, ${p.alpha})`;
                    ctx.fill();
                    p.x += p.dx;
                    p.y += p.dy;
                    p.alpha -= 0.02;
                    p.radius *= 0.96;
                    if (p.alpha <= 0) {
                        particles.splice(i, 1);
                        i--;
                    }
                }
                requestAnimationFrame(animateParticles);
            }

            animateParticles();
        });
    </script>
@endsection
