<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DemoAccountApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public User $user,
        public string $passwordPlain,
        public string $loginUrl,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Your Crewly demo account is ready')
            ->view('emails.leads.approved');
    }
}
