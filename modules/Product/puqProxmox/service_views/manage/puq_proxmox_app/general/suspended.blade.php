<div class="text-center my-5 bg-warning text-white py-5 rounded shadow-lg border border-dark">
    <div class="mb-4">
        <i class="fa fa-pause-circle fa-7x me-4 animate-bounce"></i>
        <i class="fa fa-exclamation-triangle fa-7x animate-pulse"></i>
    </div>
    <div class="display-1 fw-bold mb-3">{{ __('Product.puqProxmox.SUSPENDED') }}</div>
    <div class="display-4 fw-semibold mb-3">{{ __('Product.puqProxmox.ACCESS LIMITED') }}</div>
    <div class="fs-3 fw-medium">
        {{ __('Product.puqProxmox.Your service is currently suspended') }}<br>
        {{ __('Product.puqProxmox.Contact support to restore access') }}
    </div>
    <div class="mt-5">
        <i class="fa fa-lock fa-4x me-3 animate-pulse"></i>
        <i class="fa fa-ban fa-4x me-3 animate-bounce"></i>
        <i class="fa fa-exclamation-circle fa-4x animate-pulse"></i>
    </div>
</div>

<style>
    .animate-pulse {
        animation: pulse 1.5s infinite;
    }

    .animate-bounce {
        animation: bounce 1s infinite;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    @keyframes bounce {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-15px);
        }
    }
</style>
