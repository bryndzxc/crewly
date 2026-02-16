import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import Checkbox from '@/Components/Checkbox';
import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo } from 'react';

export default function Create({ auth, leaveTypes = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        leave_type_id: '',
        start_date: '',
        end_date: '',
        is_half_day: false,
        half_day_part: '',
        reason: '',
    });

    const selectedLeaveType = useMemo(() => {
        const id = Number(data.leave_type_id);
        return (leaveTypes ?? []).find((t) => Number(t.id) === id) || null;
    }, [data.leave_type_id, leaveTypes]);

    const halfDayAllowed = Boolean(selectedLeaveType?.allow_half_day);

    const submit = (e) => {
        e.preventDefault();
        post(route('my.leave.store'));
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Request Leave">
            <Head title="Request Leave" />

            <PageHeader title="Request Leave" subtitle="Submit a leave request for approval." />

            <div className="w-full">
                <Card className="p-6 max-w-3xl mx-auto">
                    <form onSubmit={submit} className="space-y-5">
                        <div>
                            <InputLabel htmlFor="leave_type_id" value="Leave type" />
                            <select
                                id="leave_type_id"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                value={data.leave_type_id}
                                onChange={(e) => {
                                    const nextId = e.target.value;
                                    const nextType = (leaveTypes ?? []).find((t) => String(t.id) === String(nextId)) || null;
                                    const nextHalfDayAllowed = Boolean(nextType?.allow_half_day);

                                    setData((prev) => ({
                                        ...prev,
                                        leave_type_id: nextId,
                                        is_half_day: prev.is_half_day && !nextHalfDayAllowed ? false : prev.is_half_day,
                                        half_day_part: prev.is_half_day && !nextHalfDayAllowed ? '' : prev.half_day_part,
                                    }));
                                }}
                            >
                                <option value="">Select a leave type…</option>
                                {(leaveTypes ?? []).map((t) => (
                                    <option key={t.id} value={t.id}>
                                        {t.name} ({t.code})
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.leave_type_id} className="mt-2" />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="start_date" value="Start date" />
                                <input
                                    id="start_date"
                                    type="date"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                    value={data.start_date}
                                    onChange={(e) => setData('start_date', e.target.value)}
                                />
                                <InputError message={errors.start_date} className="mt-2" />
                            </div>
                            <div>
                                <InputLabel htmlFor="end_date" value="End date" />
                                <input
                                    id="end_date"
                                    type="date"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                    value={data.end_date}
                                    onChange={(e) => setData('end_date', e.target.value)}
                                />
                                <InputError message={errors.end_date} className="mt-2" />
                            </div>
                        </div>

                        <div className="rounded-xl border border-slate-200 p-4">
                            <div className="flex items-center justify-between gap-3">
                                <label className="flex items-center gap-2 text-sm text-slate-700">
                                    <Checkbox
                                        checked={Boolean(data.is_half_day)}
                                        disabled={!halfDayAllowed}
                                        onChange={(e) => setData('is_half_day', e.target.checked)}
                                    />
                                    Half-day
                                </label>
                                {!halfDayAllowed && <div className="text-xs text-slate-500">Not allowed for selected leave type.</div>}
                            </div>

                            {data.is_half_day && (
                                <div className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <InputLabel htmlFor="half_day_part" value="Half-day part" />
                                        <select
                                            id="half_day_part"
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                            value={data.half_day_part}
                                            onChange={(e) => setData('half_day_part', e.target.value)}
                                        >
                                            <option value="">Select…</option>
                                            <option value="AM">AM</option>
                                            <option value="PM">PM</option>
                                        </select>
                                        <InputError message={errors.half_day_part} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel value="Rule" />
                                        <div className="mt-2 text-sm text-slate-600">Half-day requires start date = end date.</div>
                                    </div>
                                </div>
                            )}
                        </div>

                        <div>
                            <InputLabel htmlFor="reason" value="Reason (optional)" />
                            <textarea
                                id="reason"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                rows={4}
                                value={data.reason}
                                onChange={(e) => setData('reason', e.target.value)}
                            />
                            <InputError message={errors.reason} className="mt-2" />
                        </div>

                        <div className="flex items-center justify-end gap-3">
                            <Link href={route('my.leave.index')} className="text-sm text-slate-600 hover:text-slate-900">
                                Cancel
                            </Link>
                            <PrimaryButton disabled={processing}>Submit</PrimaryButton>
                        </div>
                    </form>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
