<div class="row g-3" id="module">
    <input type="hidden" id="uuid" name="uuid" value="{{$uuid}}">

    <!-- CA -->
    <div class="col-12 col-md-6">
        <label for="ca"
               class="form-label">{{ __('CertificateAuthority.puqACME.Certificate Authority') }}</label>
        <select class="form-select" id="ca" name="ca">
            <option value="letsencrypt" {{ ($ca == 'letsencrypt') ? 'selected' : '' }}>
                {{ __('CertificateAuthority.puqACME.Let\'s Encrypt') }}
            </option>
            <option value="letsencrypt_staging" {{ ($ca == 'letsencrypt_staging') ? 'selected' : '' }}>
                {{ __('CertificateAuthority.puqACME.Let\'s Encrypt Staging') }}
            </option>
            <option value="zerossl" {{ ($ca == 'zerossl') ? 'selected' : '' }}>
                {{ __('CertificateAuthority.puqACME.ZeroSSL') }}
            </option>
        </select>
    </div>

    <!-- Account Email -->
    <div class="col-12 col-md-6">
            <label for="email"
                   class="form-label">{{ __('CertificateAuthority.puqACME.Email address for the ACME account') }}</label>
            <input type="email" class="form-control" id="email" name="email" value="{{$email ?? ''}}">
            <div class="form-text">
                {{ __('CertificateAuthority.puqACME.This email will be used to register and manage your ACME account') }}
            </div>
    </div>

    <!-- EAB KID -->
    <div class="col-12 col-md-6">
            <label for="eab_kid" class="form-label">{{ __('CertificateAuthority.puqACME.EAB Key ID') }}</label>
            <input type="text" class="form-control" id="eab_kid" name="eab_kid" value="{{$eab_kid ?? ''}}">
            <div class="form-text">
                {{ __('CertificateAuthority.puqACME.Key ID for External Account Binding (EAB)') }}
            </div>
    </div>

    <!-- EAB HMAC Key -->
    <div class="col-12 col-md-6">
            <label for="eab_hmac_key" class="form-label">{{ __('CertificateAuthority.puqACME.EAB HMAC Key') }}</label>
            <input type="text" class="form-control" id="eab_hmac_key" name="eab_hmac_key" value="{{$eab_hmac_key ?? ''}}">
            <div class="form-text">
                {{ __('CertificateAuthority.puqACME.HMAC secret for External Account Binding (EAB)') }}
            </div>
    </div>

    <!-- DNS Zone -->
    <div class="col-12 col-md-4">
        <div class="mb-3">
            <label class="form-label" for="dns_zone_uuid">{{ __('CertificateAuthority.puqACME.DNS Zone') }}</label>
            <select name="dns_zone_uuid" id="dns_zone_uuid" class="form-select">
                <option value=""></option>
            </select>
            <div class="form-text">
                {{ __('CertificateAuthority.puqACME.A technical zone for centralized management of ACME validations via CNAME') }}
            </div>
        </div>
    </div>

    <!-- Allow Wildcard -->
    <div class="col-12 col-md-4">
        <label for="allow_wildcard"
               class="form-label">{{ __('CertificateAuthority.puqACME.Allow wildcard certificates') }}</label>
        <select class="form-select" id="allow_wildcard" name="allow_wildcard">
            <option value="0" {{ !($allow_wildcard ?? true) ? 'selected' : '' }}>{{ __('CertificateAuthority.puqACME.No') }}</option>
            <option value="1" {{ ($allow_wildcard ?? true) ? 'selected' : '' }}>{{ __('CertificateAuthority.puqACME.Yes') }}</option>
        </select>
    </div>

    <!-- DNS Record TTL -->
    <div class="col-12 col-md-4">
        <label for="dns_record_ttl"
               class="form-label">{{ __('CertificateAuthority.puqACME.DNS Record TTL (seconds)') }}</label>
        <input type="number" class="form-control" id="dns_record_ttl" name="dns_record_ttl"
               value="{{$dns_record_ttl ?? 30}}" min="30" step="1">
        <div class="form-text">
            {{ __('CertificateAuthority.puqACME.Time to live for DNS records in seconds') }}
        </div>
    </div>

    <!-- API Timeout -->
    <div class="col-12 col-md-4">
        <label for="api_timeout" class="form-label">
            {{ __('CertificateAuthority.puqACME.API Timeout (seconds)') }}
        </label>
        <input type="number" class="form-control" id="api_timeout" name="api_timeout"
               value="{{ $api_timeout ?? 10 }}" min="1" max="300" step="1">
        <div class="form-text">
            {{ __('CertificateAuthority.puqACME.Maximum waiting time for ACME API requests (1–300 seconds)') }}
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        var $elementDnsZones = $('#dns_zone_uuid');
        initializeSelect2($elementDnsZones, '{{route('admin.api.dns_zones.select.get')}}', {!! json_encode($dns_zone_data) !!}, 'GET', 1000, {});
    });
</script>
