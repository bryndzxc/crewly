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
        public readonly ?string $leadType,
        public readonly ?string $employeeCountRange,
        public readonly ?string $requestedPlan,
        public readonly ?string $industry,
        public readonly ?string $currentProcess,
        public readonly ?string $biggestPain,
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
            leadType: isset($payload['lead_type']) ? (string) $payload['lead_type'] : null,
            employeeCountRange: isset($payload['employee_count_range']) ? (string) $payload['employee_count_range'] : null,
            requestedPlan: isset($payload['requested_plan']) ? (string) $payload['requested_plan'] : null,
            industry: isset($payload['industry']) ? (string) $payload['industry'] : null,
            currentProcess: isset($payload['current_process']) ? (string) $payload['current_process'] : null,
            biggestPain: isset($payload['biggest_pain']) ? (string) $payload['biggest_pain'] : null,
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
            'lead_type' => $this->leadType,
            'employee_count_range' => $this->employeeCountRange,
            'requested_plan' => $this->requestedPlan,
            'industry' => $this->industry,
            'current_process' => $this->currentProcess,
            'biggest_pain' => $this->biggestPain,
        ];
    }
}
