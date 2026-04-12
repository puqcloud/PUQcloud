<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_email_blacklist', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('email')->unique();
            $table->string('reason')->nullable();
            $table->uuid('admin_uuid')->nullable();
            $table->timestamps();

            $table->foreign('admin_uuid')->references('uuid')->on('admins')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_email_blacklist');
    }
};