<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('ticket_uuid');
            $table->uuid('admin_uuid')->nullable();
            $table->uuid('user_uuid')->nullable();
            $table->string('name');
            $table->string('email');
            $table->longText('message');
            $table->boolean('is_admin_note')->default(false);
            $table->integer('time_spent_minutes')->default(0);
            $table->timestamps();

            $table->foreign('ticket_uuid')->references('uuid')->on('tickets')->onDelete('cascade');
            $table->foreign('admin_uuid')->references('uuid')->on('admins')->onDelete('set null');
            $table->foreign('user_uuid')->references('uuid')->on('users')->onDelete('set null');

            $table->index('ticket_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
    }
};
