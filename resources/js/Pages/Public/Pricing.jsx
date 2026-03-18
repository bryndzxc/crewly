import Badge from '@/Components/UI/Badge';
import PublicLayout from '@/Layouts/PublicLayout';
import { router, usePage } from '@inertiajs/react';
import { useMemo } from 'react';

/**
 * @typedef {Object} PricingFaq
 * @property {string} question
 * @property {string} answer
 */

/**
 * @typedef {Object} PricingPlan
 * @property {string} id
 * @property {string} name
 * @property {number} employees_up_to
 * @property {number} price_monthly
 * @property {string} cta_label
 * @property {string=} tagline
 */

/**
 * @typedef {Object} PricingPayload
 * @property {string} label
 * @property {string} badge
 * @property {string} currency
 * @property {string} billing_interval
 * @property {number=} trial_days
 * @property {string} recommended_plan_id
 * @property {string[]} features
 * @property {PricingPlan[]} plans
 * @property {string} note
 * @property {PricingFaq[]} faq
 */

function formatPhp(amount) {
    try {
        return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(amount);
    } catch {
        return `₱${amount}`;
    }
}

function CheckIcon() {
    return (
        <svg className="mt-0.5 h-4 w-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
    );
}

function PricingPlanCard({ plan, isRecommended, ctaHref, trialDays }) {
    return (
        <div
            className={
                'relative flex h-full flex-col rounded-2xl border bg-white p-8 ' +
                (isRecommended
                    ? 'border-amber-300 shadow-xl shadow-amber-100/80 ring-2 ring-amber-400/40'
                    : 'border-slate-200 shadow-sm shadow-slate-900/5')
            }
        >
            {isRecommended && (
                <div className="absolute -top-3.5 left-1/2 -translate-x-1/2 whitespace-nowrap">
                    <span className="inline-flex items-center rounded-full bg-amber-500 px-4 py-1 text-xs font-semibold text-white shadow-sm">
                        Best for Growing Teams
                    </span>
                </div>
            )}

            {/* Plan header */}
            <div>
                <div className="text-xs font-semibold uppercase tracking-widest text-slate-400">{plan.name} Plan</div>
                {plan.tagline && (
                    <div className="mt-1 text-xs text-slate-500">{plan.tagline}</div>
                )}
                <div className="mt-4 text-sm text-slate-600">
                    Up to <span className="font-semibold text-slate-900">{plan.employees_up_to}</span> employees
                </div>

                <div className="mt-5 flex items-end gap-1.5">
                    <div className="text-4xl font-bold tracking-tight text-slate-900">{formatPhp(plan.price_monthly)}</div>
                    <div className="mb-2 text-sm text-slate-500">/ month</div>
                </div>
                <div className="mt-1.5 text-xs text-slate-500">Manual billing · invoice-based</div>
                <div className="mt-1 text-xs text-slate-500">
                    Free <span className="font-medium text-slate-700">{trialDays}-day trial</span> after approval
                </div>
            </div>

            {/* Divider */}
            <div className="my-6 border-t border-slate-100" />

            {/* Access line */}
            <div className="flex-1">
                <div className="flex items-center gap-2.5 text-sm font-medium text-slate-800">
                    <CheckIcon />
                    Full access to all features
                </div>
                <p className="mt-2 text-xs text-slate-500 leading-relaxed">
                    Everything in the "All plans include" list. No feature restrictions across tiers.
                </p>
            </div>

            {/* CTA */}
            <div className="mt-8">
                <button
                    type="button"
                    onClick={() => router.visit(ctaHref)}
                    className={
                        'w-full rounded-xl px-5 py-3 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 ' +
                        (isRecommended
                            ? 'bg-amber-600 text-white shadow-md shadow-amber-600/20 hover:bg-amber-700'
                            : 'border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50')
                    }
                >
                    {plan.cta_label}
                </button>
            </div>
        </div>
    );
}

export default function PricingPage() {
    const page = usePage();
    const auth = page.props.auth ?? {};

    /** @type {PricingPayload} */
    const pricing = page.props.pricing;

    const trialDays = useMemo(() => {
        const v = Number.parseInt(String(pricing?.trial_days ?? ''), 10);
        return Number.isFinite(v) && v > 0 ? v : 30;
    }, [pricing?.trial_days]);

    const ctaHrefForPlan = useMemo(() => {
        const isLoggedIn = Boolean(auth.user);
        if (isLoggedIn) {
            return route('chat.support', {
                message: "Hi! I'd like to request Founder Access pricing for Crewly.",
            });
        }
        return null;
    }, [auth.user]);

    const recommendedId = String(pricing?.recommended_plan_id || '');
    const plans = Array.isArray(pricing?.plans) ? pricing.plans : [];
    const features = Array.isArray(pricing?.features) ? pricing.features : [];
    const faq = Array.isArray(pricing?.faq) ? pricing.faq : [];

    return (
        <PublicLayout
            title="Pricing"
            description="Founder Access pricing for early partners. Simple employee-tier plans with manual billing."
            image="/storage-images/product_preview.PNG"
        >
            {/* ─── Header ───────────────────────────────────────────── */}
            <div className="relative overflow-hidden border-b border-slate-200 bg-white">
                <div className="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                    <div className="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-amber-50 opacity-70 blur-3xl" />
                </div>

                <div className="relative mx-auto max-w-3xl px-4 py-16 sm:px-6 text-center">
                    <Badge tone="amber">{pricing?.badge || 'Founder Pricing – Limited Slots'}</Badge>

                    <h1 className="mt-5 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">Founder Access</h1>

                    <p className="mx-auto mt-4 max-w-xl text-base text-slate-600 leading-relaxed">
                        Everything you need to manage employees and run payroll — in one system.
                    </p>
                    <p className="mt-2 text-sm text-slate-500">
                        Includes a free <span className="font-semibold text-slate-700">{trialDays}-day trial</span> after approval.
                    </p>

                    <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <button
                            type="button"
                            onClick={() => {
                                if (ctaHrefForPlan) {
                                    router.visit(ctaHrefForPlan);
                                    return;
                                }
                                router.visit(route('register', { plan: recommendedId || 'growth' }));
                            }}
                            className="inline-flex items-center justify-center rounded-xl bg-amber-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-amber-600/20 transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                        >
                            Request Founder Access
                        </button>
                        <button
                            type="button"
                            onClick={() => router.visit(route('public.demo'))}
                            className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-7 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                        >
                            Try Demo
                        </button>
                    </div>
                </div>
            </div>

            <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6">
                {/* ─── All Plans Include ────────────────────────────── */}
                {features.length > 0 && (
                    <div className="mx-auto mb-10 max-w-5xl rounded-2xl border border-slate-200 bg-slate-50/80 px-6 py-5">
                        <div className="text-xs font-semibold uppercase tracking-widest text-slate-500">All plans include</div>
                        <ul className="mt-4 grid grid-cols-1 gap-x-8 gap-y-2.5 sm:grid-cols-2 lg:grid-cols-3">
                            {features.map((item) => (
                                <li key={item} className="flex items-center gap-2.5 text-sm text-slate-700">
                                    <svg className="h-4 w-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    <span>{item}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {/* ─── Plan Cards ───────────────────────────────────── */}
                <div className="mx-auto max-w-5xl">
                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        {plans.map((plan) => (
                            <PricingPlanCard
                                key={plan.id}
                                plan={plan}
                                isRecommended={String(plan.id) === recommendedId}
                                ctaHref={ctaHrefForPlan ?? route('register', { plan: String(plan.id || '') })}
                                trialDays={trialDays}
                            />
                        ))}
                    </div>
                </div>

                {/* ─── Note ─────────────────────────────────────────── */}
                <div className="mx-auto mt-10 max-w-4xl">
                    <div className="flex gap-3 rounded-2xl border border-amber-200/70 bg-amber-50/60 px-5 py-4 text-sm text-slate-700">
                        <svg className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        <p>
                            <span className="font-semibold text-slate-900">Note: </span>
                            {pricing?.note}
                        </p>
                    </div>
                </div>

                {/* ─── FAQ ──────────────────────────────────────────── */}
                <div className="mx-auto mt-16 max-w-3xl">
                    <div className="text-center">
                        <h2 className="text-2xl font-semibold tracking-tight text-slate-900">Frequently Asked Questions</h2>
                        <p className="mt-2 text-sm text-slate-600">Quick answers for founder partners.</p>
                    </div>

                    <div className="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white divide-y divide-slate-100">
                        {faq.map((item) => (
                            <div key={item.question} className="px-6 py-5">
                                <div className="text-sm font-semibold text-slate-900">{item.question}</div>
                                <div className="mt-2 text-sm text-slate-600 leading-relaxed">{item.answer}</div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* ─── Bottom CTA ───────────────────────────────────── */}
                <div className="mx-auto mt-16 max-w-3xl overflow-hidden rounded-2xl bg-slate-900">
                    <div className="px-8 py-10 text-center">
                        <div className="text-xs font-semibold uppercase tracking-widest text-amber-400">Limited availability</div>
                        <h2 className="mt-3 text-xl font-semibold text-white">Ready to get started?</h2>
                        <p className="mt-2 text-sm text-slate-400">Founder slots are limited. Lock in your pricing now.</p>
                        <div className="mt-6 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                            <button
                                type="button"
                                onClick={() => {
                                    if (ctaHrefForPlan) {
                                        router.visit(ctaHrefForPlan);
                                        return;
                                    }
                                    router.visit(route('register', { plan: recommendedId || 'growth' }));
                                }}
                                className="inline-flex items-center justify-center rounded-xl bg-amber-500 px-7 py-3 text-sm font-semibold text-white transition hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-slate-900"
                            >
                                Request Founder Access
                            </button>
                            <button
                                type="button"
                                onClick={() => router.visit(route('public.demo'))}
                                className="inline-flex items-center justify-center rounded-xl border border-slate-600 bg-transparent px-7 py-3 text-sm font-semibold text-slate-300 transition hover:border-slate-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 focus:ring-offset-slate-900"
                            >
                                Try Demo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
