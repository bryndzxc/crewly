import Card from '@/Components/UI/Card';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

function clamp(n, min, max) {
    return Math.min(Math.max(n, min), max);
}

function CheckIcon({ className = '' }) {
    return (
        <svg viewBox="0 0 24 24" className={"h-5 w-5 " + className} fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" />
        </svg>
    );
}

function DotIcon({ className = '' }) {
    return (
        <svg viewBox="0 0 24 24" className={"h-5 w-5 " + className} fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" />
        </svg>
    );
}

export default function OnboardingChecklistCard({ auth, checklist }) {
    const items = Array.isArray(checklist?.items) ? checklist.items : [];
    const total = Number(checklist?.total ?? items.length ?? 0);
    const completed = Number(checklist?.completed ?? 0);
    const isComplete = total > 0 && completed >= total;

    const dismissKey = useMemo(() => {
        const uid = Number(auth?.user?.id ?? 0);
        const cid = Number(auth?.company?.id ?? 0);
        return `crewly:onboardingChecklist:dismissedUntil:v1:${cid}:${uid}`;
    }, [auth?.user?.id, auth?.company?.id]);

    const dismissed = useMemo(() => {
        try {
            const until = Number(window.localStorage.getItem(dismissKey) || 0);
            return until > Date.now();
        } catch {
            return false;
        }
    }, [dismissKey]);

    const [isDismissed, setIsDismissed] = useState(dismissed);

    const progressPct = useMemo(() => {
        if (total <= 0) return 0;
        return clamp(Math.round((completed / total) * 100), 0, 100);
    }, [completed, total]);

    if (!checklist) return null;

    if (isComplete) {
        return (
            <div className="mb-6">
                <Card className="p-4 border-green-200/70 bg-green-50/40">
                    <div className="flex items-center gap-3">
                        <CheckIcon className="text-green-700" />
                        <div>
                            <div className="text-sm font-semibold text-slate-900">Setup complete</div>
                            <div className="text-sm text-slate-600">Your core HR setup checklist is done.</div>
                        </div>
                    </div>
                </Card>
            </div>
        );
    }

    if (isDismissed) return null;

    const go = (routeName) => {
        if (!routeName) return;
        router.visit(route(String(routeName)));
    };

    const dismiss = () => {
        const until = Date.now() + 24 * 60 * 60 * 1000;
        try {
            window.localStorage.setItem(dismissKey, String(until));
        } catch {
            // ignore
        }
        setIsDismissed(true);
    };

    const help = () => {
        const msg = 'Hi! I need help completing the Quick Setup checklist.';
        router.visit(route('chat.support', { message: msg }));
    };

    return (
        <div className="mb-6">
            <Card className="p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div className="text-base font-semibold text-slate-900">Quick Setup</div>
                        <div className="mt-1 text-sm text-slate-600">Complete these steps to activate key HR modules.</div>
                    </div>

                    <div className="flex items-center gap-2">
                        <SecondaryButton type="button" onClick={help}>
                            Need help?
                        </SecondaryButton>
                        <button
                            type="button"
                            onClick={dismiss}
                            className="text-sm font-semibold text-slate-600 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:ring-offset-2 rounded px-2 py-1"
                        >
                            Dismiss
                        </button>
                    </div>
                </div>

                <div className="mt-4">
                    <div className="flex items-center justify-between text-sm">
                        <div className="font-semibold text-slate-900">
                            Progress: {completed}/{total}
                        </div>
                        <div className="text-slate-600">{progressPct}%</div>
                    </div>
                    <div className="mt-2 h-2 w-full rounded-full bg-slate-100 ring-1 ring-slate-200 overflow-hidden">
                        <div className="h-full bg-amber-500" style={{ width: `${progressPct}%` }} />
                    </div>
                </div>

                <div className="mt-5 space-y-3">
                    {items.map((item) => (
                        <div key={item.key} className="flex items-start justify-between gap-4 rounded-xl border border-slate-200/70 bg-white/60 px-4 py-3">
                            <div className="flex items-start gap-3 min-w-0">
                                {item.completed ? <CheckIcon className="text-green-700" /> : <DotIcon className="text-slate-400" />}
                                <div className="min-w-0">
                                    <div className="text-sm font-semibold text-slate-900 truncate">{item.title}</div>
                                    <div className="mt-0.5 text-sm text-slate-600">{item.description}</div>
                                </div>
                            </div>

                            <div className="shrink-0">
                                {item.completed ? (
                                    <span className="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-800 ring-1 ring-green-200">
                                        Done
                                    </span>
                                ) : (
                                    <PrimaryButton type="button" onClick={() => go(item.ctaRouteName)}>
                                        {item.ctaLabel || 'Go'}
                                    </PrimaryButton>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </Card>
        </div>
    );
}
