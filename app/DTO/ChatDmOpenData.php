<?php

namespace App\DTO;

class ChatDmOpenData
{
    private function __construct(public readonly int $userId)
    {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self((int) ($payload['user_id'] ?? 0));
    }
}
