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

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use phpseclib3\Crypt\DSA\PrivateKey as DSAPrivateKey;
use phpseclib3\Crypt\EC\PrivateKey as ECPrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey as RSAPrivateKey;
use phpseclib3\File\X509;
use Illuminate\Support\Facades\Crypt;

class SslCertificate extends Model
{

    protected $table = 'ssl_certificates';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'domain',
        'wildcard',
        'aliases',
        'configuration',
        'status', // ['draft', 'pending','processing', 'active', 'expired', 'revoked', 'failed']
        'processing_started_at',
        'last_error',
        'key_size',
        'signature_algorithm',
        'public_key_algorithm',
        'issuer',
        'serial_number_hex',
        'serial_number_dec',
        'certificate_fingerprint_sha1',
        'certificate_fingerprint_md5',
        'certificate_fingerprint_sha256',
        'private_key_pem',
        'public_key_pem',
        'certificate_pem',
        'chain_pem',
        'csr_pem',
        'csr_valid_from',
        'csr_valid_to',
        'organization',        // O
        'organizational_unit', // OU
        'country',             // C
        'state',               // ST
        'locality',            // L
        'email',               // emailAddress
        'issued_at',
        'expires_at',
        'renewed_at',
        'auto_renew_days',
        'revoked_at',
        'revocation_reason',
        'ocsp_checked',
        'ocsp_checked_at',
        'ocsp_status',
        'certificate_authority_uuid',
    ];

    protected $casts = [
        'wildcard' => 'boolean',
        'aliases' => 'array',
        'issuer' => 'array',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'renewed_at' => 'datetime',
        'revoked_at' => 'datetime',
        'ocsp_checked_at' => 'datetime',
        'ocsp_checked' => 'boolean',
        'csr_valid_from' => 'datetime',
        'csr_valid_to' => 'datetime',
        'key_size' => 'integer',
    ];

    protected $hidden = [
        'private_key_pem',
        'csr_pem',
        'certificate_pem',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    protected static function booted()
    {
        static::saving(function ($certificate) {
            $certificate->updateCertificateInfo();
        });
    }

    public function getConfigurationAttribute($value): array
    {
        $configuration = json_decode($value, true);

        return is_array($configuration) ? $configuration : [];
    }

    public function setConfigurationAttribute($value): void
    {
        $this->attributes['configuration'] = json_encode($value);
    }

    public function setPrivateKeyPemAttribute($value): void
    {
        if (!empty($value)) {
            $this->attributes['private_key_pem'] = Crypt::encryptString($value);
        } else {
            $this->attributes['private_key_pem'] = null;
        }
    }

    public function getPrivateKeyPemAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function certificateAuthority(): belongsTo
    {
        return $this->belongsTo(CertificateAuthority::class, 'certificate_authority_uuid', 'uuid');
    }

    public function daysRemaining(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }
        return Carbon::now()->diffInDays($this->expires_at, false);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function needsRenewal(): bool
    {
        if ($this->status != 'active') {
            return false;
        }

        if ($this->auto_renew_days == 0) {
            return false;
        }

        return $this->expires_at && Carbon::now()->diffInDays($this->expires_at, false) <= $this->auto_renew_days;
    }

    public function ensureKeyAndCsr(): void
    {

        $privateKey = RSA::createKey(2048)->withPadding(RSA::SIGNATURE_PKCS1);
        $this->private_key_pem = $privateKey->toString('PKCS8');

        $x509 = new X509();
        $x509->setPrivateKey($privateKey);

        $dn = ['commonName' => $this->wildcard ? '*.'.$this->domain : $this->domain];

        if (!empty($this->organization)) {
            $dn['organizationName'] = $this->organization;
        }
        if (!empty($this->organizational_unit)) {
            $dn['organizationalUnitName'] = $this->organizational_unit;
        }
        if (!empty($this->country)) {
            $dn['countryName'] = $this->country;
        }
        if (!empty($this->state)) {
            $dn['stateOrProvinceName'] = $this->state;
        }
        if (!empty($this->locality)) {
            $dn['localityName'] = $this->locality;
        }
        if (!empty($this->email)) {
            $dn['emailAddress'] = $this->email;
        }

        $x509->setDN($dn);
        $san = [];
        $san[] = ['dNSName' => $this->wildcard ? '*.'.$this->domain : $this->domain];

        if (!empty($this->aliases)) {
            foreach ($this->aliases as $alias) {
                $san[] = ['dNSName' => $alias];
            }
        }

        $x509->loadCSR($x509->saveCSR($x509->signCSR('sha256WithRSAEncryption')));

        $x509->setExtension('id-ce-subjectAltName', $san);
        $csr = $x509->signCSR('sha256WithRSAEncryption');
        $this->csr_pem = $x509->saveCSR($csr);
        $this->csr_valid_from = now();
        $this->save();

    }

    public function generateCsr(): array
    {
        if ($this->status !== 'draft') {
            return [
                'status' => 'error',
                'errors' => [__('error.Status must be draft')],
                'code' => 412,
            ];
        }

        $this->ensureKeyAndCsr();

        $this->last_error = null;

        $this->save();

        // run module command
        $prepare_for_issuance = $this->prepareForCertificateIssuance();
        if ($prepare_for_issuance['status'] == 'error') {

            $this->last_error = isset($prepare_for_issuance['errors'])
                ? implode('; ', $prepare_for_issuance['errors'] ?? [])
                : __('error.Unknown error');

            $this->csr_pem = null;
            $this->csr_valid_from = null;
            $this->status = 'draft';
            $this->save();

            return $prepare_for_issuance;
        }

        if ($this->status == 'draft') {
            $this->status = 'pending';
            $this->last_error = null;
            $this->save();
        }

        $this->renewed_at = null;

        $this->fillAndSave($prepare_for_issuance['data']);

        return ['status' => 'success'];
    }

    public function issuance(): array
    {
        if ($this->status !== 'pending') {
            return [
                'status' => 'error',
                'errors' => [__('error.Status must be pending')],
                'code' => 412,
            ];
        }

        $this->ensureKeyAndCsr();

        $this->update([
            'status' => 'processing',
            'last_error' => null,
        ]);

        // run module command
        $process_issuance = $this->processCertificateIssuance();
        if ($process_issuance['status'] == 'error') {
            $this->update([
                'status' => 'pending',
                'last_error' => isset($process_issuance['errors'])
                    ? implode('; ', $process_issuance['errors'] ?? [])
                    : __('error.Unknown error'),
            ]);

            return $process_issuance;
        }

        $this->fillAndSave($process_issuance['data']);

        return ['status' => 'success'];
    }

    public function renewal(): array
    {
        if ($this->status !== 'active') {
            return [
                'status' => 'error',
                'errors' => [__('error.Status must be active')],
                'code' => 412,
            ];
        }

        if(!$this->needsRenewal()){
            return [
                'status' => 'error',
                'errors' => [__('error.Certificate does not need renewal')],
                'code' => 412,
            ];
        }

        $this->update([
            'status' => 'processing',
            'last_error' => null,
        ]);

        // run module command
        $process_issuance = $this->processCertificateIssuance();
        if ($process_issuance['status'] == 'error') {
            $this->update([
                'status' => 'active',
                'last_error' => isset($process_issuance['errors'])
                    ? implode('; ', $process_issuance['errors'] ?? [])
                    : __('error.Unknown error'),
            ]);

            return $process_issuance;
        }

        $this->renewed_at = now();
        $this->fillAndSave($process_issuance['data']);

        return ['status' => 'success'];
    }

    // Get info --------------------------------------------------------------------------------------------------------

    protected function getCertSerialNumberDec($cert): string
    {
        $serial = $cert['tbsCertificate']['serialNumber'] ?? null;
        if (is_object($serial)) {
            if (method_exists($serial, 'toString')) {
                return $serial->toString();
            } else {
                return (string) $serial;
            }
        } else {
            return $serial;
        }
    }

    protected function getCertSerialNumberHex($cert): ?string
    {
        try {
            $serial = $cert['tbsCertificate']['serialNumber'] ?? null;

            if (empty($serial)) {
                return null;
            }

            if (is_object($serial)) {
                if (method_exists($serial, 'toBytes')) {
                    return strtoupper(bin2hex($serial->toBytes()));
                } elseif (method_exists($serial, 'toString')) {
                    $str = $serial->toString();

                    return ctype_digit($str) ? strtoupper(dechex((int) $str)) : strtoupper($str);
                } else {
                    return strtoupper((string) $serial);
                }
            }

            if (ctype_digit((string) $serial)) {
                return strtoupper(dechex((int) $serial));
            }

            return strtoupper((string) $serial);

        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function getSignatureName($cert): ?string
    {
        $oid = $cert['signatureAlgorithm']['algorithm'] ?? null;

        if (empty($oid)) {
            return null;
        }

        $map = [
            // RSA
            '1.2.840.113549.1.1.5' => 'sha1WithRSAEncryption',
            '1.2.840.113549.1.1.11' => 'sha256WithRSAEncryption',
            '1.2.840.113549.1.1.12' => 'sha384WithRSAEncryption',
            '1.2.840.113549.1.1.13' => 'sha512WithRSAEncryption',
            // ECDSA
            '1.2.840.10045.4.3.2' => 'ecdsa-with-SHA256',
            '1.2.840.10045.4.3.3' => 'ecdsa-with-SHA384',
            '1.2.840.10045.4.3.4' => 'ecdsa-with-SHA512',
            // Ed25519
            '1.3.101.112' => 'ed25519',
            // others (add if needed)
        ];

        return $map[$oid] ?? $oid;
    }

    protected function getKeySize($key): ?string
    {
        if ($key instanceof RSAPrivateKey) {
            $keySize = $key->getLength();
        } elseif ($key instanceof ECPrivateKey) {
            $keySize = $key->getLength();
        } elseif ($key instanceof DSAPrivateKey) {
            $keySize = $key->getLength();
        }

        return $keySize;
    }

    protected function getCertDate(array $cert, string $field): ?string
    {
        $time = $cert['tbsCertificate']['validity'][$field] ?? null;
        if (empty($time)) {
            return null;
        }

        if (is_array($time)) {
            $time = $time['utcTime'] ?? $time['generalTime'] ?? null;
        }

        return $time ? date('Y-m-d H:i:s', strtotime($time)) : null;
    }

    public function updateCertificateInfo(): void
    {
        try {
            $x509 = new X509();
        } catch (\Throwable $e) {
            return;
        }

        try {
            $key = PublicKeyLoader::load($this->private_key_pem);
            $this->key_size = $this->getKeySize($key);
        } catch (\Throwable $e) {
            $this->key_size = null;
        }

        if (!empty($this->certificate_pem)) {
            try {
                $cert = $x509->loadX509($this->certificate_pem);
            } catch (\Throwable $e) {
                $cert = null;
            }

            if ($cert) {
                $this->signature_algorithm = null;
                $this->public_key_algorithm = null;
                $this->issuer = null;
                $this->serial_number_hex = null;
                $this->serial_number_dec = null;
                $this->certificate_fingerprint_sha1 = null;
                $this->certificate_fingerprint_md5 = null;
                $this->certificate_fingerprint_sha256 = null;
                $this->issued_at = null;
                $this->expires_at = null;

                try {
                    $this->signature_algorithm = $this->getSignatureName($cert);
                } catch (\Throwable $e) {
                }

                try {
                    $this->public_key_algorithm = $x509->getPublicKey()->getLoadedFormat();
                } catch (\Throwable $e) {
                }

                try {
                    $this->issuer = $x509->getIssuerDN(true);
                } catch (\Throwable $e) {
                }

                try {
                    $this->serial_number_hex = $this->getCertSerialNumberHex($cert);
                } catch (\Throwable $e) {
                }

                try {
                    $this->serial_number_dec = $this->getCertSerialNumberDec($cert);
                } catch (\Throwable $e) {
                }

                try {
                    if (preg_match('/-----BEGIN CERTIFICATE-----(.*?)-----END CERTIFICATE-----/s',
                        $this->certificate_pem, $matches)) {
                        $cleanPem = base64_decode(trim($matches[1]));
                    } else {
                        $cleanPem = null;
                    }
                } catch (\Throwable $e) {
                    $cleanPem = null;
                }

                if ($cleanPem) {
                    try {
                        $this->certificate_fingerprint_sha1 = strtoupper(implode(':',
                            str_split(hash('sha1', $cleanPem), 2)));
                    } catch (\Throwable $e) {
                        $this->certificate_fingerprint_sha1 = null;
                    }

                    try {
                        $this->certificate_fingerprint_sha256 = strtoupper(implode(':',
                            str_split(hash('sha256', $cleanPem), 2)));
                    } catch (\Throwable $e) {
                        $this->certificate_fingerprint_sha256 = null;
                    }

                    try {
                        $this->certificate_fingerprint_md5 = strtoupper(implode(':',
                            str_split(hash('md5', $cleanPem), 2)));
                    } catch (\Throwable $e) {
                        $this->certificate_fingerprint_md5 = null;
                    }
                }

                try {
                    $this->issued_at = $this->getCertDate($cert, 'notBefore');
                } catch (\Throwable $e) {
                }

                try {
                    $this->expires_at = $this->getCertDate($cert, 'notAfter');
                } catch (\Throwable $e) {
                }
            }
        }
    }

    // Module ----------------------------------------------------------------------------------------------------------
    public function module()
    {
        $ca = $this->certificateAuthority;
        $ca->getModuleData();

        return $ca->module;
    }

    public function getSettingsPage(): string
    {
        $module = $this->module();

        if (empty($module)) {
            return '<h1>'.__('error.The module is not available').'</h1>';
        }

        $data_array = $module->moduleExecute('getCertificateData', $this->configuration);

        if ($data_array['status'] == 'error') {
            return $data_array['message'];
        }
        $data = $data_array['data'];
        $data['uuid'] = $this->uuid;

        $data_array = $module->moduleExecute('getCertificatePage', $data);

        if ($data_array['status'] == 'error') {
            return $data_array['message'];
        }

        return $data_array['data'];
    }

    public function saveModuleData(array $data = []): array
    {
        $module = $this->module();

        if (empty($module)) {
            return [
                'status' => 'error',
                'message' => [__('error.Module not found')],
                'code' => 404,
            ];
        }

        $data = $module->moduleExecute('getCertificateData', $data);
        if ($data['status'] == 'success') {
            $data = $data['data'];
        }

        $data_array = $module->moduleExecute('saveCertificateData', $data, $this->uuid);

        if ($data_array['status'] == 'error') {
            $data_array['code'] = $data_array['code'] ?? 500;

            return $data_array;
        }

        $this->configuration = $data_array['data'];

        return $data_array;
    }

    public function prepareForCertificateIssuance(): array
    {
        $module = $this->module();

        if (empty($module)) {
            return [
                'status' => 'error',
                'message' => [__('error.Module not found')],
                'code' => 404,
            ];
        }

        $data = $module->moduleExecute('getCertificateData', $this->configuration);
        if ($data['status'] == 'success') {
            $data = $data['data'];
        }

        $data['uuid'] = $this->uuid;
        $generate_certificate = $module->moduleExecute('prepareForCertificateIssuance', $data);
        if ($generate_certificate['status'] == 'error') {
            $generate_certificate['code'] = $generate_certificate['code'] ?? 500;

            return $generate_certificate;
        }

        return $generate_certificate;
    }

    public function processCertificateIssuance(): array
    {
        $module = $this->module();

        if (empty($module)) {
            return [
                'status' => 'error',
                'message' => [__('error.Module not found')],
                'code' => 404,
            ];
        }

        $data = $module->moduleExecute('getCertificateData', $this->configuration);
        if ($data['status'] == 'success') {
            $data = $data['data'];
        }

        $data['uuid'] = $this->uuid;
        $generate_certificate = $module->moduleExecute('processCertificateIssuance', $data);
        if ($generate_certificate['status'] == 'error') {
            $generate_certificate['code'] = $generate_certificate['code'] ?? 500;

            return $generate_certificate;
        }

        return $generate_certificate;
    }

    public function fillAndSave(array $data): void
    {
        if (!empty($data) && is_array($data)) {
            $filtered = array_intersect_key($data, array_flip($this->fillable));

            if (!empty($filtered)) {
                $this->fill($filtered);
            }
        }
        $this->save();
    }

}
