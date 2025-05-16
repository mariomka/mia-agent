<?php

use App\Models\Interview;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            $interviews = Interview::all();

            foreach ($interviews as $interview) {
                $topics = $interview->topics;
                $updated = false;

                if (empty($topics) || !is_array($topics)) {
                    continue;
                }

                foreach ($topics as $key => $topic) {
                    if (!isset($topic['enabled'])) {
                        $topics[$key]['enabled'] = true;
                        $updated = true;
                    }
                }

                if ($updated) {
                    $interview->topics = $topics;
                    $interview->save();
                }
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $interviews = Interview::all();

            foreach ($interviews as $interview) {
                $topics = $interview->topics;
                $updated = false;

                if (empty($topics) || !is_array($topics)) {
                    continue;
                }

                foreach ($topics as $key => $topic) {
                    if (isset($topic['enabled'])) {
                        unset($topics[$key]['enabled']);
                        $updated = true;
                    }
                }

                if ($updated) {
                    $interview->topics = $topics;
                    $interview->save();
                }
            }
        });
    }
};
