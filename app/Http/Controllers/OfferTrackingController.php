<?php

namespace App\Http\Controllers;

use App\Enums\OfferStatus;
use App\Models\SentOffer;
use App\Services\Offers\SendOfferService;
use Illuminate\Http\Response;

class OfferTrackingController extends Controller
{
    /** 1x1 transparent GIF. */
    private const PIXEL = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function __invoke(string $token, SendOfferService $offers): Response
    {
        $sentOffer = SentOffer::query()->where('token', $token)->first();

        if ($sentOffer && $sentOffer->status === OfferStatus::Sent) {
            $sentOffer->update(['status' => OfferStatus::Opened, 'opened_at' => now()]);
            $offers->refreshOfferStatus($sentOffer->offer);
        }

        return response(base64_decode(self::PIXEL), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
