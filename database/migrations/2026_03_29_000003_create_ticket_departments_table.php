<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_departments', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('email')->nullable();
            $table->boolean('hidden')->default(false);
            $table->boolean('feedback_on_close')->default(false);
            $table->boolean('allow_client_reopen')->default(true);
            $table->integer('auto_close_hours')->nullable();
            $table->boolean('mail_import_enabled')->default(false);
            $table->string('mail_import_type', 10)->nullable();
            $table->string('mail_import_host')->nullable();
            $table->integer('mail_import_port')->nullable();
            $table->string('mail_import_username')->nullable();
            $table->text('mail_import_password')->nullable();
            $table->string('mail_import_encryption', 10)->nullable();
            $table->boolean('mail_import_delete_after')->default(false);
            $table->boolean('mail_import_replies_only')->default(false);
            $table->boolean('mail_import_clients_only')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('allowed_file_extensions')->nullable();
            $table->integer('max_file_size_mb')->nullable();
            $table->integer('max_files_count')->nullable();
            $table->integer('sla_response_hours')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_departments');
    }
};
