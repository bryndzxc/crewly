<?php

namespace App\DTO;

class ChatMessageSendData
{
    private function __construct(public readonly string $body)
    {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(trim((string) ($payload['body'] ?? '')));
    }
}
