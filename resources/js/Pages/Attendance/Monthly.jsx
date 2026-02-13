import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import Badge from '@/Components/UI/Badge';
import MonthPicker from '@/Components/MonthPicker';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function fullName(employee) {
    const parts = [employee?.first_name, employee?.middle_name, employee?.last_name, employee?.suffix]
        .map((v) => String(v || '').trim())
        .filter(Boolean);
    return parts.join(' ');
}

function codeTone(code) {
    if (code === 'P') return 'success';
    if (code === 'A') return 'danger';
    if (code === 'L') return 'neutral';
    return 'neutral';
}

export default function Monthly({ auth, month, days = [], rows = [] }) {
    const [selectedMonth, setSelectedMonth] = useState(month || '');

    useEffect(() => {
        setSelectedMonth(month || '');
    }, [month]);

    const onMonthChange = (next) => {
        setSelectedMonth(next);
        router.get(route('attendance.monthly'), { month: next || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Attendance (Monthly)" contentClassName="max-w-none">
            <Head title="Attendance (Monthly)" />

            <PageHeader title="Attendance (Monthly)" subtitle="Monthly log sheet (P = Present, A = Absent, L = On leave)." />

            <div className="w-full space-y-4">
                <Card className="p-6">
                    <div>
                        <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Month</div>
                        <div className="w-56">
                            <MonthPicker value={selectedMonth} onChange={onMonthChange} />
                        </div>
                    </div>

                    <div className="mt-5 overflow-x-auto">
                        <table className="min-w-[1100px] w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Employee</th>
                                    <th className="px-3 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">P</th>
                                    <th className="px-3 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">A</th>
                                    <th className="px-3 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">L</th>
                                    {(days || []).map((d) => (
                                        <th key={d.date} className="px-2 py-3 text-center text-[11px] font-semibold text-slate-600 uppercase tracking-wider whitespace-nowrap">
                                            <div className="leading-none">{d.day}</div>
                                            <div className="mt-1 text-[10px] font-medium text-slate-500">{d.dow}</div>
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 bg-white">
                                {(rows || []).length === 0 && (
                                    <tr>
                                        <td className="px-3 py-10" colSpan={4 + (days || []).length}>
                                            <div className="mx-auto max-w-xl rounded-2xl border border-amber-200/60 bg-amber-50/40 p-6">
                                                <div className="text-sm font-semibold text-slate-900">No employees</div>
                                                <div className="mt-1 text-sm text-slate-600">There are no employees to display.</div>
                                            </div>
                                        </td>
                                    </tr>
                                )}

                                {(rows || []).map((row) => {
                                    const emp = row?.employee;
                                    const totals = row?.totals || {};
                                    return (
                                        <tr key={emp?.employee_id} className="hover:bg-amber-50/30">
                                            <td className="px-3 py-3 text-sm font-medium text-slate-900 whitespace-nowrap">
                                                <div className="font-semibold">{fullName(emp) || emp?.employee_code || 'Employee'}</div>
                                                <div className="text-xs text-slate-500">{emp?.employee_code}</div>
                                            </td>
                                            <td className="px-3 py-3 text-sm text-right text-slate-700 tabular-nums">{totals.present ?? 0}</td>
                                            <td className="px-3 py-3 text-sm text-right text-slate-700 tabular-nums">{totals.absent ?? 0}</td>
                                            <td className="px-3 py-3 text-sm text-right text-slate-700 tabular-nums">{totals.leave ?? 0}</td>
                                            {(row?.days || []).map((cell) => (
                                                <td key={cell.date} className="px-2 py-3 text-center text-xs text-slate-700">
                                                    {cell.code === '—' ? '—' : <Badge tone={codeTone(cell.code)}>{cell.code}</Badge>}
                                                </td>
                                            ))}
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
