<?php

namespace App\Console\Commands;

use App\Agents\InterviewAgent;
use App\Enums\InterviewSessionStatus;
use App\Models\InterviewSession;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessStaleInterviewSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'interview:process-stale';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process interview sessions that have not been updated for more than two hours';

    /**
     * Execute the console command.
     */
    public function handle(InterviewAgent $interviewAgent): int
    {
        $twoHoursAgo = Carbon::now()->subHours(2);

        $staleSessions = InterviewSession
            ::where('status', InterviewSessionStatus::IN_PROGRESS)
            ->where('updated_at', '<', $twoHoursAgo)
            ->get();

        $this->info("Found {$staleSessions->count()} stale interview sessions.");

        foreach ($staleSessions as $session) {
            $this->processStaleSession($interviewAgent, $session);
        }

        $this->info('Stale interview sessions processed successfully.');

        return Command::SUCCESS;
    }

    /**
     * Process a stale interview session.
     */
    private function processStaleSession(InterviewAgent $interviewAgent, InterviewSession $session): void
    {
        $this->info("Processing stale session: {$session->id}");

        $hasUserMessages = $this->hasUserMessages($session);

        if ($hasUserMessages) {
            $this->processSessionWithUserMessages($interviewAgent, $session);
        } else {
            $this->processSessionWithoutUserMessages($session);
        }

        $session->save();
    }

    /**
     * Check if the session has any user messages.
     */
    private function hasUserMessages(InterviewSession $session): bool
    {
        $messages = $session->messages ?? [];

        foreach ($messages as $message) {
            if (isset($message['type']) && $message['type'] === 'user') {
                return true;
            }
        }

        return false;
    }

    /**
     * Process a session that has user messages.
     */
    private function processSessionWithUserMessages(InterviewAgent $interviewAgent, InterviewSession $session): void
    {
        $this->info("Session {$session->id} has user messages, processing as partially completed.");

        $interview = $session->interview;
        $output = $interviewAgent->chat($session->id, '', $interview, true);

        $session->update([
            'summary' => $output['summary'] ?? null,
            'topics' => $output['topics'] ?? [],
            'status' => InterviewSessionStatus::PARTIALLY_COMPLETED,
        ]);

        $this->info("Session {$session->id} marked as partially completed.");
    }

    /**
     * Process a session that has no user messages.
     */
    private function processSessionWithoutUserMessages(InterviewSession $session): void
    {
        $this->info("Session {$session->id} has no user messages, marking as canceled.");

        $session->update(['status' => InterviewSessionStatus::CANCELED]);

        $this->info("Session {$session->id} marked as canceled.");
    }
}
