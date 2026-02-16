import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import Badge from '@/Components/UI/Badge';
import MonthPicker from '@/Components/MonthPicker';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function codeTone(code) {
    if (code === 'P') return 'success';
    if (code === 'A') return 'danger';
    if (code === 'L') return 'neutral';
    return 'neutral';
}

export default function Monthly({ auth, month, days = [], cells = [], totals = {} }) {
    const [selectedMonth, setSelectedMonth] = useState(month || '');

    useEffect(() => {
        setSelectedMonth(month || '');
    }, [month]);

    const onMonthChange = (next) => {
        setSelectedMonth(next);
        router.get(route('my.attendance.monthly'), { month: next || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <AuthenticatedLayout user={auth.user} header="My Attendance (Monthly)">
            <Head title="My Attendance (Monthly)" />

            <PageHeader title="My Attendance (Monthly)" subtitle="P = Present, A = Absent, L = On leave." />

            <div className="w-full space-y-4">
                <Card className="p-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Month</div>
                            <div className="w-56">
                                <MonthPicker value={selectedMonth} onChange={onMonthChange} />
                            </div>
                        </div>

                        <div className="flex items-center gap-3 text-sm text-slate-700">
                            <div className="rounded-xl border border-slate-200 bg-white px-3 py-2">
                                <span className="font-semibold">P:</span> {totals.present ?? 0}
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-white px-3 py-2">
                                <span className="font-semibold">A:</span> {totals.absent ?? 0}
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-white px-3 py-2">
                                <span className="font-semibold">L:</span> {totals.leave ?? 0}
                            </div>
                        </div>
                    </div>

                    <div className="mt-5 overflow-x-auto">
                        <table className="min-w-[900px] w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    {(days || []).map((d) => (
                                        <th
                                            key={d.date}
                                            className="px-2 py-3 text-center text-[11px] font-semibold text-slate-600 uppercase tracking-wider whitespace-nowrap"
                                        >
                                            <div className="leading-none">{d.day}</div>
                                            <div className="mt-1 text-[10px] font-medium text-slate-500">{d.dow}</div>
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 bg-white">
                                <tr>
                                    {(cells || []).map((cell) => (
                                        <td key={cell.date} className="px-2 py-3 text-center text-xs text-slate-700">
                                            {cell.code === '—' ? '—' : <Badge tone={codeTone(cell.code)}>{cell.code}</Badge>}
                                        </td>
                                    ))}
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
