<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Logout;

class AuditLogLogout
{
    public function handle(Logout $event): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        app(AuditLogger::class)->log(
            'auth.logout',
            null,
            [],
            [],
            [
                'auth_guard' => $event->guard,
            ],
            'User logged out.'
        );
    }
}
