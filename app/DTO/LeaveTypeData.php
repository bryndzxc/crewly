<?php

namespace App\DTO;

class LeaveTypeData
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly bool $requires_approval,
        public readonly bool $paid,
        public readonly bool $allow_half_day,
        public readonly ?float $default_annual_credits,
        public readonly bool $is_active,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            code: (string) $data['code'],
            requires_approval: array_key_exists('requires_approval', $data) ? (bool) $data['requires_approval'] : true,
            paid: array_key_exists('paid', $data) ? (bool) $data['paid'] : true,
            allow_half_day: array_key_exists('allow_half_day', $data) ? (bool) $data['allow_half_day'] : true,
            default_annual_credits: array_key_exists('default_annual_credits', $data) && $data['default_annual_credits'] !== null && $data['default_annual_credits'] !== ''
                ? (float) $data['default_annual_credits']
                : null,
            is_active: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'requires_approval' => $this->requires_approval,
            'paid' => $this->paid,
            'allow_half_day' => $this->allow_half_day,
            'default_annual_credits' => $this->default_annual_credits,
            'is_active' => $this->is_active,
        ];
    }
}
