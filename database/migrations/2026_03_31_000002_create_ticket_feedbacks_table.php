<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_feedbacks', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('ticket_uuid');
            $table->tinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('ticket_uuid')->references('uuid')->on('tickets')->onDelete('cascade');
            $table->unique('ticket_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_feedbacks');
    }
};
