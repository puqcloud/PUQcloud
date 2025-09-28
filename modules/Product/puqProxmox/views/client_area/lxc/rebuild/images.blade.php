<div class="main-card card mb-2" id="images">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{__('Product.puqProxmox.Images')}}</h5>
        <div id="notStoppedMsg" class="text-danger mt-2 d-none">
            <h5 class="mb-0 fw-bold"> {{ __('Product.puqProxmox.Status must be stopped to select OS') }} </h5>
        </div>
        <h5 class="fw-bold mb-0 d-flex align-items-center">
            <i class="fa fa-info-circle me-1" id="status_icon"></i>
            <span id="status_text">{{__('Product.puqProxmox.Status')}}: -</span>
        </h5>
    </div>
    <div class="card-body p-3" id="info_card">
        <div class="mb-2" style="min-height: 100px;">
            <div class="row g-3"></div>
        </div>
        <div class="text-center">
            <div class="form-group text-center">
                <h3>{{ __('Product.puqProxmox.You must be aware of what you will do here') }}</h3>
                <h3 class="text-danger">
                    {{ __('Product.puqProxmox.Reinstalling, completely remove all data on all disks, all snapshots and backups will also be deleted') }}
                </h3>

                <h4>{{ __('Product.puqProxmox.To protect against accidental reinstallation') }}</h4>
                <h4>
                    {{ __('Product.puqProxmox.Please enter the word:') }}
                    <b class="text-danger">reinstall</b>
                    {{ __('Product.puqProxmox.In capital letters') }}
                </h4>
                <form id="rebuildForm">
                    <input type="hidden" id="os_product_option_uuid" name="os_product_option_uuid" value="">
                    <input type="text" name="protect" class="form-control"/>
                </form>
                <br>
                <button id="rebuildBtn" type="submit" class="btn btn-success" disabled>
                    <i class="fa fa-retweet"></i>&nbsp; {{ __('Product.puqProxmox.Reinstall') }}
                </button>
            </div>
        </div>
    </div>
</div>

@section('js')
    @parent
    <script>

        $(document).ready(function () {
            function loadImages() {
                blockUI('images');

                PUQajax(
                    `{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getLxcRebuildInfo']) }}`,
                    {},
                    50,
                    null,
                    'GET'
                ).then(response => {
                    const $container = $('#images .row.g-3');
                    $container.empty();

                    const imagesData = Object.values(response.data.images);
                    let status = response.data.status;
                    updateStatus(status);

                    let allowSelect = status === 'stopped';

                    if (!allowSelect) {
                        $('#notStoppedMsg').removeClass('d-none');
                    } else {
                        $('#notStoppedMsg').addClass('d-none');
                    }

                    imagesData.forEach(os => {
                        const versionOptions = os.versions.map(v => {
                            const priceText = (v.price && v.price.base) ? ` - ${v.price.base} / {{__('Product.puqProxmox.Monthly')}}` : '';
                            return `<option value="${v.uuid}">${v.name}${priceText}</option>`;
                        }).join('');
                        const card = `
<div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-3">
    <div class="image-widget border rounded p-1 mb-1 position-relative">
        <div class="widget-content p-0 d-flex align-items-center">
            <div class="widget-content-left me-1">
                <div class="avatar-icon-wrapper">
                    <div class="avatar-icon rounded" style="width:48px; height:48px; overflow:hidden;">
                        <img src="${os.icon}" alt="${os.name}" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                </div>
            </div>
            <div class="widget-content-left flex-grow-1">
                <div class="widget-heading">${os.name}</div>
                <select class="form-select form-select-sm mt-1 version-select" data-os="${os.name}" ${allowSelect ? '' : 'disabled'}>
                    <option value="">Select version</option>
                    ${versionOptions}
                </select>
            </div>
        </div>
        <i class="fa fa-check text-white position-absolute d-none"
           style="top:0; right:0; transform: translate(50%, -50%);
                  background-color: #28a745;
                  border-radius: 50%;
                  padding: 6px;
                  font-size: 1.2rem;
                  box-shadow: 0 2px 6px rgba(0,0,0,0.3);">
        </i>
    </div>
</div>
                    `;
                        $container.append(card);
                    });

                    if (allowSelect) {
                        $('.version-select').on('change', function () {
                            const selectedUuid = $(this).val();
                            $('.version-select').val('');
                            $('.image-widget').removeClass('border-3 border-success fw-bold');
                            $('.image-widget .fa-check').addClass('d-none');

                            if (selectedUuid) {
                                $(this).val(selectedUuid);
                                const widget = $(this).closest('.image-widget');
                                widget.addClass('border-3 border-success fw-bold');
                                widget.find('.fa-check').removeClass('d-none');
                                $('#os_product_option_uuid').val(selectedUuid);
                                $('#rebuildBtn').prop('disabled', false);
                            } else {
                                $('#os_product_option_uuid').val('');
                                $('#rebuildBtn').prop('disabled', true);
                            }
                        });
                    } else {
                        $('#rebuildBtn').prop('disabled', true);
                    }

                    unblockUI('images');
                }).catch(error => {
                    console.error(error);
                    unblockUI('images');
                });
            }

            function updateStatus(status) {
                let $statusText = $('#status_text');
                let $statusIcon = $('#status_icon');

                if (status === 'running') {
                    $statusText.text('Status: Running');
                    $statusIcon.removeClass().addClass('fa fa-play-circle text-success me-1');
                } else if (status === 'stopped') {
                    $statusText.text('Status: Stopped');
                    $statusIcon.removeClass().addClass('fa fa-stop-circle text-warning me-1');
                } else if (status === 'backup') {
                    $statusText.text('Status: Backup');
                    $statusIcon.removeClass().addClass('fa fa-spinner fa-spin text-primary me-1');
                } else if (status === 'create') {
                    $statusText.text('Status: Create/Restore');
                    $statusIcon.removeClass().addClass('fa fa-spinner fa-spin text-primary me-1');
                } else {
                    $statusText.text('Status: ' + status);
                    $statusIcon.removeClass().addClass('fa fa-exclamation-circle text-secondary me-1');
                }
            }


            $('#rebuildBtn').on('click', function (event) {
                event.preventDefault();
                if ($('#rebuildForm').length) {
                    blockUI('images');
                    var $form = $('#rebuildForm');
                    var formData = serializeForm($form);
                    PUQajax('{{ route('client.api.cloud.service.module.post', ['uuid' => $service_uuid, 'method' => 'postLxcRebuildNow']) }}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            unblockUI('images');
                            loadImages();
                        }).catch(function (error) {
                        unblockUI('images');
                    });
                }
            });

            loadImages();
        });

    </script>
@endsection
