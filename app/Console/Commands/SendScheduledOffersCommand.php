<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Services\Offers\SendOfferService;
use Illuminate\Console\Command;

class SendScheduledOffersCommand extends Command
{
    protected $signature = 'app:send-scheduled-offers';

    protected $description = 'Send every offer whose scheduled time has come (runs every minute from the scheduler)';

    public function handle(SendOfferService $sender): int
    {
        $due = Offer::query()
            ->whereNotNull('scheduled_at')
            ->whereNull('schedule_dispatched_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->get();

        foreach ($due as $offer) {
            // Marked first: a crash must never cause the same offer to be re-sent.
            $offer->update(['schedule_dispatched_at' => now()]);

            $report = $sender->send($offer);

            $this->line(sprintf(
                '#%d %s: sent %d, failed %d, already sent %d, skipped %d',
                $offer->id, $offer->title, $report->sent, $report->failed, $report->alreadySent,
                $report->skippedNoEmail + $report->skippedInferred
            ));
        }

        if ($due->isEmpty()) {
            $this->info('No scheduled offers are due.');
        }

        return Command::SUCCESS;
    }
}
