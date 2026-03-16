<?php

namespace App\Exceptions;

use RuntimeException;

class GovernmentContributionConfigMissingException extends RuntimeException
{
    public static function forProvider(string $provider, string $effectivityDate): self
    {
        return new self("Missing active {$provider} contribution configuration for effectivity date {$effectivityDate}.");
    }

    public static function forSssRange(string $effectivityDate, float $monthlySalary): self
    {
        $salary = number_format($monthlySalary, 2, '.', '');
        return new self("Missing SSS contribution table range for salary {$salary} (effectivity {$effectivityDate}).");
    }
}
