<div class="main-card card position-relative mb-2">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fa fa-sliders-h text-primary me-2" style="font-size: 1.3rem;"></i>{{ __('main.Options') }}
        </h5>
        <button class="btn-icon btn-shadow btn-outline-2x btn btn-outline-info"
                type="button"
                data-bs-toggle="collapse" data-bs-target="#optionsDetails"
                aria-expanded="false" aria-controls="optionsDetails">
            <i class="fa fa-chevron-down" data-bs-toggle-icon></i>
        </button>
    </div>

    <div class="collapse position-absolute top-100 start-0 w-100 bg-white shadow border rounded"
         id="optionsDetails"
         style="z-index: 9998;">
        <div class="card-body p-3">

            @foreach($product_options as $product_option)
                <div class="pb-3 mb-3 border-bottom">
                    <div class="d-flex align-items-center mb-1">
                        <i class="fa fa-layer-group text-success me-2" style="font-size: 1.2rem;"></i>
                        <strong>{{ $product_option->productOptionGroup->name }}</strong>
                    </div>

                    @if($product_option->short_description)
                        <div class="text-muted small mb-2 ps-4">
                            {{ $product_option->short_description }}
                        </div>
                    @endif

                    <div class="d-flex align-items-center ps-4">
                        <i class="fa fa-caret-right text-secondary me-2" style="font-size: 1rem;"></i>
                        <span class="fw-semibold">{{ $product_option->name }}</span>
                    </div>
                </div>
            @endforeach

        </div>
    </div>
</div>
