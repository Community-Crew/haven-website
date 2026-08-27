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
            ->get()
            ->sortBy(fn (RegistrationCode $code) => $this->sortKey($code))
            ->values();
    }

    /**
     * Building, then floor, then unit - so printed labels come off in the
     * order you'd actually walk the building handing them out. Zero-padding
     * the unit lets e.g. Terra's letter units ("A", "B", ...) and the
     * towers' numeric-string units ("2", "10", ...) sort correctly against
     * each other without needing to know which kind it is up front.
     */
    private function sortKey(RegistrationCode $code): string
    {
        $unit = $code->unit;

        return sprintf(
            '%s|%03d|%s',
            $unit?->building ?? '',
            $unit?->floor ?? 0,
            str_pad((string) ($unit?->unit ?? ''), 4, '0', STR_PAD_LEFT),
        );
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
