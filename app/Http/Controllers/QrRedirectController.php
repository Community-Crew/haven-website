<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\RedirectResponse;

/**
 * Public, unauthenticated redirect for dynamic QR codes - lives on its own
 * domain (qr.havencommunity.nl) rather than under /api so scanned URLs stay
 * short. Deliberately a 302, not 301: the whole point of these codes is
 * that destination_url can be repointed later without reprinting anything,
 * which a permanently-cached 301 would defeat.
 */
class QrRedirectController extends Controller
{
    public function __invoke(QrCode $qrCode): RedirectResponse
    {
        $qrCode->recordVisit();

        return redirect()->away($qrCode->destination_url, 302);
    }
}
