import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import Badge from '@/Components/UI/Badge';
import PrimaryButton from '@/Components/PrimaryButton';
import InputError from '@/Components/InputError';
import DatePicker from '@/Components/DatePicker';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

function fullName(employee) {
    const parts = [employee?.first_name, employee?.middle_name, employee?.last_name, employee?.suffix]
        .map((v) => String(v || '').trim())
        .filter(Boolean);
    return parts.join(' ');
}

function minutesToHhMm(minutes) {
    if (typeof minutes !== 'number' || Number.isNaN(minutes)) return '—';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}

export default function Daily({ auth, date, rows = [], actions = {}, schedule = {} }) {
    const flash = usePage().props.flash;

    const canManage = Boolean(actions?.can_manage);
    const [selectedDate, setSelectedDate] = useState(date || '');
    const [savingEmployeeId, setSavingEmployeeId] = useState(null);
    const [errorsByEmployee, setErrorsByEmployee] = useState({});

    const initialDraft = useMemo(() => {
        const map = {};
        for (const row of rows || []) {
            const emp = row?.employee;
            const record = row?.record;
            if (!emp?.employee_id) continue;

            map[emp.employee_id] = {
                status: record?.status ?? '',
                time_in: record?.time_in ?? '',
                time_out: record?.time_out ?? '',
                remarks: record?.remarks ?? '',
            };
        }
        return map;
    }, [rows]);

    const [draftByEmployee, setDraftByEmployee] = useState(initialDraft);

    useEffect(() => {
        setSelectedDate(date || '');
        setDraftByEmployee(initialDraft);
        setErrorsByEmployee({});
        setSavingEmployeeId(null);
    }, [date, initialDraft]);

    const onDateChange = (next) => {
        setSelectedDate(next);
        router.get(route('attendance.daily'), { date: next || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const updateDraft = (employeeId, patch) => {
        setDraftByEmployee((prev) => ({
            ...prev,
            [employeeId]: {
                ...(prev[employeeId] || { status: '', time_in: '', time_out: '', remarks: '' }),
                ...patch,
            },
        }));
    };

    const submitRow = (employeeId) => {
        if (!canManage) return;

        const draft = draftByEmployee[employeeId] || { status: '', time_in: '', time_out: '', remarks: '' };
        setSavingEmployeeId(employeeId);
        setErrorsByEmployee((prev) => ({ ...prev, [employeeId]: {} }));

        router.put(
            route('attendance.daily.upsert', employeeId),
            {
                date: selectedDate,
                status: draft.status || null,
                time_in: draft.time_in || null,
                time_out: draft.time_out || null,
                remarks: draft.remarks || null,
            },
            {
                preserveScroll: true,
                onFinish: () => setSavingEmployeeId(null),
                onError: (errs) => {
                    setErrorsByEmployee((prev) => ({ ...prev, [employeeId]: errs }));
                },
            }
        );
    };

    const leaveLabel = (leave) => {
        if (!Array.isArray(leave) || leave.length === 0) return null;
        const first = leave[0];
        const code = first?.leave_type?.code || first?.leave_type?.name || 'Leave';
        const half = first?.is_half_day ? ` (${String(first?.half_day_part || '').toUpperCase() || 'Half-day'})` : '';
        return `${code}${half}`;
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Attendance (Daily)" contentClassName="max-w-none">
            <Head title="Attendance (Daily)" />

            <PageHeader
                title="Attendance (Daily)"
                subtitle={
                    schedule?.schedule_start && schedule?.schedule_end
                        ? `Schedule: ${schedule.schedule_start}–${schedule.schedule_end}`
                        : 'Manual daily log sheet.'
                }
            />

            <div className="w-full space-y-4">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}

                <Card className="p-6">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Date</div>
                            <div className="w-56">
                                <DatePicker value={selectedDate} onChange={onDateChange} disabled={!selectedDate && !canManage} />
                            </div>
                        </div>
                        {!canManage && (
                            <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-900">
                                You have view-only access.
                            </div>
                        )}
                    </div>

                    <div className="mt-5 overflow-x-auto">
                        <table className="min-w-[1100px] w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Employee</th>
                                    <th className="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Leave</th>
                                    <th className="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                                    <th className="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Time In</th>
                                    <th className="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Time Out</th>
                                    <th className="px-3 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Worked</th>
                                    <th className="px-3 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Late</th>
                                    <th className="px-3 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Undertime</th>
                                    <th className="px-3 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Overtime</th>
                                    <th className="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Remarks</th>
                                    <th className="px-3 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 bg-white">
                                {(rows || []).length === 0 && (
                                    <tr>
                                        <td className="px-3 py-10" colSpan={11}>
                                            <div className="mx-auto max-w-xl rounded-2xl border border-amber-200/60 bg-amber-50/40 p-6">
                                                <div className="text-sm font-semibold text-slate-900">No employees</div>
                                                <div className="mt-1 text-sm text-slate-600">There are no employees to display.</div>
                                            </div>
                                        </td>
                                    </tr>
                                )}

                                {(rows || []).map((row) => {
                                    const emp = row?.employee;
                                    const employeeId = emp?.employee_id;
                                    const draft = draftByEmployee?.[employeeId] || { status: '', time_in: '', time_out: '', remarks: '' };
                                    const leave = row?.leave;
                                    const leaveText = leaveLabel(leave);

                                    const metrics = row?.metrics || {};
                                    const worked = minutesToHhMm(metrics.worked_minutes);
                                    const late = typeof metrics.late_minutes === 'number' ? metrics.late_minutes : null;
                                    const undertime = typeof metrics.undertime_minutes === 'number' ? metrics.undertime_minutes : null;
                                    const overtime = typeof metrics.overtime_minutes === 'number' ? metrics.overtime_minutes : null;

                                    const disableTime = draft.status !== 'PRESENT';
                                    const rowErrors = errorsByEmployee?.[employeeId] || {};

                                    return (
                                        <tr key={employeeId} className="hover:bg-amber-50/30">
                                            <td className="px-3 py-3 text-sm font-medium text-slate-900 whitespace-nowrap">
                                                <div className="flex items-center gap-2">
                                                    <div>
                                                        <div className="font-semibold">{fullName(emp) || emp?.employee_code || 'Employee'}</div>
                                                        <div className="text-xs text-slate-500">{emp?.employee_code}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-3 py-3 text-sm text-slate-700">
                                                {leaveText ? <Badge tone="neutral">{leaveText}</Badge> : '—'}
                                            </td>
                                            <td className="px-3 py-3 text-sm text-slate-700">
                                                <select
                                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                                    value={draft.status}
                                                    disabled={!canManage}
                                                    onChange={(e) => {
                                                        const next = e.target.value;
                                                        updateDraft(employeeId, { status: next, ...(next !== 'PRESENT' ? { time_in: '', time_out: '' } : {}) });
                                                    }}
                                                >
                                                    <option value="">—</option>
                                                    <option value="PRESENT">PRESENT</option>
                                                    <option value="ABSENT">ABSENT</option>
                                                </select>
                                                <InputError message={rowErrors?.status} className="mt-1" />
                                            </td>
                                            <td className="px-3 py-3 text-sm text-slate-700">
                                                <input
                                                    type="time"
                                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                                    value={draft.time_in}
                                                    disabled={!canManage || disableTime}
                                                    onFocus={(e) => e.currentTarget?.showPicker?.()}
                                                    onClick={(e) => e.currentTarget?.showPicker?.()}
                                                    onChange={(e) => updateDraft(employeeId, { time_in: e.target.value })}
                                                />
                                                <InputError message={rowErrors?.time_in} className="mt-1" />
                                            </td>
                                            <td className="px-3 py-3 text-sm text-slate-700">
                                                <input
                                                    type="time"
                                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                                    value={draft.time_out}
                                                    disabled={!canManage || disableTime}
                                                    onFocus={(e) => e.currentTarget?.showPicker?.()}
                                                    onClick={(e) => e.currentTarget?.showPicker?.()}
                                                    onChange={(e) => updateDraft(employeeId, { time_out: e.target.value })}
                                                />
                                                <InputError message={rowErrors?.time_out} className="mt-1" />
                                            </td>
                                            <td className="px-3 py-3 text-sm text-right text-slate-700 tabular-nums">{worked}</td>
                                            <td className="px-3 py-3 text-sm text-right text-slate-700 tabular-nums">{late ?? '—'}</td>
                                            <td className="px-3 py-3 text-sm text-right text-slate-700 tabular-nums">{undertime ?? '—'}</td>
                                            <td className="px-3 py-3 text-sm text-right text-slate-700 tabular-nums">{overtime ?? '—'}</td>
                                            <td className="px-3 py-3 text-sm text-slate-700 min-w-[220px]">
                                                <input
                                                    type="text"
                                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                                    value={draft.remarks}
                                                    disabled={!canManage}
                                                    onChange={(e) => updateDraft(employeeId, { remarks: e.target.value })}
                                                    placeholder="Optional"
                                                />
                                                <InputError message={rowErrors?.remarks} className="mt-1" />
                                            </td>
                                            <td className="px-3 py-3 text-sm text-right whitespace-nowrap">
                                                <PrimaryButton
                                                    onClick={() => submitRow(employeeId)}
                                                    disabled={!canManage || savingEmployeeId === employeeId || !selectedDate}
                                                >
                                                    {savingEmployeeId === employeeId ? 'Updating…' : 'Update'}
                                                </PrimaryButton>
                                                {rowErrors?.date && <InputError message={rowErrors.date} className="mt-1" />}
                                            </td>
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
