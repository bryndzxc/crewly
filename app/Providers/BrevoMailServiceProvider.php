<?php

namespace App\Providers;

use App\Mail\Transport\BrevoTransport;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class BrevoMailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Mail::extend('brevo', function (array $config = []) {
            $apiKey = $config['api_key'] ?? '';

            if (!\is_string($apiKey) || '' === trim($apiKey)) {
                throw new \InvalidArgumentException('Brevo mailer requires BREVO_API_KEY (mail.mailers.brevo.api_key).');
            }

            $timeout = (float) ($config['timeout'] ?? 10);
            $endpoint = $config['endpoint'] ?? null;

            $client = new Client([
                'timeout' => $timeout,
            ]);

            $logger = app('log')->channel($config['log_channel'] ?? null);

            return new BrevoTransport(
                apiKey: $apiKey,
                client: $client,
                endpoint: \is_string($endpoint) ? $endpoint : null,
                timeout: $timeout,
                dispatcher: null,
                logger: $logger,
            );
        });
    }
}
