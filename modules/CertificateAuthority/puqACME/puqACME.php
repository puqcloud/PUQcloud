<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

use App\Models\DnsZone;
use App\Models\SslCertificate;
use App\Modules\CertificateAuthority;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use phpseclib3\Crypt\RSA;

class puqACME extends CertificateAuthority
{
    private ?SslCertificate $certificate;

    private ?DNSZone $tech_dns_zone;

    public function __construct()
    {
        parent::__construct();
    }

    public function getModuleData(array $data = []): array
    {
        $this->module_data = [
            'ca' => $data['ca'] ?? 'zerossl',                        // letsencrypt, letsencrypt_staging, zerossl
            'email' => $data['email'] ?? '',                            // Account email used for registration
            'allow_wildcard' => $data['allow_wildcard'] ?? true,        // Whether CA supports *.domain
            'dns_zone_uuid' => $data['dns_zone_uuid'] ?? '',            // dns zone for dns-01
            'dns_record_ttl' => $data['dns_record_ttl'] ?? 30,
            'api_timeout' => $data['api_timeout'] ?? 10,

            'eab_kid' => $data['eab_kid'] ?? '',
            'eab_hmac_key' => $data['eab_hmac_key'] ?? '',
        ];

        return $this->module_data;
    }

    public function getSettingsPage(array $data = []): string
    {
        try {
            $data['eab_hmac_key'] = !empty($data['eab_hmac_key'])
                ? Crypt::decrypt($data['eab_hmac_key'])
                : '';
        } catch (\Exception $e) {
            $data['eab_hmac_key'] = '';
        }

        $dns_zone_uuid = $data['dns_zone_uuid'] ?? '';
        $dns_zone_model = DNSZone::where('uuid', $dns_zone_uuid)->first();
        $dns_zone_data = [];
        if (!empty($dns_zone_model)) {
            $dns_zone_data = [
                'id' => $dns_zone_model->uuid,
                'text' => $dns_zone_model->name,
            ];
        }
        $data['admin'] = app('admin');
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;
        $data['dns_zone_data'] = $dns_zone_data;

        return $this->view('configuration', $data);
    }

    public function saveModuleData(array $data = []): array
    {
        $validator = Validator::make($data, [
            'ca' => 'required|string|in:letsencrypt,letsencrypt_staging,zerossl',
            'email' => 'required|email',
            'allow_wildcard' => 'required|boolean',
            'dns_zone_uuid' => 'nullable|exists:dns_zones,uuid',
            'dns_record_ttl' => 'required|integer|min:30|max:86400',
            'api_timeout' => 'required|integer|min:1|max:300',
        ], [
            'ca.required' => __('CertificateAuthority.puqACME.The Certificate Authority field is required'),
            'ca.in' => __('CertificateAuthority.puqACME.Invalid Certificate Authority selected'),
            'email.required' => __('CertificateAuthority.puqACME.The Email field is required'),
            'email.email' => __('CertificateAuthority.puqACME.The Email must be a valid email address'),
            'allow_wildcard.required' => __('CertificateAuthority.puqACME.The Allow Wildcard field is required'),
            'allow_wildcard.boolean' => __('CertificateAuthority.puqACME.The Allow Wildcard field must be true or false'),
            'dns_zone_uuid.exists' => __('CertificateAuthority.puqACME.The selected DNS Zone does not exist'),
            'dns_record_ttl.required' => __('CertificateAuthority.puqACME.The DNS Record TTL field is required'),
            'dns_record_ttl.integer' => __('CertificateAuthority.puqACME.The DNS Record TTL must be an integer'),
            'dns_record_ttl.min' => __('CertificateAuthority.puqACME.The DNS Record TTL must be at least 30 seconds'),
            'dns_record_ttl.max' => __('CertificateAuthority.puqACME.The DNS Record TTL cannot exceed 86400 seconds'),
            'api_timeout.required' => __('CertificateAuthority.puqACME.The API Timeout field is required'),
            'api_timeout.integer' => __('CertificateAuthority.puqACME.The API Timeout must be an integer'),
            'api_timeout.min' => __('CertificateAuthority.puqACME.The API Timeout must be at least 1 second'),
            'api_timeout.max' => __('CertificateAuthority.puqACME.The API Timeout cannot exceed 300 seconds'),
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'code' => 422,
            ];
        }

        $data['eab_hmac_key'] = Crypt::encrypt($data['eab_hmac_key'] ?? '');

        return [
            'status' => 'success',
            'data' => $data,
            'code' => 200,
        ];
    }

    public function queues(): array
    {
        return [
            'Queue' => [
                'connection' => 'redis',
                'queue' => ['Queue'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 1,
                'maxTime' => 0,
                'maxJobs' => 0,
                'memory' => 128,
                'tries' => 1,
                'timeout' => 3600,
                'nice' => 0,
            ],
        ];
    }

    public function testConnection(): array
    {
        $client = new puqAcmeClient([], $this->module_data);
        $response = $client->testConnection();

        if ($response['status'] == 'error') {
            return $response;
        }

        $data = $response['data'];
        $directory = $data['directory'] ?? [];
        $nonce = $data['nonce'] ?? '';

        $directoryList = '';
        if (!empty($directory) && is_array($directory)) {
            $keys = array_keys($directory);
            usort($keys, function ($a, $b) {
                if ($a === 'meta') {
                    return 1;
                }
                if ($b === 'meta') {
                    return -1;
                }

                return $a <=> $b;
            });

            foreach ($keys as $key) {
                $value = $directory[$key];
                if (is_array($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                }
                $directoryList .= '<li><strong>'.htmlspecialchars($key).':</strong> <code>'.htmlspecialchars($value).'</code></li>';
            }
        }

        $html = '
<div class="text-center my-4">
    <div class="d-inline-block mb-3">
        <i class="fas fa-check-circle text-success" style="font-size: 64px; animation: pulse 1.2s ease-in-out infinite;"></i>
    </div>
    <h5 class="text-success fw-bold mb-3">'.__('CertificateAuthority.puqACME.API is available').'</h5>

    <div class="card mx-auto" style="max-width: 100%;">
        <div class="card-body text-start">
            <h6 class="fw-bold">'.__('CertificateAuthority.puqACME.Connection Details').'</h6>
            <ul class="list-unstyled mb-0">
                <li><strong>'.__('CertificateAuthority.puqACME.Nonce').':</strong> <code>'.$nonce.'</code></li>
            </ul>
            <hr>
            <h6 class="fw-bold">'.__('CertificateAuthority.puqACME.Directory Endpoints').'</h6>
            <ul class="list-unstyled mb-0">
                '.$directoryList.'
            </ul>
        </div>
    </div>
</div>

<style>
@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.15); opacity: 0.8; }
    100% { transform: scale(1); opacity: 1; }
}
</style>
';

        return [
            'status' => 'success',
            'data' => $html,
        ];
    }

    public function getCertificateData(array $data = []): array
    {
        $this->certificate_data = [
            'accept_tos' => $data['accept_tos'] ?? 'no',

            'account_email' => $data['account_email'] ?? '',
            'account_private_key' => $data['account_private_key'] ?? '',
            'account_id' => $data['account_id'] ?? '',

            'order_url' => $data['order_url'] ?? '',
            'finalize_url' => $data['finalize_url'] ?? '',
            'certificate_url' => $data['certificate_url'] ?? '',

            'eab_kid' => $data['eab_kid'] ?? '',
            'eab_hmac_key' => $data['eab_hmac_key'] ?? '',
        ];

        return $this->certificate_data;
    }

    public function getCertificatePage(array $data = []): string
    {
        $this->certificate = SslCertificate::query()->find($data['uuid']);
        if (empty($this->certificate)) {
            return '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle me-2"></i>'.__('message.No data to display').'</div>';
        }

        $this->tech_dns_zone = DnsZone::query()->find($this->module_data['dns_zone_uuid'] ?? '');
        if (empty($this->tech_dns_zone)) {
            return '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle me-2"></i>'.__('CertificateAuthority.puqACME.Technical DNS Zone not found').'</div>';
        }

        $data['admin'] = app('admin');
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;

        if ($this->certificate->status === 'draft') {

            $client = new puqAcmeClient([], $this->module_data);
            $directory = $client->getDirectory();

            if ($directory['status'] !== 'success') {
                $errorList = '';
                if (!empty($directory['errors'])) {
                    foreach ($directory['errors'] as $error) {
                        $errorList .= '<li>'.e($error).'</li>';
                    }
                } else {
                    $errorList = '<li>'.__('CertificateAuthority.puqACME.Unknown error from API').'</li>';
                }

                return '
                <div class="alert alert-danger shadow-sm rounded-3 p-3">
                    <h5 class="mb-2">
                        <i class="fa fa-plug me-2"></i>'.__('CertificateAuthority.puqACME.Failed to connect to ACME API').'
                    </h5>
                    <ul class="mb-0 small text-danger">'.$errorList.'</ul>
                </div>
            ';
            }

            $data['terms_of_service'] = $directory['data']['meta']['termsOfService'] ?? null;

            return $this->view('certificate_draft', $data);
        }

        if ($this->certificate->status === 'pending') {
            $dns_records = $this->checkCnameDnsValidationRecords();

            $data['dns_records'] = $dns_records ?? [];

            return $this->view('certificate_pending', $data);
        }

        if ($this->certificate->status === 'processing') {
            return $this->view('certificate_processing', $data);
        }

        if ($this->certificate->status === 'active') {

            $data['certificate'] = $this->certificate->toArray() ?? [];
            $data['certificate_data'] = $this->certificate_data ?? [];

            return $this->view('certificate_active', $data);
        }

        return '<div class="alert alert-info"><i class="fa fa-info-circle me-2"></i>'.__('message.No data to display').'</div>';
    }

    public function saveCertificateData(array $data = [], ?string $uuid = null): array
    {
        $this->certificate = SslCertificate::query()->find($uuid);
        if (!$this->certificate) {
            return [
                'status' => 'error',
                'errors' => [__('CertificateAuthority.puqACME.Certificate Model not found')],
                'message' => [__('CertificateAuthority.puqACME.Technical DNS Zone not found')],
                'code' => 404,
            ];
        }

        $this->tech_dns_zone = DnsZone::query()->find($this->module_data['dns_zone_uuid'] ?? '');
        if (empty($this->tech_dns_zone)) {
            return [
                'status' => 'error',
                'errors' => [__('CertificateAuthority.puqACME.Technical DNS Zone not found')],
                'message' => [__('CertificateAuthority.puqACME.Technical DNS Zone not found')],
                'code' => 404,
            ];
        }

        if ($this->certificate->status == 'draft') {
            $validator = Validator::make($data, [
                'account_email' => 'required|email',
                'accept_tos' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if ($value !== 'yes') {
                            $fail(__('CertificateAuthority.puqACME.You must accept the Terms of Service'));
                        }
                    },
                ],
            ], [
                'account_email.required' => __('CertificateAuthority.puqACME.The Email field is required'),
                'account_email.email' => __('CertificateAuthority.puqACME.The Email must be a valid email address'),
                'accept_tos.required' => __('CertificateAuthority.puqACME.You must accept the Terms of Service'),
            ]);

            if ($validator->fails()) {
                return [
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'code' => 422,
                ];
            }
        }

        $this->certificate_data['account_email'] = $data['account_email'];
        $this->certificate_data['accept_tos'] = $data['accept_tos'];

        return [
            'status' => 'success',
            'data' => $this->certificate_data,
            'code' => 200,
        ];
    }

    public function prepareForCertificateIssuance(array $data): array
    {
        $this->certificate = SslCertificate::query()->find($data['uuid']);
        if (empty($this->certificate)) {
            return [
                'status' => 'error',
                'error' => __('CertificateAuthority.puqACME.Certificate Model not found'),
            ];
        }
        $this->getCertificateData($this->certificate->configuration);
        $this->tech_dns_zone = DnsZone::query()->find($this->module_data['dns_zone_uuid']);
        if (empty($this->tech_dns_zone)) {
            return [
                'status' => 'error',
                'error' => __('CertificateAuthority.puqACME.Technical DNS Zone not found'),
            ];
        }

//        if (empty($this->certificate_data['accept_tos']) or $this->certificate_data['accept_tos'] != 'yes') {
//            return [
//                'status' => 'error',
//                'errors' => [__('CertificateAuthority.puqACME.You must accept the Terms of Service')],
//            ];
//        }
        $request = [
            'certificate_uuid' => $this->certificate->uuid,
            'certificate_domain' => $this->certificate->domain,
            'certificate_aliases' => $this->certificate->aliases,
            'tech_dns_zone_uuid' => $this->tech_dns_zone->uuid,
            'tech_dns_zone_name' => $this->tech_dns_zone->name,
        ];
        $response = [];

        // Set DNS records CNAME if possible and Template of TXT
        $set_dns_records = $this->setDnsRecords();
        $this->logDebug('setDnsRecords', $request, $set_dns_records);
        if ($set_dns_records['status'] === 'error') {
            $this->logError('setDnsRecords', $request, $set_dns_records);
        }

        // Generate Account Private Key
        $privateKey = RSA::createKey(2048)->withPadding(RSA::SIGNATURE_PKCS1);

        $this->certificate_data['account_private_key'] = Crypt::encrypt($privateKey->toString('PKCS8'));
        $this->certificate_data['account_email'] =
            !empty($this->certificate_data['account_email'])
                ? $this->certificate_data['account_email']
                : $this->module_data['email'];
        $this->certificate_data['account_id'] = '';
        $this->certificate_data['certificate_url'] = '';
        $this->certificate_data['finalize_url'] = '';
        $this->certificate_data['order_url'] = '';

        // Create ACME Account ---
        $acme = new puqAcmeClient($this->certificate_data, $this->module_data);
        $account_raw = $acme->createAccount();
        $this->logDebug('ACME Account', $request, $account_raw);
        if ($account_raw['status'] === 'error') {
            $this->logError('ACME Account Error', $this->certificate, $account_raw);
            if (!empty($account_raw['data']['detail'])) {
                $account_raw['errors'][] = $account_raw['data']['detail'];
            }

            return $account_raw;
        }
        $this->certificate_data['account_id'] = $account_raw['headers']['Location'][0] ?? '';
        $eab = $acme->getEab();
        $this->certificate_data['eab_kid'] = $eab['eab_kid'] ?? '';
        $this->certificate_data['eab_hmac_key'] = $eab['eab_hmac_key'] ?? '';

        return [
            'status' => 'success',
            'data' => [
                'configuration' => $this->certificate_data,
            ],
        ];
    }

    protected function consoleLog(string $level, string $title, mixed $context = null): void
    {
        $prefix = match (strtolower($level)) {
            'error' => '[ERROR]',
            'info' => '[INFO]',
            'debug' => '[DEBUG]',
            default => '[LOG]',
        };

        echo "\n$prefix $title\n";

        if (!empty($context)) {
            if (is_array($context) || is_object($context)) {
                echo json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
            } else {
                echo $context."\n";
            }
        }
    }

    public function processCertificateIssuance(array $data): array
    {
        // --- Step 1: Load Certificate ---
        $this->certificate = SslCertificate::query()->find($data['uuid']);
        if (empty($this->certificate)) {
            $error = 'Certificate model not found';
            $this->logError('Certificate not found', $data, $error);
            $this->consoleLog('error', 'Certificate not found', $data);

            return ['status' => 'error', 'error' => $error];
        }
        $this->consoleLog('info', 'Certificate loaded', ['domain' => $this->certificate->domain]);

        // --- Step 2: Load Certificate Data ---
        $this->getCertificateData($this->certificate->configuration);
        $this->tech_dns_zone = DnsZone::query()->find($this->module_data['dns_zone_uuid']);
        if (empty($this->tech_dns_zone)) {
            $error = 'Technical DNS Zone not found';
            $this->logError('DNS Zone not found', $data, $error);
            $this->consoleLog('error', 'DNS Zone not found', $data);

            return ['status' => 'error', 'error' => $error];
        }
        $this->consoleLog('info', 'DNS Zone loaded', ['zone' => $this->tech_dns_zone->name]);

        // --- Step 3: Check Terms of Service ---
//        if (empty($this->certificate_data['accept_tos']) || $this->certificate_data['accept_tos'] !== 'yes') {
//            $error = 'You must accept the Terms of Service';
//            $this->logError('TOS not accepted', $data, $error);
//            $this->consoleLog('error', 'TOS not accepted', $data);
//
//            return ['status' => 'error', 'errors' => [$error]];
//        }
//        $this->consoleLog('info', 'Terms of Service accepted');

        // --- Step 4: Check DNS Validation Records ---
        $check_cname_dns_records = $this->checkCnameDnsValidationRecords();
        $check_cname_error = false;
        foreach ($check_cname_dns_records as $record) {
            if ($record['status'] !== 'ok') {
                $check_cname_error = true;
            }
        }

        if ($check_cname_error) {
            $set_dns_records = $this->setDnsRecords();
            $this->logDebug('setDnsRecords', $data, $set_dns_records);
            $this->consoleLog('debug', 'Setting DNS Records', $set_dns_records);

            if ($set_dns_records['status'] === 'error') {
                $this->logError('setDnsRecords failed', $data, $set_dns_records);
                $this->consoleLog('error', 'setDnsRecords failed', $set_dns_records);
            }

            sleep(5);
            $check_cname_dns_records = $this->checkCnameDnsValidationRecords();
            $errors = [];
            foreach ($check_cname_dns_records as $record) {
                if ($record['status'] !== 'ok') {
                    $error_msg = "DNS validation failed for record: {$record['record']}";
                    $errors[] = $error_msg;
                    $this->consoleLog('error', 'DNS Validation Failed', $record);
                }
            }
            if (!empty($errors)) {
                return ['status' => 'error', 'errors' => $errors];
            }
        }
        $this->consoleLog('info', 'DNS Validation passed');

        // --- Step 5: Initialize ACME Client ---
        $acme = new puqAcmeClient($this->certificate_data, $this->module_data);
        $this->consoleLog('info', 'ACME Client initialized', ['ca' => $this->module_data['ca']]);

        // --- Step 6: Create or Check ACME Account ---

        if (empty($this->certificate_data['account_id'])) {
            $account_raw = $acme->createAccount();
            $this->logDebug('ACME Account', $this->certificate, $account_raw);
            $this->consoleLog('debug', 'ACME Account', $account_raw['data'] ?? $account_raw);
            if ($account_raw['status'] === 'error') {
                $this->logError('ACME Account Error', $this->certificate, $account_raw);
                $this->consoleLog('error', 'ACME Account Error', $account_raw['data'] ?? $account_raw);

                return $account_raw;
            }
            $this->certificate_data['account_id'] = $account_raw['headers']['Location'][0] ?? '';
        }

        // --- Step 7: Create ACME Order ---
        if ($this->certificate->wildcard) {
            $domain = '*.'.$this->certificate->domain;
        } else {
            $domain = $this->certificate->domain;
        }

        $domains = array_merge([$domain], $this->certificate->aliases ?? []);
        $order_raw = $acme->createOrder($domains);
        $this->logDebug('ACME Order', $this->certificate, $order_raw);
        $this->consoleLog('debug', 'ACME Order', $order_raw['data'] ?? $order_raw);

        if ($order_raw['status'] === 'error') {
            $this->logError('ACME Order Error', $this->certificate, $order_raw);
            $this->consoleLog('error', 'ACME Order Error', $order_raw['data'] ?? $order_raw);

            return $order_raw;
        }
        $order = $order_raw['data'];
        $this->certificate_data['order_url'] = $order_raw['headers']['Location'][0] ?? '';

        // --- Step 8: Process Authorizations ---
        $authorizations = $acme->getAuthorization($order['authorizations'] ?? []);
        $this->logDebug('ACME Authorizations', $this->certificate, $authorizations);
        $this->consoleLog('debug', 'ACME Authorizations', $authorizations['data'] ?? $authorizations);

        $authorization_errors = [];
        $authorization_valid = [];
        foreach ($authorizations as $authUrl => $auth_data_raw) {
            if ($auth_data_raw['status'] === 'error') {
                $authorization_errors[] = $authUrl.': '.json_encode($auth_data_raw['errors']);
                $this->consoleLog('error', 'Authorization retrieval failed', $auth_data_raw['data'] ?? $auth_data_raw);

                continue;
            }

            $auth_data = $auth_data_raw['data'];
            $record_name = $auth_data['identifier']['value'] ?? '';
            if (empty($record_name)) {
                $error = 'Identifier not found';
                $this->logError('Authorization Error', $auth_data_raw, $error);
                $this->consoleLog('error', 'Authorization Error', $auth_data_raw['data'] ?? $auth_data_raw);
                $authorization_errors[] = $error;

                continue;
            }

            $dnsChallenge = collect($auth_data['challenges'] ?? [])->firstWhere('type', 'dns-01');
            if (empty($dnsChallenge)) {
                $error = "DNS-01 Challenge not found for {$record_name}";
                $this->logError('Challenge Error', $auth_data_raw, $error);
                $this->consoleLog('error', 'Challenge Error', $auth_data_raw['data'] ?? $auth_data_raw);
                $authorization_errors[] = $error;

                continue;
            }

            $token = $dnsChallenge['token'] ?? '';
            if (empty($token)) {
                $error = "Token not found for {$record_name}";
                $this->logError('Challenge Token Error', $auth_data_raw, $error);
                $this->consoleLog('error', 'Challenge Token Error', $auth_data_raw['data'] ?? $auth_data_raw);
                $authorization_errors[] = $error;

                continue;
            }

            // Generate keyAuthorization and set TXT record
            $content = $acme->generateDnsTxtValue($token);
            $this->setTechTxtDnsRecord($record_name, $content);
            $this->consoleLog('info', 'ACME DNS TXT Record Set', [
                'domain' => $this->tech_dns_zone->name,
                'record_name' => $record_name,
                'content' => $content,
            ]);
            for ($i = 5; $i > 0; $i--) {
                echo "Waiting... {$i} sec\r";
                flush();
                sleep(1);
            }
            echo "\n";
            // Trigger challenge
            $trigger_raw = $acme->triggerChallenge($dnsChallenge['url']);
            $this->consoleLog('info', 'Challenge Trigger Info', $trigger_raw['data'] ?? $trigger_raw);

            if ($trigger_raw['status'] === 'error') {
                $authorization_errors[] = $record_name.': '.json_encode($trigger_raw['errors']);
                $this->consoleLog('error', 'Challenge Trigger Error', $trigger_raw['data'] ?? $trigger_raw);

                continue;
            }

            // Wait for validation
            $timeout = 120;
            $interval = 5;
            $elapsed = 0;
            do {
                sleep($interval);
                $status_raw = $acme->getChallengeStatus($dnsChallenge['url']);
                $this->consoleLog('debug', 'Challenge Status', $status_raw['data'] ?? $status_raw);
                $elapsed += $interval;

                if (($status_raw['data']['status'] ?? '') === 'valid') {
                    $authorization_valid[$record_name] = true;
                    $this->consoleLog('info', 'Challenge Validated', ['record' => $record_name]);
                    break;
                }

                if (($status_raw['data']['status'] ?? '') === 'invalid') {
                    $authorization_valid[$record_name] = false;
                    $this->consoleLog('error', 'Challenge Failed',
                        ['record' => $record_name, 'status' => $status_raw['data']]);
                    $authorization_errors[] = $record_name.': '.$status_raw['data']['error']['detail'];
                    break;
                }

            } while ($elapsed < $timeout);

            if (empty($authorization_valid[$record_name])) {
                $authorization_valid[$record_name] = false;
                $this->logError('Challenge Failed', $record_name, $status_raw['data'] ?? $status_raw);
                $this->consoleLog('error', 'Challenge Failed', ['record' => $record_name]);
            }
        }

        if (in_array(false, $authorization_valid, true)) {
            return ['status' => 'error', 'errors' => $authorization_errors];
        }

        // --- Step 9: Finalize ACME Order ---
        $this->certificate_data['finalize_url'] = $order['finalize'] ?? '';

        if (empty($this->certificate_data['finalize_url'])) {
            $error = 'Missing finalize URL in ACME order';
            $this->logError('Finalize Error', $this->certificate, $order);
            $this->consoleLog('error', 'Finalize Error', $order['data'] ?? $order);

            return ['status' => 'error', 'errors' => [$error]];
        }

        $finalize_raw = $acme->finalize($this->certificate_data['finalize_url'], $this->certificate->csr_pem);
        if ($finalize_raw['status'] === 'error') {
            $this->logError('ACME Finalize Error', $this->certificate, $finalize_raw);
            $this->consoleLog('error', 'ACME Finalize Error', $finalize_raw['data'] ?? $finalize_raw);
            if (!empty($finalize_raw['data']['detail'])) {
                $finalize_raw['errors'][] = $finalize_raw['data']['detail'];
            }

            return $finalize_raw;
        }

        // --- Polling until status becomes valid ---
        $maxAttempts = 20;
        $attempt = 0;
        $finalize = $finalize_raw['data'];

        while (($finalize['status'] ?? '') !== 'valid' && $attempt < $maxAttempts) {
            $this->consoleLog('debug', "ACME Finalize polling, attempt: $attempt", $finalize);

            for ($i = 5; $i > 0; $i--) {
                echo "Waiting... {$i} sec\r";
                flush();
                sleep(1);
            }
            $finalize_raw = $acme->getFinalizeStatus($this->certificate_data['order_url']);
            $this->consoleLog('info', 'ACME Finalize Info', $finalize_raw['data'] ?? $finalize_raw);

            if ($finalize_raw['status'] === 'error') {
                $this->logError('ACME Finalize Polling Error', $this->certificate, $finalize_raw);
                $this->consoleLog('error', 'ACME Finalize Error', $finalize_raw['data'] ?? $finalize_raw);

                return $finalize_raw;
            }

            $finalize = $finalize_raw['data'] ?? [];
            $attempt++;
        }

        if (($finalize['status'] ?? '') !== 'valid') {
            $error = 'ACME finalize did not reach valid status in time';
            $this->logError('ACME Finalize Timeout', $this->certificate, $finalize);
            $this->consoleLog('error', 'Finalize timeout', $finalize);

            return ['status' => 'error', 'errors' => [$error]];
        }

        $this->consoleLog('debug', 'ACME Finalize completed', $finalize);

        // --- Step 10: Retrieve and save certificate ---
        $this->certificate_data['certificate_url'] = $finalize['certificate'] ?? ($order['certificate'] ?? null);
        if (empty($this->certificate_data['certificate_url'])) {
            $error = 'No certificate URL provided by ACME';
            $this->consoleLog('error', $error);

            return ['status' => 'error', 'error' => $error];
        }

        $certificate_raw = $acme->getCertificate($this->certificate_data['certificate_url']);

        if ($certificate_raw['status'] === 'error') {
            $this->logError('Get Certificate Error', $this->certificate, $certificate_raw);
            $this->consoleLog('error', 'Get Certificate Error', $certificate_raw['data'] ?? $certificate_raw);

            return $certificate_raw;
        }
        $certificate = $certificate_raw['data'];

        $this->consoleLog('info', 'Certificate successfully issued', [
            'domain' => $this->certificate->domain,
            'status' => 'active',
        ]);

        $clear_dns_records = $this->clearDnsRecords();
        if ($clear_dns_records['status'] === 'error') {
            $this->logError('Clear DNS Records', $this->certificate->domain, $clear_dns_records);
            $this->consoleLog('error', 'Clear DNS Records Error', $clear_dns_records['errors'] ?? '');
        }

        return [
            'status' => 'success',
            'message' => 'Certificate successfully issued',
            'data' => [
                'configuration' => $this->certificate_data,
                'certificate_pem' => $certificate,
                'status' => 'active',
                'issued_at' => now(),
                'last_error' => null,
            ],
        ];
    }

    public function processCertificateRenewal(string $data): array
    {
        return [
            'status' => 'success',
        ];
    }

    public function processCertificateRevocation(string $data): array
    {
        return [
            'status' => 'success',
        ];
    }

    public function fetchCertificateStatus(string $data): array
    {
        return [
            'status' => 'success',
            'data' => [
                // 'serial' => '',
                // 'valid_from' => now()->subDays(30)->toDateString(),
                // 'valid_to' => now()->addDays(335)->toDateString(),
                // 'revoked' => false,
            ],
        ];
    }

    public function setDnsRecords(): array
    {
        $domains = array_merge([$this->certificate->domain], $this->certificate->aliases ?? []);
        $set_dns_record_errors = [];
        foreach ($domains as $domain) {

            $set_tech_dns_record = $this->setTechTxtDnsRecord($domain, $domain);
            $this->logDebug('setDnsRecords', ['domain' => $domain, 'dnzs_zone' => $this->tech_dns_zone],
                $set_tech_dns_record);

            if ($set_tech_dns_record['status'] === 'error') {
                $set_dns_record_errors = array_merge($set_dns_record_errors,
                    (array) $set_tech_dns_record['errors'] ?? []);
                $this->logError('setDnsRecords', ['domain' => $domain, 'dnzs_zone' => $this->tech_dns_zone],
                    $set_tech_dns_record);
            }
        }

        $this->syncDnsValidationCnameRecords();

        return [
            'status' => 'success',
        ];
    }

    public function setTechTxtDnsRecord(string $record_name, string $content): array
    {
        $data = [
            'name' => $record_name,
            'txt' => $content,
            'type' => 'TXT',
            'ttl' => $this->module_data['dns_record_ttl'],
        ];

        $records = $this->tech_dns_zone->dnsRecords()->where('name', $record_name)->where('type', $data['type'])->get();
        foreach ($records as $record) {
            $this->tech_dns_zone->deleteRecord($record->uuid);
        }

        return $this->tech_dns_zone->createUpdateRecord($data);
    }

    public function syncDnsValidationCnameRecords(): array
    {
        $domains = array_merge([$this->certificate->domain], $this->certificate->aliases ?? []);

        foreach ($domains as $domain) {
            $dns_zone = $this->findDnsZoneForDomain($domain);
            if (!$dns_zone) {
                $this->logError('syncDnsValidationCnameRecords', $domain,
                    __('CertificateAuthority.puqACME.No DNS zone found'));

                continue;
            }

            $record_name = '_acme-challenge.'.$domain;

            if (str_ends_with($record_name, '.'.$dns_zone->name)) {
                $record_name = substr($record_name, 0, -strlen('.'.$dns_zone->name));
            }

            $data = [
                'name' => $record_name,
                'target' => rtrim($domain.'.'.$this->tech_dns_zone->name, '.'),
                'type' => 'CNAME',
                'ttl' => $this->module_data['dns_record_ttl'] ?? 30,
            ];

            $dns_zone->dnsRecords()
                ->where('name', $record_name)
                ->where('type', $data['type'])
                ->get()
                ->each(fn($record) => $dns_zone->deleteRecord($record->uuid));

            $create = $dns_zone->createUpdateRecord($data);

            $this->logDebug('syncDnsValidationCnameRecords', [
                'domain' => $domain,
                'data' => $data,
                'zone' => $dns_zone,
            ], $create);

            if (($create['status'] ?? '') === 'error') {
                $this->logError('syncDnsValidationCnameRecords', [
                    'domain' => $domain,
                    'data' => $data,
                    'zone' => $dns_zone,
                ], $create);
            }
        }

        return ['status' => 'success'];
    }

    private function findDnsZoneForDomain(string $domain): ?DnsZone
    {
        $domain = rtrim($domain, '.');
        $parts = explode('.', $domain);
        while (count($parts) > 1) {
            $candidate = implode('.', $parts);
            $zone = DnsZone::query()
                ->where('name', $candidate)
                ->first();
            if ($zone) {
                return $zone;
            }
            array_shift($parts);
        }

        return null;
    }

    public function clearDnsRecords(): array
    {
        $domains = array_merge([$this->certificate->domain], $this->certificate->aliases ?? []);
        $errors = [];

        foreach ($domains as $domain) {
            $txtRecords = $this->tech_dns_zone->dnsRecords()
                ->where('name', $domain)
                ->where('type', 'TXT')
                ->get();

            foreach ($txtRecords as $record) {
                $deleted = $this->tech_dns_zone->deleteRecord($record->uuid);
                if (($deleted['status'] ?? '') === 'error') {
                    $errors[] = "TXT record deletion failed for $domain: " . json_encode($deleted['errors'] ?? []);
                }
            }

            $dns_zone = $this->findDnsZoneForDomain($domain);
            if ($dns_zone) {
                $record_name = '_acme-challenge.' . $domain;
                if (str_ends_with($record_name, '.' . $dns_zone->name)) {
                    $record_name = substr($record_name, 0, -strlen('.' . $dns_zone->name));
                }

                $cnameRecords = $dns_zone->dnsRecords()
                    ->where('name', $record_name)
                    ->where('type', 'CNAME')
                    ->get();

                foreach ($cnameRecords as $record) {
                    $deleted = $dns_zone->deleteRecord($record->uuid);
                    if (($deleted['status'] ?? '') === 'error') {
                        $errors[] = "CNAME record deletion failed for $domain: " . json_encode($deleted['errors'] ?? []);
                    }
                }
            } else {
                $errors[] = "No DNS zone found for $domain";
            }
        }

        if (!empty($errors)) {
            $this->logError('clearDnsRecords', $domains, $errors);
            return ['status' => 'error', 'errors' => $errors];
        }

        return ['status' => 'success'];
    }

    // checking
    public function checkCnameDnsValidationRecords(): array
    {
        $validation_records = [];
        $domains = array_merge([$this->certificate->domain], $this->certificate->aliases ?? []);

        foreach ($domains as $domain) {
            $record_name = '_acme-challenge.'.$domain;
            $expected_target = rtrim($domain.'.'.$this->tech_dns_zone->name, '.');
            $type = 'CNAME';

            $record = $this->queryNsCnameRecord($record_name);

            if (empty($record)) {
                $validation_records[] = [
                    'record' => $record_name,
                    'type' => $type,
                    'target' => $expected_target,
                    'status' => 'missing',
                    'message' => __('CertificateAuthority.puqACME.No DNS record found'),
                ];

                continue;
            }

            $actual_target = $record['target'];
            if ($actual_target !== $expected_target) {
                $status = 'invalid';
                $message = __('CertificateAuthority.puqACME.Record target mismatch');
            } else {
                $status = 'ok';
                $message = __('CertificateAuthority.puqACME.Valid record exists');
            }

            $validation_records[] = [
                'record' => $record_name,
                'type' => $type,
                'target' => $expected_target,
                'status' => $status,
                'message' => $message,
            ];
        }

        return $validation_records;
    }

    protected function queryNsCnameRecord(string $recordName): ?array
    {
        $domainParts = explode('.', $recordName);

        for ($i = 0; $i < count($domainParts) - 1; $i++) {
            $zone = implode('.', array_slice($domainParts, $i));

            try {
                $resolver = new NetDNS2\Resolver([
                    'nameservers' => ['8.8.8.8', '8.8.4.4', '1.1.1.1'],
                    'timeout' => 3,
                    'retry_interval' => 1,
                    'retries' => 1,
                ]);
                $nsResponse = $resolver->query($zone, 'NS');

                if (!empty($nsResponse->answer)) {
                    foreach ($nsResponse->answer as $nsRecord) {
                        $nsHost = rtrim($nsRecord->nsdname, '.');
                        $nsIp = gethostbyname($nsHost);
                        try {
                            $resolverNs = new NetDNS2\Resolver([
                                'nameservers' => [$nsIp],
                                'timeout' => 3,
                                'retry_interval' => 1,
                                'retries' => 1,
                            ]);
                            $cnameResponse = $resolverNs->query($recordName, 'CNAME');

                            if (!empty($cnameResponse->answer)) {
                                $target = rtrim($cnameResponse->answer[0]->cname ?? '', '.');

                                if ($target) {
                                    $result = [
                                        'ns' => $nsHost,
                                        'record' => $recordName,
                                        'target' => $target,
                                    ];

                                    return $result;
                                }
                            }
                        } catch (NetDNS2\Exception $e) {
                            continue;
                        }
                    }
                }
            } catch (NetDNS2\Exception $e) {
                continue;
            }
        }

        return null;
    }
}
