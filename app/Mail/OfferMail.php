<?php

namespace App\Mail;

use App\Models\Offer;
use App\Models\SentOffer;
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
                'paragraphs' => preg_split('/\R{2,}/', trim((string) $this->offer->content)) ?: [],
                'trackingUrl' => route('offers.track', ['token' => $this->sentOffer->token]),
            ],
        );
    }
}
