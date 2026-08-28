<?php

namespace App\Filament\Pages;

use App\Filament\Support\MediaRichEditor;
use App\Mail\PrivacyPolicyUpdatedMail;
use App\Models\PrivacyPolicy as PrivacyPolicyModel;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Throwable;
use UnitEnum;

/**
 * Singleton editor for the one privacy-policy row (App\Models\PrivacyPolicy,
 * id 1). Mirrors Filament's own EditRecord/EditProfile pages: a `form()`
 * schema bound to `$data`, rendered through `content()` as an embedded form
 * with a "Save" submit button, since this isn't backed by a Resource.
 *
 * `content` (Dutch) is legally leading; `content_en` is a courtesy
 * translation only - see PrivacyPolicyResource. Saving either field bumps
 * `updated_at`, which forces every resident to re-accept the policy (see
 * User::hasAcceptedCurrentPrivacyPolicy / EnsureUserAcceptedPrivacyPolicy),
 * so "Save & Notify" (a header action, not a form submit button - two
 * submit buttons can't target two different methods on one <form>) also
 * emails every activated resident a heads-up.
 *
 * @property-read Schema $form
 */
class PrivacyPolicy extends Page
{
    use CanUseDatabaseTransactions;
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;

    protected static string|null|UnitEnum $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Privacy Policy';

    protected static ?string $title = 'Privacy Policy';

    protected static ?string $slug = 'privacy-policy';

    protected string $view = 'filament.pages.privacy-policy';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $policy = PrivacyPolicyModel::current();

        $this->form->fill([
            'content' => $policy->content,
            'content_en' => $policy->content_en,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                MediaRichEditor::make('content', 'privacy-policy')
                    ->label('Content (NL - legally leading)')
                    ->required(),
                MediaRichEditor::make('content_en', 'privacy-policy')
                    ->label('Content (EN - courtesy translation)')
                    ->helperText('Informational only; the Dutch version above is legally binding.'),
            ])
            ->statePath('data');
    }

    /**
     * Plain save: persists the form state, no resident notification.
     */
    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $this->persist($this->form->getState());
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        Notification::make()
            ->success()
            ->title('Privacy policy saved')
            ->send();
    }

    /**
     * Save, then email every activated resident that the policy changed and
     * they'll need to re-accept it. Not queued - see PrivacyPolicyUpdatedMail.
     */
    public function saveAndNotify(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $this->persist($this->form->getState());
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        $notified = 0;

        User::query()
            ->whereNotNull('activated_at')
            ->cursor()
            ->each(function (User $user) use (&$notified) {
                Mail::to($user)->send(new PrivacyPolicyUpdatedMail($user));
                $notified++;
            });

        Notification::make()
            ->success()
            ->title('Privacy policy saved')
            ->body("Notified {$notified} activated resident(s) to re-accept it.")
            ->send();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data): PrivacyPolicyModel
    {
        $policy = PrivacyPolicyModel::current();

        $policy->update([
            'content' => $data['content'],
            'content_en' => $data['content_en'],
        ]);

        return $policy;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    protected function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label('Save')
                        ->submit('save')
                        ->keyBindings(['mod+s']),
                ])->key('form-actions'),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveAndNotify')
                ->label('Save & Notify Residents')
                ->color('warning')
                ->icon(Heroicon::Envelope)
                ->requiresConfirmation()
                ->modalDescription('This saves your changes and emails every activated resident that they need to re-accept the privacy policy.')
                ->action('saveAndNotify'),
        ];
    }
}
