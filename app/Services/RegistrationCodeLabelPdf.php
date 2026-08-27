<?php

namespace App\Services;

use App\Models\RegistrationCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Collection;

/**
 * Renders a printable PDF of address labels (Brother QL-700 / DK-11201,
 * 29x90mm) for registration codes - one label per code, each with a QR code
 * linking straight to the activation page with the code pre-filled, plus the
 * code itself printed as a human-readable fallback.
 */
class RegistrationCodeLabelPdf
{
    /**
     * @return Collection<int, RegistrationCode>
     */
    public function unprinted(): Collection
    {
        return RegistrationCode::query()
            ->whereNull('printed_at')
            ->with('unit')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @param  Collection<int, RegistrationCode>  $codes
     */
    public function render(Collection $codes): string
    {
        $baseUrl = rtrim(config('services.frontend_url'), '/');

        $labels = $codes->map(function (RegistrationCode $code) use ($baseUrl) {
            $url = "{$baseUrl}/dashboard/activate?code={$code->code}";

            return [
                'unit_name' => $code->unit?->name ?? 'Unknown unit',
                'code' => $code->code,
                'qr' => $this->buildQrDataUri($url),
                'host' => parse_url($baseUrl, PHP_URL_HOST),
            ];
        });

        return Pdf::loadView('pdf.registration-code-labels', ['labels' => $labels])
            ->output();
    }

    private function buildQrDataUri(string $url): string
    {
        return (new Builder(
            writer: new PngWriter,
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 220,
            margin: 4,
        ))->build()->getDataUri();
    }
}
