import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
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

function PricingPlanCard({ plan, features, isRecommended, ctaHref, trialDays }) {
    return (
        <Card
            className={
                'p-6 h-full flex flex-col ' +
                (isRecommended ? 'ring-2 ring-amber-400/70 border-amber-200/70' : '')
            }
        >
            <div className="flex items-start justify-between gap-3">
                <div>
                    <div className="text-sm font-semibold text-slate-900">{plan.name} Plan</div>
                    <div className="mt-1 text-sm text-slate-600">Up to {plan.employees_up_to} employees</div>
                </div>
                {isRecommended ? <Badge tone="amber">Recommended</Badge> : null}
            </div>

            <div className="mt-6">
                <div className="flex items-end gap-2">
                    <div className="text-3xl font-semibold tracking-tight text-slate-900">{formatPhp(plan.price_monthly)}</div>
                    <div className="pb-1 text-sm text-slate-600">/ month</div>
                </div>
                <div className="mt-1 text-xs text-slate-500">Manual billing (invoice-based)</div>
                <div className="mt-1 text-xs text-slate-500">Includes a free {trialDays}-day trial after approval</div>
            </div>

            <div className="mt-6">
                <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Includes</div>
                <ul className="mt-3 space-y-2 text-sm text-slate-700">
                    {features.map((item) => (
                        <li key={item} className="flex gap-2">
                            <span className="mt-1.5 h-1.5 w-1.5 rounded-full bg-amber-500/70 flex-none" />
                            <span>{item}</span>
                        </li>
                    ))}
                </ul>
            </div>

            <div className="mt-8 flex flex-col gap-3">
                <PrimaryButton
                    type="button"
                    onClick={() => {
                        router.visit(ctaHref);
                    }}
                    className="w-full"
                >
                    {plan.cta_label}
                </PrimaryButton>
            </div>
        </Card>
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
            <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6">
                <div className="mx-auto max-w-3xl text-center">
                    <Badge tone="amber">{pricing?.badge || 'Founder Pricing – Limited Slots'}</Badge>
                    <h1 className="mt-4 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Founder Access</h1>
                    <p className="mt-3 text-sm text-slate-600">
                        {pricing?.label || 'Founder Access (Limited Early Partners)'}
                    </p>
                    <p className="mt-2 text-sm text-slate-600">
                        Includes a free <span className="font-semibold text-slate-900">{trialDays}-day trial</span> after approval.
                    </p>

                    <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <PrimaryButton
                            type="button"
                            onClick={() => {
                                if (ctaHrefForPlan) {
                                    router.visit(ctaHrefForPlan);
                                    return;
                                }

                                router.visit(route('register', { plan: recommendedId || 'growth' }));
                            }}
                        >
                            Request Founder Access
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={() => {
                                router.visit(route('public.demo'));
                            }}
                        >
                            Request a demo
                        </SecondaryButton>
                    </div>
                </div>

                <div className="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {plans.map((plan) => (
                        <PricingPlanCard
                            key={plan.id}
                            plan={plan}
                            features={features}
                            isRecommended={String(plan.id) === recommendedId}
                            ctaHref={ctaHrefForPlan ?? route('register', { plan: String(plan.id || '') })}
                            trialDays={trialDays}
                        />
                    ))}
                </div>

                <div className="mt-10 mx-auto max-w-4xl">
                    <div className="rounded-2xl border border-slate-200/70 bg-white/70 backdrop-blur px-5 py-4 text-sm text-slate-700">
                        <span className="font-semibold text-slate-900">Note:</span> {pricing?.note}
                    </div>
                </div>

                <div className="mt-14 mx-auto max-w-4xl">
                    <div className="text-center">
                        <h2 className="text-xl font-semibold tracking-tight text-slate-900">FAQ</h2>
                        <p className="mt-2 text-sm text-slate-600">Quick answers for founder partners.</p>
                    </div>

                    <div className="mt-8 grid grid-cols-1 gap-4">
                        {faq.map((item) => (
                            <Card key={item.question} className="p-6">
                                <div className="text-sm font-semibold text-slate-900">{item.question}</div>
                                <div className="mt-2 text-sm text-slate-600">{item.answer}</div>
                            </Card>
                        ))}
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
