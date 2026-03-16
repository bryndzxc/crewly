import Joyride, { STATUS } from 'react-joyride';
import { useEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';

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
    ];
}

export default function CrewlyTour({ forceRun = false }) {
    const user = usePage().props?.auth?.user;
    const tutorialCompletedAt = user?.tutorial_completed_at ?? null;
    const role = user?.role;

    const steps = useMemo(() => stepsForRole(role), [role]);

    const shouldAutoRun = tutorialCompletedAt === null;
    const [run, setRun] = useState(false);
    const [dismissed, setDismissed] = useState(false);

    useEffect(() => {
        if (forceRun) {
            setDismissed(false);
            setRun(true);
            return;
        }

        if (shouldAutoRun && !dismissed) {
            setRun(true);
            return;
        }

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

    return (
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
                    markCompleted();
                    return;
                }
            }}
        />
    );
}
