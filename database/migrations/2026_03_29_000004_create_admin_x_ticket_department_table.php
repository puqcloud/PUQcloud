<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_x_ticket_department', function (Blueprint $table) {
            $table->uuid('admin_uuid');
            $table->uuid('ticket_department_uuid');
            $table->foreign('admin_uuid')->references('uuid')->on('admins')->onDelete('cascade');
            $table->foreign('ticket_department_uuid')->references('uuid')->on('ticket_departments')->onDelete('cascade');
            $table->unique(['admin_uuid', 'ticket_department_uuid'], 'admin_ticket_dept_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_x_ticket_department');
    }
};
