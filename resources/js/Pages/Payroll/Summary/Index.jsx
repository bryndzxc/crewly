import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import StatCard from '@/Components/UI/StatCard';
import PrimaryButton from '@/Components/PrimaryButton';
import DatePicker from '@/Components/DatePicker';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function PayrollSummaryIndex({
    auth,
    filters = {},
    rows = [],
    totals = {},
    meta = {},
    actions = {},
}) {
    const flash = usePage().props.flash;

    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');
    const canExport = Boolean(actions?.can_export);

    useEffect(() => {
        setFrom(filters.from || '');
        setTo(filters.to || '');
    }, [filters.from, filters.to]);

    const onGenerate = () => {
        router.get(
            route('payroll.summary.index'),
            { from: from || undefined, to: to || undefined },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const exportHref = useMemo(() => {
        if (!canExport) return null;
        if (!from || !to) return null;
        return route('payroll.summary.export', { from, to, format: 'csv' });
    }, [canExport, from, to]);

    const items = Array.isArray(rows) ? rows : [];

    const sum = (key) => items.reduce((acc, r) => acc + Number(r?.[key] || 0), 0);
    const cardTotals = {
        employees: Number(totals?.employees ?? items.length ?? 0),
        present_days: Number(totals?.present_days ?? sum('present_days')),
        absent_days: Number(totals?.absent_days ?? sum('absent_days')),
        late_minutes: Number(totals?.late_minutes ?? sum('late_minutes')),
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Payroll Summary" contentClassName="max-w-none">
            <Head title="Payroll Summary" />

            <PageHeader title="Payroll Summary" subtitle="Payroll input report (attendance + leave totals)." />

            <div className="w-full space-y-4">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}

                <Card className="relative p-6">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Date From</div>
                                <DatePicker value={from} onChange={setFrom} />
                            </div>
                            <div>
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Date To</div>
                                <DatePicker value={to} onChange={setTo} />
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <PrimaryButton onClick={onGenerate} disabled={!from || !to}>
                                Generate
                            </PrimaryButton>

                            {canExport && exportHref && (
                                <a
                                    href={exportHref}
                                    className="inline-flex items-center rounded-md border border-amber-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-amber-900 shadow-sm hover:bg-amber-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2"
                                >
                                    Export CSV
                                </a>
                            )}
                        </div>
                    </div>

                    <div className="mt-4 text-xs text-slate-500">
                        Generated: {meta?.generated_at ?? '—'} | Period: {meta?.from ?? from} to {meta?.to ?? to}
                    </div>
                </Card>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard title="Employees" value={cardTotals.employees} caption="Included in report" />
                    <StatCard title="Present Days" value={cardTotals.present_days} caption="Sum across employees" />
                    <StatCard title="Absent Days" value={cardTotals.absent_days} caption="Sum across employees" />
                    <StatCard title="Late Minutes" value={cardTotals.late_minutes} caption="Sum across employees" />
                </div>

                <Card className="p-6">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h3 className="text-base font-semibold text-slate-900">Report</h3>
                            <p className="mt-1 text-sm text-slate-600">Per-employee totals for the selected period.</p>
                        </div>
                    </div>

                    <div className="mt-4 overflow-x-auto">
                        <table className="min-w-[1100px] w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50 sticky top-0">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Code</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Employee</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Department</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Present</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Absent</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">On Leave</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Worked Hours</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Late</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Undertime</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Overtime</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 bg-white">
                                {items.length === 0 && (
                                    <tr>
                                        <td className="px-4 py-10" colSpan={10}>
                                            <div className="mx-auto max-w-xl rounded-2xl border border-amber-200/60 bg-amber-50/40 p-6">
                                                <div className="text-sm font-semibold text-slate-900">No data</div>
                                                <div className="mt-1 text-sm text-slate-600">Generate a report to see results.</div>
                                            </div>
                                        </td>
                                    </tr>
                                )}

                                {items.map((r) => (
                                    <tr key={r.employee_id} className="hover:bg-amber-50/30">
                                        <td className="px-4 py-3 text-sm font-medium text-slate-900 whitespace-nowrap">{r.employee_code}</td>
                                        <td className="px-4 py-3 text-sm text-slate-900 whitespace-nowrap">{r.employee_name}</td>
                                        <td className="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">{r.department || '—'}</td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums">{r.present_days}</td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums">{r.absent_days}</td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums">{r.on_leave_days}</td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums">{Number(r.worked_hours || 0).toFixed(2)}</td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums">{r.late_minutes}</td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums">{r.undertime_minutes}</td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums">{r.overtime_minutes}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
