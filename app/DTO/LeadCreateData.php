<?php

namespace App\DTO;

class LeadCreateData
{
    private function __construct(
        public readonly string $fullName,
        public readonly string $companyName,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $companySize,
        public readonly ?string $message,
        public readonly ?string $sourcePage,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            fullName: (string) ($payload['full_name'] ?? ''),
            companyName: (string) ($payload['company_name'] ?? ''),
            email: (string) ($payload['email'] ?? ''),
            phone: isset($payload['phone']) ? (string) $payload['phone'] : null,
            companySize: isset($payload['company_size']) ? (string) $payload['company_size'] : null,
            message: isset($payload['message']) ? (string) $payload['message'] : null,
            sourcePage: isset($payload['source_page']) ? (string) $payload['source_page'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toCreateAttributes(): array
    {
        return [
            'full_name' => $this->fullName,
            'company_name' => $this->companyName,
            'email' => $this->email,
            'phone' => $this->phone,
            'company_size' => $this->companySize,
            'message' => $this->message,
            'source_page' => $this->sourcePage,
        ];
    }
}
