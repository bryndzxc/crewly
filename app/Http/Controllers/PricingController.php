<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PricingController extends Controller
{
    public function index(Request $request): Response
    {
        // Intentionally public. CTA behavior is handled client-side based on auth state.

        return Inertia::render('Public/Pricing', [
            'pricing' => [
                'label' => 'Founder Access (Limited Early Partners)',
                'badge' => 'Founder Pricing – Limited Slots',
                'currency' => 'PHP',
                'billing_interval' => 'month',
                'trial_days' => max(1, (int) config('crewly.billing.trial_days', 30)),
                'recommended_plan_id' => 'growth',
                'features' => [
                    'Employee records (Digital 201 files)',
                    'Attendance & Leave tracking',
                    'Incident Management',
                    'Cash Advance tracking',
                    'Payroll processing & payslips',
                    'Government contributions (SSS, PhilHealth, Pag-IBIG)',
                ],
                'plans' => $this->founderAccessPlans(),
                'note' => 'Founder partners retain discounted pricing as the product grows.',
                'faq' => $this->pricingFaq(),
            ],
        ]);
    }

    /**
     * Structured pricing config that can later be moved to config/pricing.php.
     *
     * Example later:
     *   return config('pricing.founder_access.plans', $this->founderAccessPlans());
     */
    private function founderAccessPlans(): array
    {
        // Prefer config/pricing.php when present.
        $fromConfig = config('pricing.founder_access.plans');
        if (is_array($fromConfig) && count($fromConfig) > 0) {
            return $fromConfig;
        }

        return [
            [
                'id' => 'starter',
                'name' => 'Starter',
                'employees_up_to' => 20,
                'price_monthly' => 1200,
                'cta_label' => 'Request Founder Access',
                'tagline' => 'Best for small teams',
            ],
            [
                'id' => 'growth',
                'name' => 'Growth',
                'employees_up_to' => 50,
                'price_monthly' => 2000,
                'cta_label' => 'Request Founder Access',
                'tagline' => 'Best for growing teams',
            ],
            [
                'id' => 'pro',
                'name' => 'Pro',
                'employees_up_to' => 100,
                'price_monthly' => 3500,
                'cta_label' => 'Request Founder Access',
                'tagline' => 'Best for scaling teams',
            ],
        ];
    }

    private function pricingFaq(): array
    {
        $fromConfig = config('pricing.founder_access.faq');
        if (is_array($fromConfig) && count($fromConfig) > 0) {
            return $fromConfig;
        }

        return [
            [
                'question' => 'Is payroll included?',
                'answer' => 'Yes. Crewly includes payroll summary, government contributions (SSS, PhilHealth, Pag-IBIG), and payslip generation. Additional payroll enhancements will continue to be released for founder partners.',
            ],
            [
                'question' => 'Is there a contract?',
                'answer' => 'No long-term contract required for Founder Access. It’s month-to-month while early partner slots are available.',
            ],
            [
                'question' => 'Can I upgrade later?',
                'answer' => 'Yes. You can upgrade (or downgrade) your employee tier at any time as your team grows.',
            ],
            [
                'question' => 'How does billing work?',
                'answer' => 'Manual billing only for now. We’ll invoice you monthly and coordinate payment via your preferred method (e.g., bank transfer).',
            ],
        ];
    }
}
