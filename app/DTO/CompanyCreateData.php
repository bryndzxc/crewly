<?php

namespace App\DTO;

use Illuminate\Support\Arr;

class CompanyCreateData
{
    /**
     * @param  array{name:string,slug:string,timezone?:string|null,is_active?:bool|null,logo_path?:string|null,address?:string|null}  $data
     */
    private function __construct(public array $data)
    {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $data = [
            'name' => trim((string) ($payload['name'] ?? '')),
            'slug' => trim((string) ($payload['slug'] ?? '')),
            'timezone' => $payload['timezone'] ?? null,
            'is_active' => array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : true,
            'logo_path' => $payload['logo_path'] ?? null,
            'address' => $payload['address'] ?? null,
        ];

        return new self(Arr::only($data, ['name', 'slug', 'timezone', 'is_active', 'logo_path', 'address']));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
