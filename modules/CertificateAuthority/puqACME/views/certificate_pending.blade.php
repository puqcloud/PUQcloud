<div class="row g-3" id="dns-validation-module">

    <h5>{{ __('CertificateAuthority.puqACME.DNS Validation Records') }}</h5>
    <p>{{ __('CertificateAuthority.puqACME.To issue your SSL certificate, the following DNS records must be set. Please add missing records to your domain DNS settings and click "Recheck"') }}</p>

    @if(!empty($dns_records))
        @foreach($dns_records as $dns)
            <div class="card mb-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div>
                            <strong>{{ $dns['record'] }}</strong> ({{ $dns['type'] }})<br>
                            <small>
                                {{ __('CertificateAuthority.puqACME.Target') }}: <code>{{ $dns['target'] ?? '—' }}</code><br>
                            </small>
                        </div>
                        <div>
                            @if($dns['status'] === 'ok')
                                <span class="badge bg-success">{{ __('CertificateAuthority.puqACME.Valid') }}</span>
                            @elseif($dns['status'] === 'missing')
                                <span class="badge bg-danger">{{ __('CertificateAuthority.puqACME.Missing') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ $dns['status'] }}</span>
                            @endif
                        </div>
                    </div>

                    @if($dns['status'] === 'missing')
                        <div class="alert alert-warning mb-0">
                            {{ __('CertificateAuthority.puqACME.This record is missing. Please add a CNAME record in your domain DNS settings:') }}
                            <ul class="mb-0">
                                <li><strong>Host / Name:</strong> {{ $dns['record'] }}</li>
                                <li><strong>Type:</strong> {{ $dns['type'] }}</li>
                                <li><strong>Value / Target:</strong> {{ $dns['target'] }}</li>
                            </ul>
                            {{ __('CertificateAuthority.puqACME.After adding, click "Recheck DNS Records" to verify') }}
                        </div>
                    @elseif($dns['status'] === 'ok')
                        <div class="alert alert-success mb-0">
                            {{ __('CertificateAuthority.puqACME.Record is correctly set and verified') }}
                        </div>
                    @else
                        <div class="alert alert-secondary mb-0">
                            {{ $dns['message'] ?? __('CertificateAuthority.puqACME.Status unknown') }}
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div class="alert alert-info">
            {{ __('CertificateAuthority.puqACME.No DNS records to validate') }}
        </div>
    @endif

    <div class="mt-3">
        <button type="button" class="btn btn-primary" onclick="location.reload();">
            {{ __('CertificateAuthority.puqACME.Recheck DNS Records') }}
        </button>
    </div>

</div>
