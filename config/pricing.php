<?php

return [
    'founder_access' => [
        'currency' => 'PHP',
        'billing_interval' => 'month',
        'recommended_plan_id' => 'growth',
        'plans' => [
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
        ],
        'faq' => [
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
        ],
    ],
];
