<?php

namespace App\Services\ActivityIndex;

/**
 * Min-max normalization of the scrape frequency on a log scale, into [0, 1].
 * When all companies have the same frequency there is no signal, so F is 0.
 */
final class FrequencyScale
{
    public function __construct(
        public readonly int $min,
        public readonly int $max,
    ) {}

    public function normalize(int $count): float
    {
        $low = log(1 + max(0, $this->min));
        $high = log(1 + max(0, $this->max));

        if ($high <= $low) {
            return 0.0;
        }

        $value = (log(1 + max(0, $count)) - $low) / ($high - $low);

        return max(0.0, min(1.0, $value));
    }
}
