<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Thin wrapper around endroid/qr-code so every feature that needs a QR
 * image (registration labels, dynamic QR codes, ...) builds them the same
 * way instead of each re-deriving Builder options.
 *
 * Every code gets the Haven favicon punched into its center by default -
 * error correction is bumped to High to compensate for the obscured area,
 * which is the standard approach for logo-embedded QR codes.
 */
class QrImageBuilder
{
    private const LOGO_PATH = 'resources/images/qr-logo.png';

    /**
     * 400px default: tested with zbarimg against a real (long) activation
     * URL with the logo punched in - anything much smaller than this starts
     * losing enough modules under the logo to fail scanning, even at 15%.
     * Callers encoding short data (e.g. the qr.havencommunity.nl redirect
     * URLs) have headroom to go smaller if they need to; longer data should
     * size up, not down.
     */
    public function dataUri(string $data, int $size = 400, int $margin = 4, bool $withLogo = true): string
    {
        return $this->build($data, $size, $margin, $withLogo)->getDataUri();
    }

    public function png(string $data, int $size = 400, int $margin = 4, bool $withLogo = true): string
    {
        return $this->build($data, $size, $margin, $withLogo)->getString();
    }

    private function build(string $data, int $size, int $margin, bool $withLogo)
    {
        $logoPath = base_path(self::LOGO_PATH);
        $withLogo = $withLogo && is_file($logoPath);

        return (new Builder(
            writer: new PngWriter,
            data: $data,
            errorCorrectionLevel: $withLogo ? ErrorCorrectionLevel::High : ErrorCorrectionLevel::Medium,
            size: $size,
            margin: $margin,
            logoPath: $withLogo ? $logoPath : '',
            // Kept conservative (rather than the more common ~20-25%):
            // tested against zbarimg with a real activation URL's length at
            // this class's actual call-site sizes, 15% was the largest
            // ratio that still scanned reliably once the logo's punched-out
            // area started eating into higher-version (longer data) codes.
            logoResizeToWidth: $withLogo ? (int) round($size * 0.15) : null,
            logoPunchoutBackground: $withLogo,
        ))->build();
    }
}
