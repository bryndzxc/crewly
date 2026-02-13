import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import StatCard from '@/Components/UI/StatCard';
import Badge from '@/Components/UI/Badge';
import { Head, Link } from '@inertiajs/react';

function fullName(employee) {
    const parts = [employee?.first_name, employee?.middle_name, employee?.last_name, employee?.suffix]
        .map((v) => String(v || '').trim())
        .filter(Boolean);

    return parts.join(' ');
}

export default function Dashboard({
    auth,
    employees_count = 0,
    expiring_30_count = 0,
    expired_count = 0,
    expiring_days = 30,
    expiring_soon = [],
    probation_ending_30_count = 0,
    probation_ending_soon = [],
    can_approve_leaves = false,
    pending_leave_approvals_count = 0,
    pending_leave_approvals_top5 = [],
    upcoming_approved_leaves_top5 = [],
    can_view_employee_relations = false,
    open_incidents_count = 0,
    open_incidents_top5 = [],
    can_manage_attendance = false,
    attendance_unmarked_today_count = 0,
    attendance_unmarked_today_top5 = [],
}) {
    const statCardLinkClassName =
        'cursor-pointer transition will-change-transform ' +
        'hover:-translate-y-0.5 hover:shadow-xl hover:shadow-slate-900/10 hover:ring-1 hover:ring-amber-200 ' +
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2';

    const stats = [
        {
            title: 'Total Employees',
            value: employees_count,
            caption: 'All active records',
            href: route('employees.index'),
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
            title: `Expiring Documents (next ${expiring_days} days)`,
            value: expiring_30_count,
            caption: 'Documents with an expiry date',
            href: route('documents.expiring', { days: 30 }),
            icon: (
                <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M8 7V3m8 4V3" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M4 11h16" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 5h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z" />
                </svg>
            ),
        },
        {
            title: 'Expired Documents',
            value: expired_count,
            caption: 'Requires attention',
            href: route('documents.expiring', { expired: 1 }),
            icon: (
                <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v4m0 4h.01" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
            ),
        },
        {
            title: 'Probation Ending Soon (next 30 days)',
            value: probation_ending_30_count,
            caption: 'Upcoming regularization dates',
            href: route('employees.probation', { days: 30 }),
            icon: (
                <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M8 7V3m8 4V3" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M4 11h16" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 5h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 15l2 2 4-4" />
                </svg>
            ),
        },
    ];

    const expiringItems = Array.isArray(expiring_soon) ? expiring_soon : [];
    const probationItems = Array.isArray(probation_ending_soon) ? probation_ending_soon : [];
    const pendingLeaveItems = Array.isArray(pending_leave_approvals_top5) ? pending_leave_approvals_top5 : [];
    const upcomingLeaveItems = Array.isArray(upcoming_approved_leaves_top5) ? upcoming_approved_leaves_top5 : [];
    const openIncidentItems = Array.isArray(open_incidents_top5) ? open_incidents_top5 : [];
    const attendanceUnmarkedItems = Array.isArray(attendance_unmarked_today_top5) ? attendance_unmarked_today_top5 : [];

    if (can_view_employee_relations) {
        stats.push({
            title: 'Open Incidents',
            value: open_incidents_count,
            caption: 'Open / under review cases',
            href: route('employees.index'),
            icon: (
                <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v4m0 4h.01" />
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"
                    />
                </svg>
            ),
        });
    }

    if (can_manage_attendance) {
        stats.push({
            title: "Attendance Pending (Today)",
            value: attendance_unmarked_today_count,
            caption: 'Employees not yet marked (excludes approved leave)',
            href: route('attendance.daily'),
            icon: (
                <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M8 7V3m8 4V3" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M4 11h16" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 5h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 16l2 2 4-4" />
                </svg>
            ),
        });
    }

    const incidentTone = (status) => {
        const s = String(status || '').toUpperCase();
        if (s === 'OPEN') return 'amber';
        if (s === 'UNDER_REVIEW') return 'neutral';
        if (s === 'RESOLVED') return 'success';
        if (s === 'CLOSED') return 'neutral';
        return 'neutral';
    };

    const probationToneForDays = (days) => {
        if (typeof days !== 'number') return 'neutral';
        if (days <= 7) return 'danger';
        if (days <= 30) return 'amber';
        return 'neutral';
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Dashboard" contentClassName="max-w-none">
            <Head title="Dashboard" />

            <PageHeader title="Dashboard" subtitle="Your people operations snapshot." />

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                {stats.map((stat) => (
                    <Link
                        key={stat.title}
                        href={stat.href}
                        className="block rounded-2xl"
                        aria-label={stat.title}
                    >
                        <StatCard
                            title={stat.title}
                            value={stat.value}
                            caption={stat.caption}
                            icon={stat.icon}
                            className={statCardLinkClassName}
                        />
                    </Link>
                ))}
            </div>

            {can_view_employee_relations && (
                <div className="mt-6">
                    <Card className="p-6">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <h3 className="text-base font-semibold text-slate-900">Open Incidents</h3>
                                <p className="mt-1 text-sm text-slate-600">Top 5 incidents that are open or under review.</p>
                            </div>
                            <Link
                                href={route('employees.index')}
                                className="text-sm font-semibold text-amber-800 hover:text-amber-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 rounded"
                            >
                                View employees
                            </Link>
                        </div>

                        <div className="mt-4 overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Employee</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Category</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Date</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                                        <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200 bg-white">
                                    {openIncidentItems.length === 0 && (
                                        <tr>
                                            <td className="px-4 py-10" colSpan={5}>
                                                <div className="mx-auto max-w-xl rounded-2xl border border-amber-200/60 bg-amber-50/40 p-6">
                                                    <div className="text-sm font-semibold text-slate-900">No open incidents</div>
                                                    <div className="mt-1 text-sm text-slate-600">Nothing requiring attention right now.</div>
                                                </div>
                                            </td>
                                        </tr>
                                    )}

                                    {openIncidentItems.map((incident) => {
                                        const employee = incident?.employee;

                                        return (
                                            <tr key={incident.id} className="hover:bg-amber-50/40">
                                                <td className="px-4 py-3 text-sm font-medium text-slate-900">
                                                    {employee?.employee_id ? (
                                                        <Link
                                                            href={route('employees.show', employee.employee_id)}
                                                            className="text-amber-800 hover:text-amber-900"
                                                        >
                                                            {fullName(employee) || employee.employee_code || 'Employee'}
                                                        </Link>
                                                    ) : (
                                                        '—'
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-slate-700">{incident.category}</td>
                                                <td className="px-4 py-3 text-sm text-slate-700">{incident.incident_date ?? '—'}</td>
                                                <td className="px-4 py-3 text-sm text-slate-700">
                                                    <Badge tone={incidentTone(incident.status)}>{incident.status}</Badge>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-right whitespace-nowrap">
                                                    {employee?.employee_id ? (
                                                        <Link
                                                            href={route('employees.show', employee.employee_id)}
                                                            className="inline-flex items-center rounded-md border border-amber-200 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-amber-900 shadow-sm hover:bg-amber-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2"
                                                        >
                                                            View
                                                        </Link>
                                                    ) : null}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>
            )}

            {can_manage_attendance && (
                <div className="mt-6">
                    <Card className="p-6">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <h3 className="text-base font-semibold text-slate-900">Attendance Pending (Today)</h3>
                                <p className="mt-1 text-sm text-slate-600">Top 5 employees not yet marked today (excludes approved leave).</p>
                            </div>
                            <Link
                                href={route('attendance.daily')}
                                className="text-sm font-semibold text-amber-800 hover:text-amber-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 rounded"
                            >
                                Open daily sheet
                            </Link>
                        </div>

                        <div className="mt-4 overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Employee</th>
                                        <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200 bg-white">
                                    {attendanceUnmarkedItems.length === 0 && (
                                        <tr>
                                            <td className="px-4 py-10" colSpan={2}>
                                                <div className="mx-auto max-w-xl rounded-2xl border border-amber-200/60 bg-amber-50/40 p-6">
                                                    <div className="text-sm font-semibold text-slate-900">All set</div>
                                                    <div className="mt-1 text-sm text-slate-600">Everyone is marked (or on approved leave).</div>
                                                </div>
                                            </td>
                                        </tr>
                                    )}

                                    {attendanceUnmarkedItems.map((employee) => (
                                        <tr key={employee.employee_id} className="hover:bg-amber-50/40">
                                            <td className="px-4 py-3 text-sm font-medium text-slate-900">
                                                {fullName(employee) || employee.employee_code || 'Employee'}
                                                <div className="text-xs text-slate-500">{employee.employee_code}</div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-right whitespace-nowrap">
                                                <Link
                                                    href={route('attendance.daily')}
                                                    className="inline-flex items-center rounded-md border border-amber-200 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-amber-900 shadow-sm hover:bg-amber-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2"
                                                >
                                                    Mark
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>
            )}

            <div className="mt-6">
                <Card className="p-6">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h3 className="text-base font-semibold text-slate-900">Expiring Soon</h3>
                            <p className="mt-1 text-sm text-slate-600">Top 5 documents expiring in the next {expiring_days} days.</p>
                        </div>
                        <div className="flex items-center gap-3">
                            <Link
                                href={route('documents.expiring', { days: 30 })}
                                className="text-sm font-semibold text-amber-800 hover:text-amber-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 rounded"
                            >
                                View all
                            </Link>
                            <div className="hidden sm:inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                                Next {expiring_days} days
                            </div>
                        </div>
                    </div>

                    <div className="mt-4 overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Employee</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Document Type</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Expiry Date</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Days Remaining</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 bg-white">
                                {expiringItems.length === 0 && (
                                    <tr>
                                        <td className="px-4 py-10" colSpan={5}>
                                            <div className="mx-auto max-w-xl rounded-2xl border border-amber-200/60 bg-amber-50/40 p-6">
                                                <div className="flex items-start gap-4">
                                                    <div className="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                                                        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M8 7V3m8 4V3" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M4 11h16" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 5h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 15h6" />
                                                        </svg>
                                                    </div>
                                                    <div className="min-w-0">
                                                        <div className="text-sm font-semibold text-slate-900">No documents expiring soon</div>
                                                        <div className="mt-1 text-sm text-slate-600">You’re all set for the next 30 days.</div>
                                                        <div className="mt-4 flex flex-wrap items-center gap-3">
                                                            <Link
                                                                href={route('employees.index')}
                                                                className="inline-flex items-center rounded-md border border-amber-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-amber-900 shadow-sm hover:bg-amber-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2"
                                                            >
                                                                View Employees
                                                            </Link>
                                                            <Link
                                                                href={route('documents.expiring', { days: 30 })}
                                                                className="inline-flex items-center rounded-md border border-amber-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-amber-900 shadow-sm hover:bg-amber-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2"
                                                            >
                                                                View All Documents
                                                            </Link>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                )}

                                {expiringItems.map((doc) => {
                                    const employee = doc?.employee;
                                    const days = doc?.days_to_expiry;
                                    const tone = typeof days === 'number' && days <= 0 ? 'danger' : 'amber';

                                    return (
                                        <tr key={doc.id} className="hover:bg-amber-50/40">
                                            <td className="px-4 py-3 text-sm font-medium text-slate-900">
                                                {employee?.employee_id ? (
                                                    <Link
                                                        href={route('employees.show', employee.employee_id)}
                                                        className="text-amber-800 hover:text-amber-900"
                                                    >
                                                        {fullName(employee) || employee.employee_code || 'Employee'}
                                                    </Link>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{doc.type}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{doc.expiry_date ?? '—'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">
                                                {typeof days === 'number' ? <Badge tone={tone}>{days} days</Badge> : '—'}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-right whitespace-nowrap">
                                                {employee?.employee_id ? (
                                                    <a
                                                        href={route('employees.documents.download', [employee.employee_id, doc.id])}
                                                        className="inline-flex items-center rounded-md border border-amber-200 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-amber-900 shadow-sm hover:bg-amber-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2"
                                                    >
                                                        Download
                                                    </a>
                                                ) : null}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <div className="mt-6">
                <Card className="p-6">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h3 className="text-base font-semibold text-slate-900">Probation Ending Soon</h3>
                            <p className="mt-1 text-sm text-slate-600">Top 5 employees with upcoming regularization dates.</p>
                        </div>
                        <Link
                            href={route('employees.probation', { days: 30 })}
                            className="text-sm font-semibold text-amber-800 hover:text-amber-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 rounded"
                        >
                            View all
                        </Link>
                    </div>

                    <div className="mt-4 overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Employee</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Department</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Regularization</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Days Remaining</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 bg-white">
                                {probationItems.length === 0 && (
                                    <tr>
                                        <td className="px-4 py-10" colSpan={5}>
                                            <div className="mx-auto max-w-xl rounded-2xl border border-amber-200/60 bg-amber-50/40 p-6">
                                                <div className="flex items-start gap-4">
                                                    <div className="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                                                        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M8 7V3m8 4V3" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M4 11h16" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 5h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 15l2 2 4-4" />
                                                        </svg>
                                                    </div>
                                                    <div className="min-w-0">
                                                        <div className="text-sm font-semibold text-slate-900">No probation ending soon</div>
                                                        <div className="mt-1 text-sm text-slate-600">You’re all set for the next 30 days.</div>
                                                        <div className="mt-4 flex flex-wrap items-center gap-3">
                                                            <Link
                                                                href={route('employees.index')}
                                                                className="inline-flex items-center rounded-md border border-amber-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-amber-900 shadow-sm hover:bg-amber-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2"
                                                            >
                                                                View Employees
                                                            </Link>
                                                            <Link
                                                                href={route('employees.probation', { days: 30 })}
                                                                className="inline-flex items-center rounded-md border border-amber-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-amber-900 shadow-sm hover:bg-amber-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2"
                                                            >
                                                                View Probation List
                                                            </Link>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                )}

                                {probationItems.map((emp) => {
                                    const tone = probationToneForDays(emp.days_remaining);
                                    const label = emp.full_name || emp.employee_code || 'Employee';

                                    return (
                                        <tr key={emp.employee_id} className="hover:bg-amber-50/40">
                                            <td className="px-4 py-3 text-sm font-medium text-slate-900">
                                                <Link
                                                    href={route('employees.show', emp.employee_id)}
                                                    className="text-amber-800 hover:text-amber-900"
                                                >
                                                    {label}
                                                </Link>
                                                <div className="mt-0.5 text-xs text-slate-500">{emp.employee_code}</div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{emp.department ?? '—'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{emp.regularization_date ?? '—'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">
                                                {typeof emp.days_remaining === 'number' ? <Badge tone={tone}>{emp.days_remaining} days</Badge> : '—'}
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm whitespace-nowrap">
                                                <Link
                                                    href={route('employees.show', emp.employee_id)}
                                                    className="inline-flex items-center rounded-md border border-amber-200 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-amber-900 shadow-sm hover:bg-amber-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2"
                                                >
                                                    View
                                                </Link>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                {can_approve_leaves && (
                    <Card className="p-6">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <h3 className="text-base font-semibold text-slate-900">Pending Leave Approvals</h3>
                                <p className="mt-1 text-sm text-slate-600">Requests waiting for your decision.</p>
                            </div>
                            <div className="flex items-center gap-3">
                                <div className="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                                    {pending_leave_approvals_count} pending
                                </div>
                                <Link
                                    href={route('leave.requests.index', { status: 'PENDING' })}
                                    className="text-sm font-semibold text-amber-800 hover:text-amber-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 rounded"
                                >
                                    View all
                                </Link>
                            </div>
                        </div>

                        <div className="mt-4 space-y-3">
                            {pendingLeaveItems.length === 0 && (
                                <div className="rounded-2xl border border-amber-200/60 bg-amber-50/40 p-4 text-sm text-slate-700">
                                    No pending leave requests.
                                </div>
                            )}

                            {pendingLeaveItems.map((item) => (
                                <Link
                                    key={item.id}
                                    href={route('leave.requests.show', item.id)}
                                    className="block rounded-2xl border border-slate-200 bg-white p-4 hover:bg-amber-50/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <div className="text-sm font-semibold text-slate-900 truncate">
                                                {fullName(item.employee) || item.employee?.employee_code || 'Employee'}
                                            </div>
                                            <div className="mt-1 text-xs text-slate-600">
                                                {item.leave_type?.name ?? 'Leave'} • {item.start_date} → {item.end_date}
                                            </div>
                                        </div>
                                        <Badge tone="amber">PENDING</Badge>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </Card>
                )}

                <Card className="p-6">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h3 className="text-base font-semibold text-slate-900">Upcoming Leaves</h3>
                            <p className="mt-1 text-sm text-slate-600">Approved leaves in the next 30 days.</p>
                        </div>
                        <Link
                            href={route('leave.requests.index', { status: 'APPROVED' })}
                            className="text-sm font-semibold text-amber-800 hover:text-amber-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 rounded"
                        >
                            View all
                        </Link>
                    </div>

                    <div className="mt-4 space-y-3">
                        {upcomingLeaveItems.length === 0 && (
                            <div className="rounded-2xl border border-amber-200/60 bg-amber-50/40 p-4 text-sm text-slate-700">
                                No upcoming approved leaves.
                            </div>
                        )}

                        {upcomingLeaveItems.map((item) => (
                            <Link
                                key={item.id}
                                href={route('leave.requests.show', item.id)}
                                className="block rounded-2xl border border-slate-200 bg-white p-4 hover:bg-amber-50/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <div className="text-sm font-semibold text-slate-900 truncate">
                                            {fullName(item.employee) || item.employee?.employee_code || 'Employee'}
                                        </div>
                                        <div className="mt-1 text-xs text-slate-600">
                                            {item.leave_type?.name ?? 'Leave'} • {item.start_date} → {item.end_date}
                                        </div>
                                    </div>
                                    <Badge tone="success">APPROVED</Badge>
                                </div>
                            </Link>
                        ))}
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

