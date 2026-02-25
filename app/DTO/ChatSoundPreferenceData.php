<?php

namespace App\DTO;

class ChatSoundPreferenceData
{
    private function __construct(public readonly bool $enabled)
    {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self((bool) ($payload['enabled'] ?? false));
    }
}
