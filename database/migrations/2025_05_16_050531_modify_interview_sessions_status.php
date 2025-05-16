<?php

use App\Enums\InterviewSessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->string('status')->default(InterviewSessionStatus::IN_PROGRESS->value)->after('metadata');
        });

        DB::statement('UPDATE interview_sessions SET status = CASE WHEN finished = 1 THEN ? ELSE ? END', [
            InterviewSessionStatus::COMPLETED->value,
            InterviewSessionStatus::IN_PROGRESS->value
        ]);

        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->dropIndex(['finished']);
            $table->dropColumn('finished');
        });
    }

    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->boolean('finished')->default(false)->index()->after('metadata');
        });

        DB::statement('UPDATE interview_sessions SET finished = CASE
                WHEN status = ? OR status = ? THEN 1
                ELSE 0
                END', [
            InterviewSessionStatus::COMPLETED->value,
            InterviewSessionStatus::PARTIALLY_COMPLETED->value
        ]);

        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
