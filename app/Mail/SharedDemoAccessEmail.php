<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SharedDemoAccessEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $loginUrl,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Your Crewly demo access is ready')
            ->view('emails.leads.shared-demo-access');
    }
}
