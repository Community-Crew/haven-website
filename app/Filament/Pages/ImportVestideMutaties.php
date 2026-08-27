<?php

namespace App\Filament\Pages;

use App\Models\RegistrationCode;
use App\Services\RegistrationCodeLabelPdf;
use App\Services\VestideMutatieImporter;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Admin tool for Vestide's monthly "mutaties" export: paste the raw
 * "Actief vanaf" / "Contractomschrijving" list, preview which units it
 * matches (and who currently lives there), then apply it - clearing the
 * outgoing resident(s) off each unit and issuing a fresh registration code
 * for the incoming one.
 *
 * @property-read Schema $form
 */
class ImportVestideMutaties extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Members';

    protected static ?string $navigationLabel = 'Import Mutaties';

    protected static ?string $title = 'Import Vestide Mutaties';

    protected string $view = 'filament.pages.import-vestide-mutaties';

    public ?string $raw = null;

    /** @var array<int, array<string, mixed>>|null */
    public ?array $rows = null;

    public bool $applied = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('raw')
                    ->label('Vestide mutaties list')
                    ->helperText('Paste the "Actief vanaf" / "Contractomschrijving" table straight from the Vestide export.')
                    ->rows(14)
                    ->required(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->color('gray')
                ->action(fn (VestideMutatieImporter $importer) => $this->preview($importer)),
            Action::make('apply')
                ->label('Apply mutaties')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('This unassigns and deactivates the current resident(s) of every matched unit and issues a new registration code for each. This cannot be undone automatically.')
                ->visible(fn () => filled($this->rows) && ! $this->applied)
                ->action(fn (VestideMutatieImporter $importer) => $this->apply($importer)),
            Action::make('downloadLabels')
                ->label('Download Unprinted Labels')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Download unprinted labels')
                ->modalDescription('Generates one label per unprinted registration code across all units (not just this batch) and marks every one of them as printed.')
                ->action(fn (RegistrationCodeLabelPdf $pdf) => $this->downloadLabels($pdf)),
        ];
    }

    public function preview(VestideMutatieImporter $importer): void
    {
        $state = $this->form->getState();

        $this->rows = $importer->preview($state['raw'] ?? '');
        $this->applied = false;

        $matched = collect($this->rows)->whereNotNull('unit_id')->count();
        $total = count($this->rows);

        Notification::make()
            ->title("Matched {$matched} of {$total} rows")
            ->status($matched === $total ? 'success' : 'warning')
            ->send();
    }

    public function apply(VestideMutatieImporter $importer): void
    {
        $this->rows = $importer->apply($this->rows ?? []);
        $this->applied = true;

        $applied = collect($this->rows)->where('applied', true)->count();

        Notification::make()
            ->title("Applied {$applied} mutation(s)")
            ->body('New registration codes were generated for each matched unit; the previous resident(s) were unassigned and deactivated.')
            ->success()
            ->send();
    }

    public function downloadLabels(RegistrationCodeLabelPdf $pdf): ?StreamedResponse
    {
        $codes = $pdf->unprinted();

        if ($codes->isEmpty()) {
            Notification::make()->title('No unprinted labels')->warning()->send();

            return null;
        }

        $binary = $pdf->render($codes);

        $codes->toQuery()->update(['printed_at' => now()]);

        // Reflect the new printed status in any preview rows already on screen.
        if ($this->rows) {
            $printedIds = $codes->pluck('id')->all();

            foreach ($this->rows as $i => $row) {
                if (in_array($row['registration_code_id'] ?? null, $printedIds, true)) {
                    $this->rows[$i]['printed_at'] = now();
                }
            }
        }

        Notification::make()
            ->title($codes->count().' label(s) marked as printed')
            ->success()
            ->send();

        return response()->streamDownload(
            fn () => print ($binary),
            'vestide-labels-'.now()->format('Y-m-d').'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function togglePrinted(int $index): void
    {
        if (! isset($this->rows[$index]['registration_code_id'])) {
            return;
        }

        $code = RegistrationCode::find($this->rows[$index]['registration_code_id']);

        if (! $code) {
            return;
        }

        $code->togglePrinted();

        $this->rows[$index]['printed_at'] = $code->printed_at;
    }
}
