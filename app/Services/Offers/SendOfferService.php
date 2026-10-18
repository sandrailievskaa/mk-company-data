<?php

namespace App\Services\Offers;

use App\Enums\EmailSource;
use App\Enums\OfferStatus;
use App\Mail\OfferMail;
use App\Models\Company;
use App\Models\Offer;
use App\Models\SentOffer;
use App\Services\EmailEnrichment\EmailExtractor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Distributes an offer to its recipient companies.
 *
 * Idempotent by design: a company that already received an offer (sent/opened) is
 * never contacted again, backed by a unique (offer_id, company_id) constraint.
 * Failed deliveries can be retried. Pattern-generated (inferred) addresses are
 * skipped unless explicitly included, because they are not verified.
 */
class SendOfferService
{
    public function __construct(private readonly EmailExtractor $extractor) {}

    /**
     * Count what a distribution would do, without sending anything.
     */
    public function plan(Offer $offer, bool $includeInferred = false): SendReport
    {
        $report = new SendReport;

        foreach ($this->classify($offer, $includeInferred) as $bucket => $companies) {
            $count = $companies->count();
            $report->recipients += $count;

            match ($bucket) {
                'already_sent' => $report->alreadySent = $count,
                'no_email' => $report->skippedNoEmail = $count,
                'inferred' => $report->skippedInferred = $count,
                default => null,
            };
        }

        return $report;
    }

    public function send(Offer $offer, bool $includeInferred = false): SendReport
    {
        $groups = $this->classify($offer, $includeInferred);

        $report = new SendReport(
            recipients: $groups->sum(fn (Collection $c) => $c->count()),
            alreadySent: $groups['already_sent']->count(),
            skippedNoEmail: $groups['no_email']->count(),
            skippedInferred: $groups['inferred']->count(),
        );

        foreach ($groups['deliverable'] as $company) {
            $sentOffer = SentOffer::query()->firstOrCreate(
                ['offer_id' => $offer->id, 'company_id' => $company->id],
                ['recipient_email' => $company->email],
            );

            if ($sentOffer->status->isDelivered()) {
                $report->alreadySent++;

                continue;
            }

            $this->deliver($sentOffer, $offer, (string) $company->email) ? $report->sent++ : $report->failed++;
        }

        $this->refreshOfferStatus($offer);

        return $report;
    }

    /**
     * Retry a single failed delivery.
     */
    public function retry(SentOffer $sentOffer): bool
    {
        if ($sentOffer->status->isDelivered()) {
            return true;
        }

        $offer = $sentOffer->offer;
        $email = (string) ($sentOffer->company?->email ?? $sentOffer->recipient_email);

        $ok = $this->deliver($sentOffer, $offer, $email);
        $this->refreshOfferStatus($offer);

        return $ok;
    }

    public function refreshOfferStatus(Offer $offer): void
    {
        $statuses = $offer->sentOffers()->pluck('status');

        $status = match (true) {
            $statuses->contains(OfferStatus::Opened) => OfferStatus::Opened,
            $statuses->contains(OfferStatus::Sent) => OfferStatus::Sent,
            $statuses->contains(OfferStatus::Failed) => OfferStatus::Failed,
            default => OfferStatus::Pending,
        };

        if ($offer->status !== $status) {
            $offer->update(['status' => $status]);
        }
    }

    private function deliver(SentOffer $sentOffer, Offer $offer, string $email): bool
    {
        $sentOffer->update(['recipient_email' => $email]);

        try {
            Mail::to($email)->send(new OfferMail($offer, $sentOffer));

            $sentOffer->update([
                'status' => OfferStatus::Sent,
                'sent_at' => now(),
                'error_message' => null,
            ]);

            return true;
        } catch (Throwable $e) {
            $sentOffer->update([
                'status' => OfferStatus::Failed,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ]);

            return false;
        }
    }

    /**
     * @return Collection<string, Collection<int, Company>>
     */
    private function classify(Offer $offer, bool $includeInferred): Collection
    {
        $groups = collect([
            'already_sent' => collect(),
            'no_email' => collect(),
            'inferred' => collect(),
            'deliverable' => collect(),
        ]);

        $delivered = $offer->sentOffers()->get()
            ->filter(fn (SentOffer $s) => $s->status->isDelivered())
            ->pluck('company_id')
            ->all();

        foreach ($offer->recipients() as $company) {
            $bucket = match (true) {
                in_array($company->id, $delivered, true) => 'already_sent',
                ! $this->extractor->isUsable($company->email) => 'no_email',
                $company->email_source === EmailSource::Inferred && ! $includeInferred => 'inferred',
                default => 'deliverable',
            };

            $groups[$bucket]->push($company);
        }

        return $groups;
    }
}
