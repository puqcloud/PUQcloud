@section('content')
    @parent
    <div class="container px-0">
        <canvas id="mouseCanvas"
                style="position:fixed; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:9999;"></canvas>

        <div class="card mb-2 shadow-sm border-0" style="min-height: 250px; position: relative; z-index: 1;">
            <div class="card-body text-center" id="lxc_status_card">
                <div class="mb-4">
                    <div id="status_spinner" class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status"></div>
                </div>

                <h4 class="fw-bold text-dark" id="status_message">{{ __('Product.puqProxmox.Deploying your LXC') }}</h4>

                <p class="small text-muted mt-2" id="sub_message">{{ __('Product.puqProxmox.Please wait while your LXC is being prepared') }}</p>

                <div class="progress mt-4" style="height: 8px; max-width: 400px; margin: 0 auto;">
                    <div id="progress_bar" class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 0%"></div>
                </div>

                <p class="small text-muted mt-2" id="progress_text">
                    {{ __('Product.puqProxmox.Status') }}: <span id="deploy_status">pending</span> | {{ __('Product.puqProxmox.Progress') }}: <span id="deploy_progress">0%</span>
                </p>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            let stopUpdates = false;
            let successReloaded = false;
            let particles = [];

            function updateLxcCard(data) {
                let status = data?.status ?? 'pending';
                let progress = data?.progress ?? 0;

                let statusMessage = "{{ __('Product.puqProxmox.Deploying your LXC') }}";
                let subMessage = "{{ __('Product.puqProxmox.Please wait while your LXC is being prepared') }}";
                let colorClass = 'text-secondary';
                let progressClass = 'bg-secondary';

                switch(status) {
                    case 'pending':
                        statusMessage = "{{ __('Product.puqProxmox.LXC deployment is pending') }}";
                        colorClass = 'text-secondary';
                        progressClass = 'bg-secondary';
                        break;
                    case 'running':
                        statusMessage = "{{ __('Product.puqProxmox.LXC deployment is running') }}";
                        colorClass = 'text-primary';
                        progressClass = 'bg-primary';
                        break;
                    case 'success':
                        statusMessage = "{{ __('Product.puqProxmox.LXC deployed successfully') }}";
                        subMessage = "";
                        colorClass = 'text-success';
                        progressClass = 'bg-success';
                        if(!successReloaded){
                            successReloaded = true;
                            setTimeout(() => { location.reload(); }, 500);
                        }
                        break;
                    case 'failed':
                        statusMessage = "{{ __('Product.puqProxmox.Deployment failed') }}";
                        subMessage = "{{ __('Product.puqProxmox.Please contact support') }}";
                        colorClass = 'text-danger';
                        progressClass = 'bg-danger';
                        break;
                    case 'canceled':
                        statusMessage = "{{ __('Product.puqProxmox.Deployment canceled') }}";
                        subMessage = "";
                        colorClass = 'text-warning';
                        progressClass = 'bg-warning';
                        break;
                }

                $('#status_message').text(statusMessage).removeClass().addClass('fw-bold ' + colorClass);
                $('#sub_message').text(subMessage);
                $('#progress_bar').removeClass().addClass('progress-bar progress-bar-striped progress-bar-animated ' + progressClass).css('width', progress + '%');
                $('#deploy_status').text(status).removeClass().addClass(colorClass);
                $('#deploy_progress').text(progress + '%');

                if(status === 'running') $('#status_spinner').show();
                else $('#status_spinner').hide();

                if(['success','failed','canceled'].includes(status)) stopUpdates = true;
            }

            function checkLxcStatus() {
                if(stopUpdates) return;
                PUQajax("{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getLxcDeployStatus']) }}", {}, 50, null, 'GET')
                    .then(res => { updateLxcCard(res.data); })
                    .catch(() => {});
            }

            setInterval(checkLxcStatus, 2000);
            checkLxcStatus();

            const canvas = document.getElementById("mouseCanvas");
            const ctx = canvas.getContext("2d");

            function resizeCanvas() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
            window.addEventListener("resize", resizeCanvas);
            resizeCanvas();

            document.addEventListener("mousemove", function(e) {
                for(let i=0;i<3;i++){
                    particles.push({ x:e.clientX, y:e.clientY, radius:Math.random()*6+2, alpha:1, dx:(Math.random()-0.5)*2, dy:(Math.random()-0.5)*2 });
                }
            });

            function animateParticles() {
                ctx.clearRect(0,0,canvas.width,canvas.height);
                for(let i=0;i<particles.length;i++){
                    let p = particles[i];
                    ctx.beginPath();
                    ctx.arc(p.x,p.y,p.radius,0,Math.PI*2);
                    ctx.fillStyle = `rgba(0,123,255,${p.alpha})`;
                    ctx.fill();
                    p.x += p.dx; p.y += p.dy; p.alpha -= 0.02; p.radius *= 0.96;
                    if(p.alpha <= 0){ particles.splice(i,1); i--; }
                }
                requestAnimationFrame(animateParticles);
            }
            animateParticles();
        });
    </script>
@endsection

