<?php

namespace App\Services\Offers;

/**
 * Outcome of planning or running an offer distribution.
 */
final class SendReport
{
    public function __construct(
        public int $recipients = 0,
        public int $sent = 0,
        public int $failed = 0,
        public int $alreadySent = 0,
        public int $skippedNoEmail = 0,
        public int $skippedInferred = 0,
    ) {}

    /** Number of companies that would receive (or just received) a message. */
    public function deliverable(): int
    {
        return $this->recipients - $this->alreadySent - $this->skippedNoEmail - $this->skippedInferred;
    }
}
