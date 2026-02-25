import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/UI/PageHeader';
import Table from '@/Components/Table';
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';
import Badge from '@/Components/UI/Badge';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

const badgeToneForDays = (days) => {
    if (typeof days !== 'number') return 'neutral';
    if (days <= 7) return 'danger';
    if (days <= 30) return 'amber';
    return 'neutral';
};

const initialsFromName = (name) => {
    const safe = String(name || '').trim();
    if (!safe) return 'E';
    const parts = safe.split(/\s+/).filter(Boolean);
    const first = parts[0]?.charAt(0) ?? 'E';
    const last = parts.length > 1 ? parts[parts.length - 1].charAt(0) : '';
    return (first + last).toUpperCase();
};

export default function Probation({ auth, employees, filters = {} }) {
    const [days, setDays] = useState(Number(filters.days ?? 30));
    const [search, setSearch] = useState(filters.search ?? '');
    const [isLoading, setIsLoading] = useState(false);

    const items = employees?.data ?? [];
    const canEdit = ['admin', 'hr'].includes(String(auth?.user?.role ?? '').toLowerCase());

    const queryParams = useMemo(
        () => ({
            days,
            search: String(search || '').trim() === '' ? undefined : search,
        }),
        [days, search]
    );

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
            if (pathname.startsWith('/employees/probation')) setIsLoading(true);
        });

        const unsubscribeFinish = router.on('finish', (event) => {
            const visit = event?.detail?.visit;
            const pathname = parsePathname(visit?.url);
            if (pathname.startsWith('/employees/probation')) setIsLoading(false);
        });

        return () => {
            if (typeof unsubscribeStart === 'function') unsubscribeStart();
            if (typeof unsubscribeFinish === 'function') unsubscribeFinish();
        };
    }, []);

    useEffect(() => {
        const currentDays = Number(filters.days ?? 30);
        const currentSearch = String(filters.search ?? '');

        if (Number(days) === currentDays && String(search ?? '') === currentSearch) return;

        const handler = setTimeout(() => {
            router.get(route('employees.probation'), { ...queryParams, page: 1 }, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 300);

        return () => clearTimeout(handler);
    }, [queryParams]);

    const emptyState = useMemo(() => {
        if (items.length === 0 && String(search || '').trim() !== '') return 'No employees match your search.';
        if (items.length === 0) return `No probation ending in the next ${days} days.`;
        return null;
    }, [items.length, search, days]);

    return (
        <AuthenticatedLayout user={auth.user} header="Probation Ending Soon" contentClassName="max-w-none">
            <Head title="Probation Ending Soon" />

            <PageHeader title="Probation Ending Soon" subtitle="Track upcoming regularization dates." />

            <div className="w-full space-y-4">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex items-center gap-2">
                        <label className="text-sm font-medium text-slate-700" htmlFor="days">
                            Days
                        </label>
                        <select
                            id="days"
                            value={days}
                            onChange={(e) => setDays(Number(e.target.value))}
                            className="rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm"
                        >
                            <option value={30}>30</option>
                            <option value={60}>60</option>
                            <option value={90}>90</option>
                        </select>
                    </div>

                    <TextInput
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search by employee name/code or department…"
                        className="w-full lg:w-96"
                        aria-label="Search employees"
                    />
                </div>

                <Table
                    loading={isLoading}
                    loadingText="Loading employees…"
                    columns={[
                        { key: 'employee', label: 'Employee' },
                        { key: 'department', label: 'Department' },
                        { key: 'regularization_date', label: 'Regularization Date' },
                        { key: 'days_remaining', label: 'Days Remaining' },
                        { key: 'actions', label: 'Actions', align: 'right' },
                    ]}
                    items={items}
                    rowKey={(emp) => emp.employee_id}
                    emptyState={emptyState}
                    pagination={{
                        meta: employees?.meta ?? employees,
                        links: employees?.links ?? employees?.meta?.links ?? [],
                        perPage: null,
                        onPerPageChange: undefined,
                    }}
                    renderRow={(emp) => {
                        const tone = badgeToneForDays(emp.days_remaining);
                        const label = emp.full_name || emp.employee_code || 'Employee';

                        return (
                            <tr className="hover:bg-amber-50/40">
                                <td className="px-4 py-3 text-sm font-medium text-slate-900">
                                    <div className="flex items-center gap-3">
                                        <div className="h-10 w-10 rounded-full border border-gray-200 bg-gray-50 flex items-center justify-center text-xs font-semibold text-gray-600">
                                            {initialsFromName(emp.full_name)}
                                        </div>
                                        <div className="min-w-0">
                                            <div className="truncate">{label}</div>
                                            <div className="mt-0.5 text-xs text-slate-500">{emp.employee_code}</div>
                                        </div>
                                    </div>
                                </td>
                                <td className="px-4 py-3 text-sm text-slate-700">{emp.department ?? '—'}</td>
                                <td className="px-4 py-3 text-sm text-slate-700">{emp.regularization_date ?? '—'}</td>
                                <td className="px-4 py-3 text-sm text-slate-700">
                                    {typeof emp.days_remaining === 'number' ? (
                                        <Badge tone={tone}>{emp.days_remaining} days</Badge>
                                    ) : (
                                        '—'
                                    )}
                                </td>
                                <td className="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    <div className="flex items-center justify-end gap-3">
                                        <Link href={route('employees.show', emp.employee_id)} className="shrink-0">
                                            <SecondaryButton type="button">View</SecondaryButton>
                                        </Link>

                                        {canEdit ? (
                                            <Link href={route('employees.edit', emp.employee_id)} className="shrink-0">
                                                <SecondaryButton type="button">Edit</SecondaryButton>
                                            </Link>
                                        ) : null}
                                    </div>
                                </td>
                            </tr>
                        );
                    }}
                />
            </div>
        </AuthenticatedLayout>
    );
}
