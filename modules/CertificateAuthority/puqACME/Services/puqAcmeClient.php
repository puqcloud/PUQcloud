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

use Illuminate\Support\Facades\Http;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\File\X509;
use Illuminate\Support\Facades\Crypt;


class puqAcmeClient
{
    private string $directory_url;

    private array $directory = [];

    private string $email;

    private string $nonce = '';

    private $account_key;

    private string $account_id = '';

    private string $order_url = '';

    private string $finalize_url = '';

    private string $certificate_url = '';

    public int $api_timeout;

    private bool $eab = false;
    private string $eab_kid;
    private string $eab_hmac_key;

    public function __construct(array $certificate_config, array $ca_config)
    {

        if ($ca_config['ca'] == 'letsencrypt') {
            $this->directory_url = 'https://acme-v02.api.letsencrypt.org/directory';
        }

        if ($ca_config['ca'] == 'letsencrypt_staging') {
            $this->directory_url = 'https://acme-staging-v02.api.letsencrypt.org/directory';
        }

        if ($ca_config['ca'] == 'zerossl') {
            $this->directory_url = 'https://acme.zerossl.com/v2/DV90';
            $this->eab = true;
            $this->eab_kid = $ca_config['eab_kid'] ?? '';

            $this->eab_hmac_key = !empty($ca_config['eab_hmac_key'])
                ? Crypt::decryptString($ca_config['eab_hmac_key'])
                : '';
        }

        $this->api_timeout = $ca_config['api_timeout'] ?? 10;
        $this->email = $certificate_config['account_email'] ?? '';

        $account_key_pem = !empty($certificate_config['account_private_key'])
            ? Crypt::decryptString($certificate_config['account_private_key'])
            : '';

        $this->account_id = $certificate_config['account_id'] ?? '';
        $this->order_url = $certificate_config['order_url'] ?? '';
        $this->finalize_url = $certificate_config['finalize_url'] ?? '';
        $this->certificate_url = $certificate_config['certificate_url'] ?? '';

        if (!empty($account_key_pem)) {
            try {
                $this->account_key = PublicKeyLoader::load($account_key_pem)
                    ->withHash('sha256')
                    ->withPadding(RSA::SIGNATURE_PKCS1);

                $this->logInfo('Account key loaded');
            } catch (\Throwable $e) {
                $this->logError('Account Key Load Error', $e->getMessage());
                throw new \Exception('Invalid account private key.');
            }

            $directory = $this->getDirectory();
            if ($directory['status'] == 'success') {
                $this->directory = $directory['data'];
            }

            $nonce = $this->getNonce();
            if ($nonce['status'] == 'success') {
                $this->nonce = $nonce['data'];
            }
        }

    }

    public function testConnection(): array
    {
        $directory = $this->getDirectory();
        if ($directory['status'] == 'success') {
            $this->directory = $directory['data'];
        } else {
            return $directory;
        }

        $nonce = $this->getNonce();
        if ($nonce['status'] == 'success') {
            $this->nonce = $nonce['data'];
        } else {
            return $nonce;
        }

        return [
            'status' => 'success',
            'message' => 'Connection to API successful',
            'data' => [
                'directory' => $this->directory,
                'nonce' => $this->nonce,
            ],
        ];
    }

    /** ---------------- BASE ---------------- **/
    public function getDirectory(): array
    {
        $requestData = [
            'url' => $this->directory_url,
            'options' => ['verify' => false, 'timeout' => $this->api_timeout],
        ];
        $responseData = [
            'status_code' => null,
            'headers' => [],
            'body' => '',
        ];

        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout($this->api_timeout)
                ->get($this->directory_url);

            $responseData['status_code'] = $response->status();
            $responseData['headers'] = $response->headers();
            $responseData['body'] = $response->body();

            if ($responseData['status_code'] < 200 || $responseData['status_code'] >= 300) {
                $this->logError('Load Directory Error', $requestData, $responseData);

                return [
                    'status' => 'error',
                    'errors' => ["HTTP error: status code {$responseData['status_code']}"],
                    'data' => null,
                ];
            }

            if (empty($responseData['body'])) {
                $this->logError('Load Directory Error', $requestData, $responseData);

                return [
                    'status' => 'error',
                    'errors' => ['Empty response body'],
                    'data' => null,
                ];
            }

            try {
                $directory = $response->json();
            } catch (\Throwable $jsonError) {
                $responseData['json_error'] = $jsonError->getMessage();
                $this->logError('Load Directory JSON Parse Error', $requestData, $responseData);

                return [
                    'status' => 'error',
                    'errors' => ['JSON parse error: '.$jsonError->getMessage()],
                ];
            }

            $responseData['directory'] = $directory;
            $this->logDebug('Directory loaded', $requestData, $responseData);

            return [
                'status' => 'success',
                'data' => $directory,
            ];

        } catch (\Throwable $e) {
            $responseData['exception'] = $e->getMessage();
            $this->logError('Load Directory Error', $requestData, $responseData);

            return [
                'status' => 'error',
                'errors' => [$e->getMessage()],
            ];
        }
    }

    private function getNonce(): array
    {
        $newNonce = $this->directory['newNonce'] ?? '';

        if (empty($newNonce)) {
            $requestData = ['directory' => $this->directory, 'url' => $this->directory_url];
            $responseData = ['error' => 'newNonce is empty in directory'];

            $this->logError('Get Nonce', $requestData, $responseData);

            return [
                'status' => 'error',
                'errors' => ['newNonce is empty in directory'],
                'data' => null,
            ];
        }

        $requestData = ['url' => $newNonce, 'options' => ['verify' => false, 'timeout' => $this->api_timeout]];
        $responseData = ['status_code' => null, 'headers' => [], 'body' => '', 'nonce' => ''];

        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout($this->api_timeout)
                ->head($newNonce);

            $responseData['status_code'] = $response->status();
            $responseData['headers'] = $response->headers();
            $responseData['body'] = $response->body();
            $responseData['nonce'] = $response->header('Replay-Nonce') ?? $response->header('replay-nonce') ?? '';

            if (empty($responseData['nonce'])) {
                $this->logError('Get Nonce - empty', $requestData, $responseData);

                return [
                    'status' => 'error',
                    'errors' => ['Nonce is empty'],
                    'data' => null,
                ];
            }

            $this->logDebug('Nonce fetched', $requestData, $responseData);

            return [
                'status' => 'success',
                'data' => $responseData['nonce'],
            ];

        } catch (\Throwable $e) {
            $responseData['exception'] = $e->getMessage();
            $this->logError('Get Nonce - exception', $requestData, $responseData);

            return [
                'status' => 'error',
                'errors' => [$e->getMessage()],
            ];
        }
    }

    private function getJWK(): array
    {
        $publicKey = $this->account_key->getPublicKey();
        $reflection = new \ReflectionObject($publicKey);

        $modulusProp = $reflection->getProperty('modulus');
        $modulusProp->setAccessible(true);
        $modulus = $modulusProp->getValue($publicKey);

        $exponentProp = $reflection->getProperty('exponent');
        $exponentProp->setAccessible(true);
        $exponent = $exponentProp->getValue($publicKey);

        return [
            'e' => $this->base64url($exponent->toBytes()),
            'kty' => 'RSA',
            'n' => $this->base64url($modulus->toBytes()),
        ];
    }

    private function jws($payload, ?string $url = null): string
    {
        $protected = [
            'alg' => 'RS256',
            'nonce' => $this->nonce,
            'url' => $url,
        ];

        if (!empty($this->account_id)) {
            $protected['kid'] = $this->account_id;
        } else {
            $protected['jwk'] = $this->getJWK();
        }

        $protected64 = $this->base64url(json_encode($protected, JSON_UNESCAPED_SLASHES));

        if ($payload === '') {
            $payload64 = '';
        } elseif (is_array($payload)) {
            if (empty($payload)) {
                $payload64 = $this->base64url('{}');
            } else {
                $payload64 = $this->base64url(json_encode($payload, JSON_UNESCAPED_SLASHES));
            }
        } else {
            $payload64 = $this->base64url($payload);
        }

        $data = "$protected64.$payload64";
        $signature = $this->account_key->sign($data);

        return json_encode([
            'protected' => $protected64,
            'payload' => $payload64,
            'signature' => $this->base64url($signature),
        ]);
    }

    private function createExternalAccountPayload(): array
    {
        $protected = [
            'alg' => 'HS256',
            'kid' => $this->eab_kid,
            'url' => $this->directory['newAccount'],
        ];

        $protected64 = $this->base64url(json_encode($protected, JSON_UNESCAPED_SLASHES));
        $payload64 = $this->base64url(json_encode($this->getJWK(), JSON_UNESCAPED_SLASHES));

        $hmacKey = $this->base64url_decode($this->eab_hmac_key);

        $signature = hash_hmac('sha256', "$protected64.$payload64", $hmacKey, true);

        return [
            'protected' => $protected64,
            'payload' => $payload64,
            'signature' => $this->base64url($signature),
        ];
    }

    private function request(string $url, $payload): array
    {
        $requestData = [
            'url' => $url,
            'payload' => $payload,
            'headers' => ['Content-Type' => 'application/jose+json'],
            'options' => ['verify' => false, 'timeout' => $this->api_timeout],
            'nonce_before' => $this->nonce,
        ];

        $responseData = [
            'status_code' => null,
            'headers' => [],
            'body' => '',
            'nonce_after' => null,
            'json' => null,
        ];

        try {
            $url = rtrim(trim($url), '/');

            $jws = $this->jws($payload, $url);

            $response = Http::withBody($jws, 'application/jose+json')
                ->timeout($this->api_timeout)
                ->withOptions(['verify' => false])
                ->post($url);

            $responseData['status_code'] = $response->status();
            $responseData['headers'] = $response->headers();
            $responseData['body'] = $response->body();
            $responseData['nonce_after'] = $response->header('Replay-Nonce') ?? $this->nonce;

            $this->nonce = $responseData['nonce_after'];

            if ($responseData['status_code'] < 200 || $responseData['status_code'] >= 300) {
                $responseData['json'] = $response->json();
                $this->logError('ACME Request HTTP Error', $requestData, $responseData);

                return [
                    'status' => 'error',
                    'errors' => ["HTTP error: status code {$responseData['status_code']}"],
                    'data' => $responseData['json'] ?? null,
                    'headers' => $responseData['headers'],
                ];
            }

            try {
                $responseData['json'] = $response->json();
            } catch (\Throwable $jsonError) {
                $responseData['json_error'] = $jsonError->getMessage();
                $this->logError('ACME Request JSON Parse Error', $requestData, $responseData);

                return [
                    'status' => 'error',
                    'errors' => ['JSON parse error: '.$jsonError->getMessage()],
                ];
            }

            $this->logInfo('ACME Request Success', $requestData, $responseData);

            return [
                'status' => 'success',
                'data' => $responseData['json'] ?? $responseData['body'],
                'headers' => $responseData['headers'],
            ];

        } catch (\Throwable $e) {
            $responseData['exception'] = $e->getMessage();
            $this->logError('ACME Request Error', $requestData, $responseData);

            return [
                'status' => 'error',
                'errors' => [$e->getMessage()],
            ];
        }
    }

    private function getZerosslEabCredentials(): array
    {
        $url = 'https://api.zerossl.com/acme/eab-credentials-email';

        $requestData = [
            'url' => $url,
            'email' => $this->email,
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'options' => ['verify' => false, 'timeout' => $this->api_timeout],
        ];

        $responseData = [
            'status_code' => null,
            'headers' => [],
            'body' => '',
            'json' => null,
        ];

        try {
            $response = Http::asForm()
                ->timeout($this->api_timeout)
                ->withOptions(['verify' => false])
                ->post($url, [
                    'email' => $this->email,
                ]);

            $responseData['status_code'] = $response->status();
            $responseData['headers'] = $response->headers();
            $responseData['body'] = $response->body();

            if ($responseData['status_code'] < 200 || $responseData['status_code'] >= 300) {
                $responseData['json'] = $response->json();
                $this->logError('ZeroSSL EAB Request HTTP Error', $requestData, $responseData);

                return [
                    'status' => 'error',
                    'errors' => ["HTTP error: status code {$responseData['status_code']}"],
                    'data' => $responseData['json'] ?? null,
                    'headers' => $responseData['headers'],
                ];
            }

            try {
                $responseData['json'] = $response->json();
            } catch (\Throwable $jsonError) {
                $responseData['json_error'] = $jsonError->getMessage();
                $this->logError('ZeroSSL EAB Request JSON Parse Error', $requestData, $responseData);

                return [
                    'status' => 'error',
                    'errors' => ['JSON parse error: '.$jsonError->getMessage()],
                ];
            }

            if (empty($responseData['json']['eab_kid']) || empty($responseData['json']['eab_hmac_key'])) {
                $this->logError('ZeroSSL EAB Request Invalid Response', $requestData, $responseData);

                return [
                    'status' => 'error',
                    'errors' => ['Invalid EAB credentials response: missing eab_kid or eab_hmac_key'],
                    'data' => $responseData['json'],
                ];
            }

            $this->logInfo('ZeroSSL EAB Request Success', $requestData, $responseData);

            return [
                'status' => 'success',
                'data' => $responseData['json'],
                'headers' => $responseData['headers'],
            ];

        } catch (\Throwable $e) {
            $responseData['exception'] = $e->getMessage();
            $this->logError('ZeroSSL EAB Request Error', $requestData, $responseData);

            return [
                'status' => 'error',
                'errors' => [$e->getMessage()],
            ];
        }
    }

    private function base64url($data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64url_decode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /** ---------------- ACCOUNT ---------------- **/
    public function createAccount(): array
    {
        $payload = [
            'termsOfServiceAgreed' => true,
            'contact' => ["mailto:{$this->email}"],
        ];

        if ($this->eab) {

            if (empty($this->eab_kid) or empty($this->eab_hmac_key)) {
                $eab_raw = $this->getZerosslEabCredentials();
                if ($eab_raw['status'] == 'error') {
                    return $eab_raw;
                }
                $this->eab_kid = $eab_raw['data']['eab_kid'];
                $this->eab_hmac_key = $eab_raw['data']['eab_hmac_key'];
            }

            $payload['externalAccountBinding'] = $this->createExternalAccountPayload();
        }

        $response = $this->request($this->directory['newAccount'], $payload);

        if ($response['status'] === 'success') {
            if ($response['data']['status'] == 'valid') {
                $this->account_id = $response['headers']['Location'][0] ?? null;
            }
        }

        return $response;
    }

    /** ---------------- ORDER ---------------- **/
    public function createOrder(array $domains): array
    {
        $identifiers = array_map(fn($d) => ['type' => 'dns', 'value' => $d], $domains);
        $payload = ['identifiers' => $identifiers];

        return $this->request($this->directory['newOrder'], $payload);
    }

    /** ---------------- CHALLENGE DNS-01 ---------------- **/
    public function getAuthorization(array $authUrls): array
    {

        foreach ($authUrls as $url) {
            $results[$url] = $this->request($url, '');
        }

        return $results;
    }

    public function triggerChallenge(string $url): array
    {
        return $this->request($url, []);
    }

    public function getChallengeStatus(string $url): array
    {
        return $this->request($url, '');
    }

    public function generateDnsTxtValue(string $token): string
    {
        $jwk = $this->getJWK();
        if (!is_array($jwk)) {
            throw new \Exception('getJWK must return an array');
        }

        $jwkJson = json_encode($jwk);
        if ($jwkJson === false) {
            throw new \Exception('Failed to json_encode JWK');
        }
        $thumbprint = $this->base64url(hash('sha256', $jwkJson, true));

        $keyAuthorization = $token.'.'.$thumbprint;

        $digest = hash('sha256', $keyAuthorization, true);
        $txtValue = $this->base64url($digest);

        return $txtValue;
    }

    /** ---------------- FINALIZE ---------------- **/
    public function finalize(string $url, string $csrPem): array
    {
        $csrDer = base64_decode(preg_replace('/-----.*?-----|\s+/', '', $csrPem));

        $payload = ['csr' => $this->base64url($csrDer)];

        return $this->request($url, $payload);
    }

    public function getFinalizeStatus(string $url): array
    {
        return $this->request($url, '');
    }

    /** ---------------- CERTIFICATE ---------------- **/
    public function getCertificate(string $url): array
    {
        return $this->request($url, '');
    }

    /** ---------------- OTHER ---------------- **/
    public function getRenewalInfo($CertId): array
    {
        return $this->request($this->directory['renewalInfo'].'/'.$CertId, '');
    }

    public function generateCertId(string $certPem): string
    {
        try {
            $x509 = new X509;
            $cert = $x509->loadX509($certPem);

            if ($cert === false || empty($cert)) {
                throw new \Exception('Failed to load certificate');
            }

            $serialNumber = $cert['tbsCertificate']['serialNumber'] ?? null;

            if (empty($serialNumber)) {
                throw new \Exception('Serial number not found in certificate');
            }

            if (is_object($serialNumber) && method_exists($serialNumber, 'toBytes')) {
                $serialBinary = $serialNumber->toBytes();
            } else {
                $serialBinary = $serialNumber;
            }

            $extensions = $cert['tbsCertificate']['extensions'] ?? [];

            $akiBinary = null;

            foreach ($extensions as $extension) {
                $extnId = $extension['extnId'] ?? null;

                // id-ce-authorityKeyIdentifier = 2.5.29.35
                if ($extnId === 'id-ce-authorityKeyIdentifier' || $extnId === '2.5.29.35') {
                    $extnValue = $extension['extnValue'] ?? null;

                    if (is_array($extnValue)) {
                        $akiBinary = $extnValue['keyIdentifier'] ?? null;
                    } elseif (is_string($extnValue)) {
                        $akiBinary = $extnValue;
                    }

                    break;
                }
            }

            if (empty($akiBinary)) {
                throw new \Exception('Authority Key Identifier not found in certificate');
            }

            $certId = $this->base64url($akiBinary).'.'.$this->base64url($serialBinary);

            $this->logDebug('Generated certId', [
                'aki_length' => strlen($akiBinary),
                'serial_length' => strlen($serialBinary),
                'cert_id' => $certId,
                'serial_hex' => bin2hex($serialBinary),
                'aki_hex' => bin2hex($akiBinary),
            ]);

            return $certId;

        } catch (\Throwable $e) {
            $this->logError('Generate certId Error', ['error' => $e->getMessage()]);
            throw new \Exception('Failed to generate certId: '.$e->getMessage());
        }
    }

    public function revokeCert(string $certPem, int $reason = 0): array
    {
        if (empty($this->directory['revokeCert'])) {
            return [
                'status' => 'error',
                'errors' => ['revokeCert endpoint not found in directory'],
                'data' => null,
            ];
        }

        try {
            $certPem = trim($certPem);

            if (preg_match('/-----BEGIN CERTIFICATE-----(.*?)-----END CERTIFICATE-----/s', $certPem, $matches)) {
                $certPemClean = "-----BEGIN CERTIFICATE-----\n".$matches[1].'-----END CERTIFICATE-----';
            } else {
                throw new \Exception('Invalid certificate PEM format');
            }

            $certBase64 = preg_replace('/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/', '',
                $certPemClean);

            $certDer = base64_decode($certBase64);

            if ($certDer === false || empty($certDer)) {
                throw new \Exception('Failed to decode certificate from base64');
            }

            $x509 = new X509;
            $cert = $x509->loadX509($certDer);

            if (empty($cert)) {
                throw new \Exception('Certificate validation failed - invalid DER format');
            }

            $serialNumber = $cert['tbsCertificate']['serialNumber'] ?? null;
            if ($serialNumber) {
                if (is_object($serialNumber) && method_exists($serialNumber, 'toBytes')) {
                    $serialBytes = $serialNumber->toBytes();
                } else {
                    $serialBytes = $serialNumber;
                }
                $serialHex = strtoupper(bin2hex($serialBytes));
            } else {
                $serialHex = 'unknown';
            }

            $payload = [
                'certificate' => $this->base64url($certDer),
            ];

            if ($reason > 0) {
                $payload['reason'] = $reason;
            }

            $this->logInfo('Revoking certificate', [
                'serial' => $serialHex,
                'reason' => $reason,
                'cert_der_length' => strlen($certDer),
                'cert_base64url_length' => strlen($payload['certificate']),
            ]);

            $result = $this->request($this->directory['revokeCert'], $payload);

            if ($result['status'] === 'success') {
                $this->logInfo('Certificate revoked successfully', ['serial' => $serialHex]);
            }

            return $result;

        } catch (\Throwable $e) {
            $this->logError('Revoke Certificate Error', ['error' => $e->getMessage()]);

            return [
                'status' => 'error',
                'errors' => [$e->getMessage()],
            ];
        }
    }

    public function getEab(): array
    {
        return [
            'eab_kid' => $this->eab_kid ?? '',
            'eab_hmac_key' => Crypt::encryptString($this->eab_hmac_key ?? ''),
        ];
    }

    /** ---------------- LOGGING ---------------- **/
    private function logDebug(string $action, mixed $request = [], mixed $response = []): void
    {
        if (function_exists('logModule')) {
            logModule('CertificateAuthority', 'puqACME', $action, 'debug', $request, $response);
        }
    }

    private function logInfo(string $action, mixed $request = [], mixed $response = []): void
    {
        if (function_exists('logModule')) {
            logModule('CertificateAuthority', 'puqACME', $action, 'info', $request, $response);
        }
    }

    private function logError(string $action, mixed $request = [], mixed $response = []): void
    {
        if (function_exists('logModule')) {
            logModule('CertificateAuthority', 'puqACME', $action, 'error', $request, $response);
        }
    }
}
