<?php

namespace App\Mail;

use App\Mail\Support\RendersInlinedMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// Queued, unlike the other Mailables in this app (see AccountActivatedMail):
// "Save & Notify Residents" sends this to every activated resident in one
// go, and sending that many synchronously inside a single admin request was
// slow enough to invite a duplicate click (and duplicate sends) - see
// PrivacyPolicy::saveAndNotify(). Drained by the `queue:work
// --stop-when-empty` scheduled task in routes/console.php (QUEUE_CONNECTION
// is 'database'; there's still no persistent queue worker process).
class PrivacyPolicyUpdatedMail extends Mailable implements ShouldQueue
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
