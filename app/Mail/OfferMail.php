<?php

namespace App\Mail;

use App\Models\Offer;
use App\Models\SentOffer;
use App\Support\OfferContentFormatter;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OfferMail extends Mailable
{
    public function __construct(
        public readonly Offer $offer,
        public readonly SentOffer $sentOffer,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: (string) $this->offer->title);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.offer',
            with: [
                'bodyHtml' => app(OfferContentFormatter::class)->toHtml($this->offer->content),
                'trackingUrl' => route('offers.track', ['token' => $this->sentOffer->token]),
            ],
        );
    }
}
