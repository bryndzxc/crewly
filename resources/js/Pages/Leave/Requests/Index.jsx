import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import Table from '@/Components/Table';
import Badge from '@/Components/UI/Badge';
import Modal from '@/Components/Modal';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import DangerButton from '@/Components/DangerButton';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

function fullName(employee) {
    const parts = [employee?.first_name, employee?.middle_name, employee?.last_name, employee?.suffix]
        .map((v) => String(v || '').trim())
        .filter(Boolean);
    return parts.join(' ');
}

function statusTone(status) {
    if (status === 'PENDING') return 'amber';
    if (status === 'APPROVED') return 'success';
    if (status === 'DENIED') return 'danger';
    return 'neutral';
}

export default function Index({ auth, requests, leaveTypes = [], employees = [], filters = {}, actions = {} }) {
    const [query, setQuery] = useState(filters.q ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [leaveTypeId, setLeaveTypeId] = useState(filters.leave_type_id ?? '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');
    const [perPage, setPerPage] = useState(filters.per_page ?? 10);
    const [isLoading, setIsLoading] = useState(false);
    const [createOpen, setCreateOpen] = useState(false);
    const flash = usePage().props.flash;

    const requestItems = requests?.data ?? [];

    useEffect(() => {
        const parsePathname = (url) => {
            try {
                return new URL(url, window.location.origin).pathname;
            } catch {
                return String(url || '');
            }
        };

        const unsubscribeStart = router.on('start', (event) => {
            const visit = event?.detail?.visit;
            const pathname = parsePathname(visit?.url);
            if (pathname.startsWith('/leave/requests')) setIsLoading(true);
        });

        const unsubscribeFinish = router.on('finish', (event) => {
            const visit = event?.detail?.visit;
            const pathname = parsePathname(visit?.url);
            if (pathname.startsWith('/leave/requests')) setIsLoading(false);
        });

        return () => {
            if (typeof unsubscribeStart === 'function') unsubscribeStart();
            if (typeof unsubscribeFinish === 'function') unsubscribeFinish();
        };
    }, []);

    useEffect(() => {
        const handler = setTimeout(() => {
            router.get(
                route('leave.requests.index'),
                {
                    q: query,
                    status: status || undefined,
                    leave_type_id: leaveTypeId || undefined,
                    date_from: dateFrom || undefined,
                    date_to: dateTo || undefined,
                    per_page: perPage,
                    page: 1,
                },
                { preserveState: true, preserveScroll: true, replace: true }
            );
        }, 250);

        return () => clearTimeout(handler);
    }, [query, status, leaveTypeId, dateFrom, dateTo, perPage]);

    const onPerPageChange = (nextPerPage) => {
        setPerPage(nextPerPage);
        router.get(
            route('leave.requests.index'),
            {
                q: query,
                status: status || undefined,
                leave_type_id: leaveTypeId || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
                per_page: nextPerPage,
                page: 1,
            },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const emptyState = useMemo(() => {
        if (requestItems.length === 0 && (query ?? '') !== '') return 'No leave requests match your search.';
        if (requestItems.length === 0) return 'No leave requests yet.';
        return null;
    }, [requestItems.length, query]);

    const createForm = useForm({
        employee_id: '',
        leave_type_id: '',
        start_date: '',
        end_date: '',
        is_half_day: false,
        half_day_part: '',
        reason: '',
    });

    const selectedLeaveType = useMemo(() => {
        const id = Number(createForm.data.leave_type_id);
        return (leaveTypes ?? []).find((t) => Number(t.id) === id) || null;
    }, [createForm.data.leave_type_id, leaveTypes]);

    const halfDayAllowed = Boolean(selectedLeaveType?.allow_half_day);

    const submitCreate = (e) => {
        e.preventDefault();
        createForm.post(route('leave.requests.store'), {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                setCreateOpen(true);
            },
            onSuccess: () => {
                setCreateOpen(false);
                createForm.reset();
            },
        });
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Leave Requests" contentClassName="max-w-none">
            <Head title="Leave Requests" />

            <div className="w-full space-y-4">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        {flash.error}
                    </div>
                )}

                <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:gap-4 w-full">
                        <div className="lg:col-span-2">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Employee</div>
                            <TextInput
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Search employee code or name…"
                                className="mt-1 block w-full"
                            />
                        </div>

                        <div>
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</div>
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                            >
                                <option value="">All</option>
                                <option value="PENDING">Pending</option>
                                <option value="APPROVED">Approved</option>
                                <option value="DENIED">Denied</option>
                                <option value="CANCELLED">Cancelled</option>
                            </select>
                        </div>

                        <div>
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Type</div>
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                value={leaveTypeId}
                                onChange={(e) => setLeaveTypeId(e.target.value)}
                            >
                                <option value="">All</option>
                                {(leaveTypes ?? []).map((t) => (
                                    <option key={t.id} value={t.id}>
                                        {t.name} ({t.code})
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">From</div>
                            <input
                                type="date"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                value={dateFrom}
                                onChange={(e) => setDateFrom(e.target.value)}
                            />
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">To</div>
                            <input
                                type="date"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                value={dateTo}
                                onChange={(e) => setDateTo(e.target.value)}
                            />
                        </div>
                    </div>

                    {actions?.create && (
                        <div className="shrink-0">
                            <PrimaryButton type="button" onClick={() => setCreateOpen(true)}>
                                Create Request
                            </PrimaryButton>
                        </div>
                    )}
                </div>

                <Table
                    loading={isLoading}
                    loadingText="Loading leave requests…"
                    columns={[
                        { key: 'employee', label: 'Employee' },
                        { key: 'type', label: 'Type' },
                        { key: 'dates', label: 'Dates' },
                        { key: 'days', label: 'Days' },
                        { key: 'status', label: 'Status' },
                        { key: 'action', label: 'Action', align: 'right' },
                    ]}
                    items={requestItems}
                    rowKey={(r) => r.id}
                    emptyState={emptyState}
                    pagination={{
                        meta: requests?.meta ?? requests,
                        links: requests?.links ?? requests?.meta?.links ?? [],
                        perPage,
                        onPerPageChange,
                    }}
                    renderRow={(r) => (
                        <tr className="hover:bg-amber-50/30">
                            <td className="px-4 py-3 text-sm">
                                <div className="font-medium text-slate-900">{fullName(r.employee) || r.employee?.employee_code || 'Employee'}</div>
                                <div className="text-xs text-slate-500">{r.employee?.employee_code ?? '—'}</div>
                            </td>
                            <td className="px-4 py-3 text-sm text-slate-700">
                                {r.leave_type?.name ?? r.leaveType?.name ?? '—'}
                            </td>
                            <td className="px-4 py-3 text-sm text-slate-700">
                                {r.start_date} → {r.end_date}
                                {r.is_half_day ? <div className="text-xs text-slate-500">Half-day ({r.half_day_part || '—'})</div> : null}
                            </td>
                            <td className="px-4 py-3 text-sm text-slate-700">{r.total_days}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">
                                <Badge tone={statusTone(r.status)}>{r.status}</Badge>
                            </td>
                            <td className="px-4 py-3 text-right text-sm">
                                <Link href={route('leave.requests.show', r.id)} className="shrink-0">
                                    <SecondaryButton type="button">View</SecondaryButton>
                                </Link>
                            </td>
                        </tr>
                    )}
                />
            </div>

            <Modal show={createOpen} onClose={() => setCreateOpen(false)} maxWidth="2xl">
                <form onSubmit={submitCreate} className="p-6 space-y-5">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900">Create leave request</h2>
                            <p className="mt-1 text-sm text-slate-600">Fill out the details below.</p>
                        </div>
                        <DangerButton type="button" onClick={() => setCreateOpen(false)}>
                            Close
                        </DangerButton>
                    </div>

                    <div>
                        <InputLabel htmlFor="employee_id" value="Employee" />
                        <select
                            id="employee_id"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                            value={createForm.data.employee_id}
                            onChange={(e) => createForm.setData('employee_id', e.target.value)}
                        >
                            <option value="">Select an employee…</option>
                            {(employees ?? []).map((emp) => (
                                <option key={emp.employee_id} value={emp.employee_id}>
                                    {emp.employee_code} — {fullName(emp)}
                                </option>
                            ))}
                        </select>
                        <InputError message={createForm.errors.employee_id} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="leave_type_id" value="Leave type" />
                        <select
                            id="leave_type_id"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                            value={createForm.data.leave_type_id}
                            onChange={(e) => {
                                const nextId = e.target.value;
                                const nextType = (leaveTypes ?? []).find((t) => String(t.id) === String(nextId)) || null;
                                const nextHalfDayAllowed = Boolean(nextType?.allow_half_day);
                                createForm.setData((prev) => ({
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
                        <InputError message={createForm.errors.leave_type_id} className="mt-2" />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="start_date" value="Start date" />
                            <input
                                id="start_date"
                                type="date"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                value={createForm.data.start_date}
                                onChange={(e) => createForm.setData('start_date', e.target.value)}
                            />
                            <InputError message={createForm.errors.start_date} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="end_date" value="End date" />
                            <input
                                id="end_date"
                                type="date"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                value={createForm.data.end_date}
                                onChange={(e) => createForm.setData('end_date', e.target.value)}
                            />
                            <InputError message={createForm.errors.end_date} className="mt-2" />
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 p-4">
                        <div className="flex items-center justify-between gap-3">
                            <label className="flex items-center gap-2 text-sm text-slate-700">
                                <Checkbox
                                    checked={Boolean(createForm.data.is_half_day)}
                                    disabled={!halfDayAllowed}
                                    onChange={(e) => createForm.setData('is_half_day', e.target.checked)}
                                />
                                Half-day
                            </label>
                            {!halfDayAllowed && (
                                <div className="text-xs text-slate-500">Not allowed for selected leave type.</div>
                            )}
                        </div>

                        {createForm.data.is_half_day && (
                            <div className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <InputLabel htmlFor="half_day_part" value="Half-day part" />
                                    <select
                                        id="half_day_part"
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                        value={createForm.data.half_day_part}
                                        onChange={(e) => createForm.setData('half_day_part', e.target.value)}
                                    >
                                        <option value="">Select…</option>
                                        <option value="AM">AM</option>
                                        <option value="PM">PM</option>
                                    </select>
                                    <InputError message={createForm.errors.half_day_part} className="mt-2" />
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
                            value={createForm.data.reason}
                            onChange={(e) => createForm.setData('reason', e.target.value)}
                        />
                        <InputError message={createForm.errors.reason} className="mt-2" />
                    </div>

                    <div className="flex items-center justify-end gap-3">
                        <SecondaryButton type="button" onClick={() => setCreateOpen(false)} disabled={createForm.processing}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton disabled={createForm.processing}>Create</PrimaryButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
