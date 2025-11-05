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

namespace App\Console\Commands;

use App\Models\SslCertificate;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SslManagerCheckExpiration extends Command
{
    protected $signature = 'SslManager:CheckExpiration';
    protected $description = 'Check certificates and mark as expired if past expiration date';

    public function handle()
    {
        $certificates = SslCertificate::all();

        foreach ($certificates as $certificate) {
            if ($certificate->expires_at && Carbon::parse($certificate->expires_at)->isPast()) {
                $certificate->status = 'expired';
                $certificate->save();
                $this->line("Certificate {$certificate->domain} set to expired.");
            }
        }

        $this->info('Expiration check completed.');
    }
}
