<?php

namespace App\Mail;

use App\Mail\Support\RendersInlinedMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// Not ShouldQueue - see AccountActivatedMail; no queue worker runs in
// production yet, and this is sent in a small synchronous loop over
// activated residents from the "Save & Notify" Filament action.
class PrivacyPolicyUpdatedMail extends Mailable
{
    use Queueable, RendersInlinedMail, SerializesModels;

    public function __construct(public User $user) {}

    public function build(): self
    {
        $url = rtrim(config('services.frontend_url'), '/').'/privacy-policy';

        return $this->subject(__('mail.privacy_policy_updated.subject'))
            ->html($this->renderInlined('mail.privacy-policy-updated', ['user' => $this->user, 'url' => $url]))
            ->text('mail.privacy-policy-updated-text', ['user' => $this->user, 'url' => $url]);
    }
}
