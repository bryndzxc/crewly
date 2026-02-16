import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import Badge from '@/Components/UI/Badge';
import DatePicker from '@/Components/DatePicker';
import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

function minutesToHhMm(minutes) {
    if (typeof minutes !== 'number' || Number.isNaN(minutes)) return '—';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}

function statusTone(status) {
    const s = String(status || '').toUpperCase();
    if (s === 'PRESENT') return 'success';
    if (s === 'ABSENT') return 'danger';
    return 'neutral';
}

export default function Daily({ auth, date, record, metrics = {}, schedule = {} }) {
    const [selectedDate, setSelectedDate] = useState(date || '');

    useEffect(() => {
        setSelectedDate(date || '');
    }, [date]);

    const onDateChange = (next) => {
        setSelectedDate(next);
        router.get(route('my.attendance.daily'), { date: next || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const worked = useMemo(() => minutesToHhMm(metrics.worked_minutes), [metrics.worked_minutes]);

    return (
        <AuthenticatedLayout user={auth.user} header="My Attendance (Daily)">
            <Head title="My Attendance (Daily)" />

            <PageHeader
                title="My Attendance (Daily)"
                subtitle={
                    schedule?.schedule_start && schedule?.schedule_end
                        ? `Schedule: ${schedule.schedule_start}–${schedule.schedule_end}`
                        : 'Daily attendance record.'
                }
            />

            <div className="w-full space-y-4">
                <Card className="p-6">
                    <div>
                        <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Date</div>
                        <div className="w-56">
                            <DatePicker value={selectedDate} onChange={onDateChange} />
                        </div>
                    </div>

                    {!record ? (
                        <div className="mt-6 rounded-2xl border border-amber-200/60 bg-amber-50/40 p-6">
                            <div className="text-sm font-semibold text-slate-900">No record</div>
                            <div className="mt-1 text-sm text-slate-600">There is no attendance record for this date.</div>
                        </div>
                    ) : (
                        <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</div>
                                <div className="mt-2">
                                    <Badge tone={statusTone(record.status)}>{record.status || '—'}</Badge>
                                </div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Time In</div>
                                <div className="mt-1 text-sm font-medium text-slate-900 tabular-nums">{record.time_in || '—'}</div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Time Out</div>
                                <div className="mt-1 text-sm font-medium text-slate-900 tabular-nums">{record.time_out || '—'}</div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Worked</div>
                                <div className="mt-1 text-sm font-medium text-slate-900 tabular-nums">{worked}</div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Late (min)</div>
                                <div className="mt-1 text-sm font-medium text-slate-900 tabular-nums">{typeof metrics.late_minutes === 'number' ? metrics.late_minutes : '—'}</div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Undertime (min)</div>
                                <div className="mt-1 text-sm font-medium text-slate-900 tabular-nums">{typeof metrics.undertime_minutes === 'number' ? metrics.undertime_minutes : '—'}</div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Overtime (min)</div>
                                <div className="mt-1 text-sm font-medium text-slate-900 tabular-nums">{typeof metrics.overtime_minutes === 'number' ? metrics.overtime_minutes : '—'}</div>
                            </div>
                            <div className="sm:col-span-2 lg:col-span-3">
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Remarks</div>
                                <div className="mt-1 text-sm text-slate-700">{record.remarks || '—'}</div>
                            </div>
                        </div>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
