<div class="row g-3" id="module">
    <input type="hidden" id="uuid" name="uuid" value="{{ $uuid }}">

    <div class="mb-3">
        <label for="email" class="form-label">
            {{ __('CertificateAuthority.puqACME.Email address for the ACME account') }}
        </label>
        <input type="email" class="form-control" id="account_email" name="account_email"
               value="{{ $account_email ?? '' }}">
        <div class="form-text">
            {{ __('CertificateAuthority.puqACME.This email will be used to register and manage your ACME account') }}
        </div>
    </div>

    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="accept_tos" name="accept_tos" required
            @checked(isset($accept_tos) && $accept_tos === 'yes')>
        <label class="form-check-label" for="accept_tos">
            {!! __('CertificateAuthority.puqACME.I agree to the') !!}
            <a href="{{ $terms_of_service }}" target="_blank" rel="noopener">
                {{ __('CertificateAuthority.puqACME.Terms of Service') }}
            </a>
        </label>
    </div>
</div>
