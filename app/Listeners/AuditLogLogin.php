<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;

class AuditLogLogin
{
    public function handle(Login $event): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        app(AuditLogger::class)->log(
            'auth.login',
            null,
            [],
            [],
            [
                'auth_guard' => $event->guard,
            ],
            'User logged in.'
        );
    }
}
