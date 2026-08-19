<?php

namespace App\Mail;

use App\Mail\Support\RendersInlinedMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// Not ShouldQueue - there's no queue worker running in production yet
// (deploy.yml only reloads php-fpm, nothing consumes the `jobs` table), and
// nothing else in the app queues mail either. Revisit once one exists.
class AccountActivatedMail extends Mailable
{
    use Queueable, RendersInlinedMail, SerializesModels;

    public function __construct(public User $user) {}

    public function build(): self
    {
        return $this->subject(__('mail.account_activated.subject'))
            ->html($this->renderInlined('mail.account-activated', ['user' => $this->user]))
            ->text('mail.account-activated-text', ['user' => $this->user]);
    }
}
