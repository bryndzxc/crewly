import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import DatePicker from '@/Components/DatePicker';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function EmployeeDashboard({ auth, employee, leaveSummary, attendanceHistory, compensation, allowances, allowancesTotal, defaults }) {
    const [from, setFrom] = useState(defaults?.from || '');
    const [to, setTo] = useState(defaults?.to || '');

    const period = useMemo(() => {
        if (!from || !to) return null;
        return `${from}_${to}`;
    }, [from, to]);

    const payslipPreviewHref = useMemo(() => {
        if (!period) return null;
        return route('payroll.payslip.show', { employee: employee.employee_id, period });
    }, [employee?.employee_id, period]);

    const payslipDownloadHref = useMemo(() => {
        if (!period) return null;
        return route('payroll.payslip.show', { employee: employee.employee_id, period, download: 1 });
    }, [employee?.employee_id, period]);

    const attendanceItems = Array.isArray(attendanceHistory) ? attendanceHistory : [];
    const allowanceItems = Array.isArray(allowances) ? allowances : [];
    const recentLeave = Array.isArray(leaveSummary?.recent) ? leaveSummary.recent : [];

    return (
        <AuthenticatedLayout user={auth.user} header="Employee Dashboard" contentClassName="max-w-none">
            <Head title="Employee Dashboard" />

            <PageHeader title="Employee Dashboard" subtitle="Your leave, attendance, and payroll-ready information." />

            <div className="w-full space-y-4">
                <Card className="p-6">
                    <div className="text-sm font-semibold text-slate-900">Profile</div>
                    <div className="mt-1 text-sm text-slate-700">
                        <div className="font-medium text-slate-900">{employee?.name || '—'}</div>
                        <div className="text-slate-600">{employee?.position_title || '—'}</div>
                        <div className="text-slate-600">{employee?.department?.name || '—'}</div>
                    </div>
                </Card>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Card className="p-6">
                        <div className="text-sm font-semibold text-slate-900">Leave Summary</div>
                        <div className="mt-3 grid grid-cols-3 gap-3">
                            <div className="rounded-lg border border-slate-200 bg-white p-3">
                                <div className="text-xs font-semibold uppercase tracking-wider text-slate-600">Pending</div>
                                <div className="mt-1 text-lg font-semibold text-slate-900 tabular-nums">{leaveSummary?.pending ?? 0}</div>
                            </div>
                            <div className="rounded-lg border border-slate-200 bg-white p-3">
                                <div className="text-xs font-semibold uppercase tracking-wider text-slate-600">Approved</div>
                                <div className="mt-1 text-lg font-semibold text-slate-900 tabular-nums">{leaveSummary?.approved ?? 0}</div>
                            </div>
                            <div className="rounded-lg border border-slate-200 bg-white p-3">
                                <div className="text-xs font-semibold uppercase tracking-wider text-slate-600">Denied</div>
                                <div className="mt-1 text-lg font-semibold text-slate-900 tabular-nums">{leaveSummary?.denied ?? 0}</div>
                            </div>
                        </div>

                        <div className="mt-4">
                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-600">Recent Requests</div>
                            <div className="mt-2 space-y-2">
                                {recentLeave.length === 0 && <div className="text-sm text-slate-600">—</div>}
                                {recentLeave.map((r) => (
                                    <div key={r.id} className="flex items-center justify-between rounded-md border border-slate-200 bg-white px-3 py-2 text-sm">
                                        <div className="text-slate-900">
                                            {r.leave_type?.name || 'Leave'}: {r.start_date} to {r.end_date}
                                        </div>
                                        <div className="text-slate-600">{r.status}</div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="text-sm font-semibold text-slate-900">Attendance History</div>
                        <div className="mt-3 overflow-x-auto">
                            <table className="min-w-[520px] w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Date</th>
                                        <th className="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                                        <th className="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Time In</th>
                                        <th className="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Time Out</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200 bg-white">
                                    {attendanceItems.length === 0 && (
                                        <tr>
                                            <td className="px-3 py-6 text-sm text-slate-600" colSpan={4}>
                                                —
                                            </td>
                                        </tr>
                                    )}
                                    {attendanceItems.map((a) => (
                                        <tr key={a.id}>
                                            <td className="px-3 py-2 text-sm text-slate-900 whitespace-nowrap">{a.date || '—'}</td>
                                            <td className="px-3 py-2 text-sm text-slate-700 whitespace-nowrap">{a.status || '—'}</td>
                                            <td className="px-3 py-2 text-sm text-slate-700 whitespace-nowrap">{a.time_in || '—'}</td>
                                            <td className="px-3 py-2 text-sm text-slate-700 whitespace-nowrap">{a.time_out || '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Card className="p-6">
                        <div className="text-sm font-semibold text-slate-900">Compensation</div>
                        <div className="mt-3 text-sm text-slate-700">
                            <div className="flex items-center justify-between">
                                <div className="text-slate-600">Base Salary</div>
                                <div className="font-semibold text-slate-900 tabular-nums">{Number(compensation?.base_salary || 0).toFixed(2)}</div>
                            </div>
                            <div className="mt-2 flex items-center justify-between">
                                <div className="text-slate-600">Pay Frequency</div>
                                <div className="text-slate-900">{compensation?.pay_frequency || '—'}</div>
                            </div>
                            <div className="mt-2 flex items-center justify-between">
                                <div className="text-slate-600">Effective Date</div>
                                <div className="text-slate-900">{compensation?.effective_date || '—'}</div>
                            </div>
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="text-sm font-semibold text-slate-900">Allowances</div>
                        <div className="mt-2 text-sm text-slate-600">Total: {Number(allowancesTotal || 0).toFixed(2)}</div>
                        <div className="mt-3 overflow-x-auto">
                            <table className="min-w-[520px] w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Name</th>
                                        <th className="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Frequency</th>
                                        <th className="px-3 py-2 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Amount</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200 bg-white">
                                    {allowanceItems.length === 0 && (
                                        <tr>
                                            <td className="px-3 py-6 text-sm text-slate-600" colSpan={3}>
                                                —
                                            </td>
                                        </tr>
                                    )}
                                    {allowanceItems.map((a) => (
                                        <tr key={a.id}>
                                            <td className="px-3 py-2 text-sm text-slate-900 whitespace-nowrap">{a.allowance_name}</td>
                                            <td className="px-3 py-2 text-sm text-slate-700 whitespace-nowrap">{a.frequency}</td>
                                            <td className="px-3 py-2 text-sm text-right text-slate-700 tabular-nums">{Number(a.amount || 0).toFixed(2)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>

                <Card className="p-6">
                    <div className="text-sm font-semibold text-slate-900">Payslip Downloads</div>
                    <div className="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
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
                            <a
                                href={payslipPreviewHref || '#'}
                                target="_blank"
                                rel="noreferrer"
                                className={
                                    payslipPreviewHref
                                        ? 'inline-flex items-center rounded-md border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-900 shadow-sm hover:bg-slate-50'
                                        : 'pointer-events-none inline-flex items-center rounded-md border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-400'
                                }
                            >
                                Preview Payslip
                            </a>

                            <a href={payslipDownloadHref || '#'} className={payslipDownloadHref ? '' : 'pointer-events-none'}>
                                <PrimaryButton type="button" disabled={!payslipDownloadHref}>
                                    Download Payslip
                                </PrimaryButton>
                            </a>
                        </div>
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
