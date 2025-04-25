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
        Schema::create('interviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('interview_type')->nullable();
            $table->string('agent_name');
            $table->string('language');
            $table->string('target_name')->nullable();
            $table->text('target_description')->nullable();
            $table->text('welcome_message')->nullable();
            $table->text('goodbye_message')->nullable();
            $table->json('topics')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
