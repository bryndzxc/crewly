import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import StatCard from '@/Components/UI/StatCard';
import { Head } from '@inertiajs/react';

export default function Dashboard({ auth }) {
    const stats = [
        {
            title: 'Total Employees',
            value: 42,
            caption: 'Dummy data (Phase 1)',
            icon: (
                <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                </svg>
            ),
        },
        {
            title: 'Pending Leaves',
            value: 7,
            caption: 'Dummy data (Phase 1)',
            icon: (
                <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M8 7V6a4 4 0 118 0v1" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 7h14l-1 14H6L5 7z" />
                </svg>
            ),
        },
        {
            title: 'Upcoming Deadlines',
            value: 3,
            caption: 'Next 14 days',
            icon: (
                <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M8 7V3m8 4V3" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M4 11h16" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 5h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z" />
                </svg>
            ),
        },
        {
            title: 'Open Positions',
            value: 5,
            caption: 'Dummy data (Phase 1)',
            icon: (
                <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h6" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v6" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M7 7h10v14H7V7z" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2" />
                </svg>
            ),
        },
    ];

    const reminders = [
        { title: 'Probation reviews', meta: 'Due Feb 20', tag: 'People' },
        { title: 'Payroll cutoff', meta: 'Feb 28, 5:00 PM', tag: 'Finance' },
        { title: 'Contract renewals', meta: 'Next week', tag: 'Operations' },
        { title: 'Offer approvals', meta: '2 pending', tag: 'Recruitment' },
    ];

    return (
        <AuthenticatedLayout user={auth.user} header="Dashboard">
            <Head title="Dashboard" />

            <PageHeader title="Dashboard" subtitle="Your people operations snapshot." />

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                {stats.map((stat) => (
                    <StatCard
                        key={stat.title}
                        title={stat.title}
                        value={stat.value}
                        caption={stat.caption}
                        icon={stat.icon}
                    />
                ))}
            </div>

            <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <Card className="p-6 lg:col-span-2">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h3 className="text-base font-semibold text-slate-900">Upcoming reminders</h3>
                            <p className="mt-1 text-sm text-slate-600">A quick view of what needs attention.</p>
                        </div>
                        <div className="hidden sm:inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                            Next 14 days
                        </div>
                    </div>

                    <div className="mt-4 divide-y divide-slate-200/80">
                        {reminders.map((r) => (
                            <div key={r.title} className="flex items-center justify-between gap-4 py-3">
                                <div className="min-w-0">
                                    <div className="truncate text-sm font-medium text-slate-900">{r.title}</div>
                                    <div className="mt-0.5 text-xs text-slate-500">{r.meta}</div>
                                </div>
                                <div className="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                                    {r.tag}
                                </div>
                            </div>
                        ))}
                    </div>
                </Card>

                <Card className="p-6">
                    <h3 className="text-base font-semibold text-slate-900">Quick notes</h3>
                    <p className="mt-1 text-sm text-slate-600">Dummy content for Phase 1 UI.</p>
                    <div className="mt-4 space-y-3 text-sm text-slate-700">
                        <div className="flex items-start gap-3">
                            <span className="mt-0.5 h-2 w-2 rounded-full bg-amber-500" aria-hidden="true" />
                            <span>Review access roles after onboarding.</span>
                        </div>
                        <div className="flex items-start gap-3">
                            <span className="mt-0.5 h-2 w-2 rounded-full bg-amber-500" aria-hidden="true" />
                            <span>Follow up on two pending leave requests.</span>
                        </div>
                        <div className="flex items-start gap-3">
                            <span className="mt-0.5 h-2 w-2 rounded-full bg-amber-500" aria-hidden="true" />
                            <span>Confirm next payroll cutoff timeline.</span>
                        </div>
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
