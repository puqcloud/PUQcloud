<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_canned_responses', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('title');
            $table->text('message');
            $table->uuid('department_uuid')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('department_uuid')->references('uuid')->on('ticket_departments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_canned_responses');
    }
};
