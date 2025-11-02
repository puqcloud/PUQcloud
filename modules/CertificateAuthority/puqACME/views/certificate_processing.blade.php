<div class="row g-3" id="module">
    <div class="text-center py-5">
        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h3 class="fw-bold text-primary mb-2">
            {{ __('CertificateAuthority.puqACME.Processing your certificate request...') }}
        </h3>
        <p class="text-muted mb-4">
            {{ __('CertificateAuthority.puqACME.Please wait while we communicate with the Certificate Authority') }}
        </p>
        <div class="processing-text text-primary fw-semibold">
            {{ __('CertificateAuthority.puqACME.Status: In Progress') }}
        </div>
    </div>
</div>

<style>
    .processing-text {
        font-size: 1.1rem;
        animation: pulse 1.5s ease-in-out infinite;
    }

    @keyframes pulse {
        0% { opacity: 0.3; }
        50% { opacity: 1; }
        100% { opacity: 0.3; }
    }
</style>
