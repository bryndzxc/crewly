<?php

namespace App\Mail;

use App\Models\Company;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceSummaryEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Company $company)
    {
    }

    public function build(): self
    {
        $amountDue = $this->amountDueForPlan((string) ($this->company->plan_name ?? ''));

        $start = CarbonImmutable::now();
        $end = $this->company->next_billing_at
            ? CarbonImmutable::instance($this->company->next_billing_at)
            : $start->addMonth();

        return $this
            ->subject('Crewly invoice summary')
            ->view('emails.billing.invoice-summary')
            ->with([
                'amountDue' => $amountDue,
                'billingPeriod' => [
                    'start_label' => $start->format('M Y'),
                    'end_label' => $end->format('M Y'),
                ],
            ]);
    }

    private function amountDueForPlan(string $planId): ?int
    {
        $planId = strtolower(trim($planId));
        if ($planId === '') {
            return null;
        }

        $plans = config('pricing.founder_access.plans', []);
        if (is_array($plans) && count($plans) > 0) {
            foreach ($plans as $plan) {
                if (!is_array($plan)) {
                    continue;
                }
                if (strtolower((string) ($plan['id'] ?? '')) !== $planId) {
                    continue;
                }

                $price = $plan['price_monthly'] ?? null;
                if (is_int($price)) {
                    return $price;
                }
                if (is_numeric($price)) {
                    return (int) $price;
                }

                return null;
            }
        }

        // Fallback (in case config is cached and doesn't include config/pricing.php yet).
        $fallback = [
            'starter' => 1200,
            'growth' => 2000,
            'pro' => 3500,
        ];

        return $fallback[$planId] ?? null;

    }
}
