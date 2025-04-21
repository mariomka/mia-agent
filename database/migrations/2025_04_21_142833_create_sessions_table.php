<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('interview_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('interview_id');
            $table->foreign('interview_id')->references('id')->on('interviews')->onDelete('cascade');
            $table->string('session_id')->unique()->index();
            $table->json('messages');
            $table->text('summary')->nullable();
            $table->json('topics')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('finished')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_sessions');
    }
};
