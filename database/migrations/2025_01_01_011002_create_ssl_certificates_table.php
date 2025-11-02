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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->uuid()->primary();

            $table->string('domain');
            $table->boolean('wildcard')->default(false);
            $table->json('aliases')->nullable();

            $table->longText('configuration');

            $table->enum('status', ['draft', 'pending', 'processing', 'active', 'expired', 'revoked', 'failed'])->default('draft');
            $table->timestamp('processing_started_at')->nullable();
            $table->text('last_error')->nullable();

            $table->integer('key_size')->nullable();             // 2048, 4096
            $table->string('signature_algorithm')->nullable();   // SHA256-RSA
            $table->string('public_key_algorithm')->nullable();  // RSA, EC
            $table->json('issuer')->nullable();                // Issuer CN
            $table->string('serial_number_hex')->nullable();
            $table->string('serial_number_dec')->nullable();
            $table->string('certificate_fingerprint_sha1')->nullable();
            $table->string('certificate_fingerprint_md5')->nullable();
            $table->string('certificate_fingerprint_sha256')->nullable();

            $table->longText('private_key_pem')->nullable();
            $table->longText('public_key_pem')->nullable();
            $table->longText('certificate_pem')->nullable();
            $table->longText('chain_pem')->nullable();
            $table->longText('csr_pem')->nullable();

            $table->string('organization')->nullable();         // O
            $table->string('organizational_unit')->nullable();  // OU
            $table->string('country')->nullable();              // C
            $table->string('state')->nullable();                // ST
            $table->string('locality')->nullable();             // L
            $table->string('email')->nullable();                // emailAddress

            $table->timestamp('csr_valid_from')->nullable();
            $table->timestamp('csr_valid_to')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('renewed_at')->nullable();
            $table->integer('auto_renew_days')->default(7);

            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->boolean('ocsp_checked')->default(false);
            $table->timestamp('ocsp_checked_at')->nullable();
            $table->string('ocsp_status')->nullable();

            $table->uuid('certificate_authority_uuid');
            $table->foreign('certificate_authority_uuid')
                ->references('uuid')
                ->on('certificate_authorities')
                ->onDelete('restrict');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssl_certificates');
    }
};
