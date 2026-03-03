<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'timezone' => 'Asia/Manila',
            'is_active' => true,
            'is_demo' => false,

            'plan_name' => Company::PLAN_STARTER,
            'max_employees' => 20,
            'subscription_status' => Company::SUB_TRIAL,
            'trial_ends_at' => null,
            'next_billing_at' => null,
            'last_payment_at' => null,
            'grace_days' => 7,
            'billing_notes' => null,
        ];
    }
}
