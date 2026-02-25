<?php

namespace App\DTO;

use Illuminate\Support\Arr;

class DeveloperCompanyWithUserCreateData
{
    /**
     * @param  array{company:CompanyCreateData,user:array{name:string,email:string,password:string,role?:string|null}}  $data
     */
    private function __construct(public array $data)
    {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $company = CompanyCreateData::fromArray($payload['company'] ?? []);

        $user = [
            'name' => trim((string) data_get($payload, 'user.name', '')),
            'email' => strtolower(trim((string) data_get($payload, 'user.email', ''))),
            'password' => (string) data_get($payload, 'user.password', ''),
            'role' => data_get($payload, 'user.role'),
        ];

        return new self([
            'company' => $company,
            'user' => Arr::only($user, ['name', 'email', 'password', 'role']),
        ]);
    }

    public function company(): CompanyCreateData
    {
        return $this->data['company'];
    }

    /**
     * @return array{name:string,email:string,password:string,role?:string|null}
     */
    public function user(): array
    {
        return $this->data['user'];
    }
}
