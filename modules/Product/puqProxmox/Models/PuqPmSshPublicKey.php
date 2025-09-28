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

namespace Modules\Product\puqProxmox\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PuqPmSshPublicKey extends Model
{
    protected $table = 'puq_pm_ssh_public_keys';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'client_uuid',
        'name',
        'public_key',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_uuid', 'uuid');
    }

    public function getInfo(): array
    {
        $parts = preg_split('/\s+/', trim($this->public_key), 3);

        if (count($parts) < 2) {
            throw new \Exception("Invalid SSH public key format");
        }

        $type = $parts[0];
        $keyData = base64_decode($parts[1], true);
        $comment = $parts[2] ?? null;

        if ($keyData === false) {
            throw new \Exception("Invalid base64 in SSH key");
        }

        // SHA256 fingerprint compatible with OpenSSH
        $fingerprint = 'SHA256:' . rtrim(strtr(base64_encode(hash('sha256', $keyData, true)), '+/', '-_'), '=');

        // Optionally: determine key size (for RSA/ECDSA)
        $keySize = null;
        if (strpos($type, 'rsa') !== false) {
            $rsa = openssl_pkey_get_public("ssh-rsa {$parts[1]}");
            if ($rsa) {
                $details = openssl_pkey_get_details($rsa);
                $keySize = $details['bits'] ?? null;
            }
        } elseif (strpos($type, 'ecdsa') !== false) {
            $keySize = match($type) {
                'ecdsa-sha2-nistp256' => 256,
                'ecdsa-sha2-nistp384' => 384,
                'ecdsa-sha2-nistp521' => 521,
                default => null,
            };
        } elseif (strpos($type, 'ed25519') !== false) {
            $keySize = 256;
        }

        return [
            'type' => $type,
            'fingerprint' => $fingerprint,
            'comment' => $comment,
            'bits' => $keySize,
        ];
    }
}
