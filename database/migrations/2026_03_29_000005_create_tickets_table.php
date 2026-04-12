<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->unsignedBigInteger('number');
            $table->string('subject');
            $table->uuid('client_uuid')->nullable();
            $table->uuid('user_uuid')->nullable();
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->uuid('department_uuid');
            $table->uuid('status_uuid');
            $table->string('priority', 10)->default('medium');
            $table->uuid('assigned_admin_uuid')->nullable();
            $table->uuid('service_uuid')->nullable();
            $table->json('cc_emails')->nullable();
            $table->json('tags')->nullable();
            $table->text('admin_notes')->nullable();
            $table->integer('time_spent_minutes')->default(0);
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('sla_reminder_sent')->default(false);
            $table->timestamps();

            $table->foreign('client_uuid')->references('uuid')->on('clients')->onDelete('set null');
            $table->foreign('user_uuid')->references('uuid')->on('users')->onDelete('set null');
            $table->foreign('department_uuid')->references('uuid')->on('ticket_departments')->onDelete('restrict');
            $table->foreign('status_uuid')->references('uuid')->on('ticket_statuses')->onDelete('restrict');
            $table->foreign('assigned_admin_uuid')->references('uuid')->on('admins')->onDelete('set null');
            $table->foreign('service_uuid')->references('uuid')->on('services')->onDelete('set null');

            $table->index('client_uuid');
            $table->index('department_uuid');
            $table->index('status_uuid');
            $table->index('assigned_admin_uuid');
            $table->unique('number');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
