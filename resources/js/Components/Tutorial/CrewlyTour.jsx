import Joyride, { STATUS } from 'react-joyride';
import { useEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';

const FINAL_STEP = {
    target: 'body',
    placement: 'center',
    title: "You're all set!",
    content: "You can replay this tutorial anytime from your account menu at the top right.",
    disableBeacon: true,
};

function stepsForRole(role) {
    const r = String(role || '').toLowerCase();

    if (r === 'employee') {
        return [
            {
                target: '[data-tour="dashboard"]',
                title: 'Welcome to Crewly',
                content: 'This is your dashboard where you can track your leave, attendance, and payroll-ready information.',
                disableBeacon: true,
            },
            {
                target: '[data-tour="leave"]',
                content: 'Request leave and track your leave status here.',
                disableBeacon: true,
            },
            {
                target: '[data-tour="cash-advance"]',
                content: 'Create and review your cash advance requests here.',
                disableBeacon: true,
            },
            {
                target: '[data-tour="payslips"]',
                content: 'Preview and download your payslips here.',
                disableBeacon: true,
            },
            FINAL_STEP,
        ];
    }

    // Admin / HR / Manager tour
    return [
        {
            target: '[data-tour="dashboard"]',
            title: 'Welcome to Crewly',
            content: 'This is your HR dashboard where you can quickly view employee data, leave summaries, and payroll overview.',
            disableBeacon: true,
        },
        {
            target: '[data-tour="employees"]',
            content: 'Manage your employee records and digital 201 files here.',
            disableBeacon: true,
        },
        {
            target: '[data-tour="leave"]',
            content: 'Employees can request leave and HR can approve or reject them here.',
            disableBeacon: true,
        },
        {
            target: '[data-tour="attendance"]',
            content: 'Track employee attendance and monitor daily activity.',
            disableBeacon: true,
        },
        {
            target: '[data-tour="payroll"]',
            content: 'View payroll summary and generate payslips.',
            disableBeacon: true,
        },
        {
            target: '[data-tour="checklist"]',
            content: 'Follow this checklist to set up your company in Crewly.',
            disableBeacon: true,
        },
        {
            target: '[data-tour="support"]',
            content: 'If you need help, you can message support anytime.',
            disableBeacon: true,
        },
        {
            ...FINAL_STEP,
            content: "You can replay this tutorial anytime from your account menu. Don't forget to check the Setup Checklist on your dashboard to finish configuring your company.",
        },
    ];
}

const btnBase =
    'inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 transition';
const btnPrimary =
    btnBase + ' bg-amber-500 text-white hover:bg-amber-600 focus-visible:ring-amber-500';
const btnSecondary =
    btnBase + ' border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 focus-visible:ring-amber-400';

export default function CrewlyTour({ forceRun = false }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const tutorialCompletedAt = user?.tutorial_completed_at ?? null;
    const role = user?.role;
    const isDemo = Boolean(auth?.company?.is_demo);

    const steps = useMemo(() => stepsForRole(role), [role]);

    // Demo companies always show the welcome modal on each fresh page load
    // so any visitor can experience the tour without needing a fresh account.
    const shouldAutoRun = !tutorialCompletedAt || isDemo;
    const [run, setRun] = useState(false);
    const [dismissed, setDismissed] = useState(false);
    const [showWelcomeModal, setShowWelcomeModal] = useState(false);

    useEffect(() => {
        if (forceRun) {
            setDismissed(false);
            setShowWelcomeModal(false);
            setRun(true);
            return;
        }

        if (shouldAutoRun && !dismissed) {
            setShowWelcomeModal(true);
            return;
        }

        setShowWelcomeModal(false);
        setRun(false);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [forceRun, shouldAutoRun, dismissed]);

    const markCompleted = async () => {
        try {
            await axios.post(route('user.tutorial.complete'), {}, { headers: { Accept: 'application/json' } });
        } catch {
            // ignore
        }

        setDismissed(true);
        setRun(false);
        // Refresh auth props to reflect the completed state on future pages.
        try {
            router.reload({ only: ['auth'] });
        } catch {
            // ignore
        }
    };

    const handleSkip = () => {
        setShowWelcomeModal(false);
        markCompleted();
    };

    const handleStartTour = () => {
        setShowWelcomeModal(false);
        setRun(true);
    };

    return (
        <>
            {showWelcomeModal && (
                <div className="fixed inset-0 z-[10001] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
                    <div className="w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl ring-1 ring-slate-200">
                        <div className="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 ring-1 ring-amber-200">
                            <svg viewBox="0 0 24 24" className="h-6 w-6 text-amber-700" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>

                        <div className="mt-4 text-lg font-semibold text-slate-900">
                            Welcome to Crewly!
                        </div>
                        <p className="mt-2 text-sm text-slate-600">
                            Let&apos;s take a quick tour of the key features so you can hit the ground running.
                        </p>
                        <p className="mt-1 text-sm text-slate-500">
                            You can always replay this tour later from your account menu.
                        </p>

                        <div className="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-end">
                            <button type="button" onClick={handleSkip} className={btnSecondary}>
                                Skip for Now
                            </button>
                            <button type="button" onClick={handleStartTour} className={btnPrimary}>
                                Start Tour
                            </button>
                        </div>
                    </div>
                </div>
            )}

            <Joyride
                steps={steps}
                run={run}
                continuous={true}
                showSkipButton={true}
                showProgress={true}
                scrollToFirstStep={true}
                disableOverlayClose={true}
                spotlightClicks={false}
                styles={{ options: { zIndex: 10000 } }}
                callback={(data) => {
                    const status = data?.status;

                    if (status === STATUS.FINISHED || status === STATUS.SKIPPED) {
                        if (forceRun) {
                            // Replay mode: tutorial already marked complete — just stop the tour
                            // and navigate away from ?tour=1 so it doesn't restart on refresh.
                            setRun(false);
                            router.visit(
                                String(user?.role || '').toLowerCase() === 'employee'
                                    ? route('employee.dashboard')
                                    : route('dashboard'),
                                { replace: true }
                            );
                        } else {
                            markCompleted();
                        }
                    }
                }}
            />
        </>
    );
}
