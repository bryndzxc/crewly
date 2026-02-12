import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/UI/PageHeader';
import Table from '@/Components/Table';
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';
import ExpiryStatusBadge from '@/Components/UI/ExpiryStatusBadge';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

function fullName(employee) {
    const parts = [employee?.first_name, employee?.middle_name, employee?.last_name, employee?.suffix]
        .map((v) => String(v || '').trim())
        .filter(Boolean);

    return parts.join(' ');
}

export default function Index({ auth, documents, filters = {} }) {
    const [days, setDays] = useState(Number(filters.days ?? 30));
    const [expiredOnly, setExpiredOnly] = useState(Boolean(Number(filters.expired ?? 0)));
    const [search, setSearch] = useState(filters.search ?? '');
    const [isLoading, setIsLoading] = useState(false);

    const items = documents?.data ?? [];

    const queryParams = useMemo(
        () => ({
            days,
            expired: expiredOnly ? 1 : 0,
            search: String(search || '').trim() === '' ? undefined : search,
            page: 1,
        }),
        [days, expiredOnly, search]
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
            if (pathname.startsWith('/documents/expiring')) setIsLoading(true);
        });

        const unsubscribeFinish = router.on('finish', (event) => {
            const visit = event?.detail?.visit;
            const pathname = parsePathname(visit?.url);
            if (pathname.startsWith('/documents/expiring')) setIsLoading(false);
        });

        return () => {
            if (typeof unsubscribeStart === 'function') unsubscribeStart();
            if (typeof unsubscribeFinish === 'function') unsubscribeFinish();
        };
    }, []);

    useEffect(() => {
        const handler = setTimeout(() => {
            router.get(route('documents.expiring'), queryParams, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 300);

        return () => clearTimeout(handler);
    }, [queryParams]);

    const onDaysChange = (nextDays) => {
        setDays(Number(nextDays));
    };

    const onToggleExpired = () => {
        setExpiredOnly((v) => !v);
    };

    const emptyState = useMemo(() => {
        if (items.length === 0 && String(search || '').trim() !== '') return 'No documents match your search.';
        if (items.length === 0 && expiredOnly) return 'No expired documents.';
        if (items.length === 0) return 'No expiring documents.';
        return null;
    }, [items.length, search, expiredOnly]);

    return (
        <AuthenticatedLayout user={auth.user} header="Expiring Documents" contentClassName="max-w-none">
            <Head title="Expiring Documents" />

            <PageHeader title="Expiring Documents" subtitle="Track expiring and expired employee documents." />

            <div className="w-full space-y-4">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div className="flex items-center gap-2">
                            <label className="text-sm font-medium text-slate-700" htmlFor="days">
                                Days
                            </label>
                            <select
                                id="days"
                                value={days}
                                onChange={(e) => onDaysChange(e.target.value)}
                                className="rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm"
                            >
                                <option value={30}>30</option>
                                <option value={60}>60</option>
                                <option value={90}>90</option>
                            </select>
                        </div>

                        <button
                            type="button"
                            onClick={onToggleExpired}
                            className={
                                'inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium transition ' +
                                (expiredOnly
                                    ? 'border-amber-300 bg-amber-50 text-amber-900'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50')
                            }
                        >
                            {expiredOnly ? 'Showing expired only' : 'Show expired only'}
                        </button>
                    </div>

                    <TextInput
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search by employee name/code or doc type…"
                        className="w-full lg:w-96"
                        aria-label="Search documents"
                    />
                </div>

                <Table
                    loading={isLoading}
                    loadingText="Loading documents…"
                    columns={[
                        { key: 'employee', label: 'Employee' },
                        { key: 'type', label: 'Document Type' },
                        { key: 'expiry_date', label: 'Expiry Date' },
                        { key: 'status', label: 'Status' },
                        { key: 'days_to_expiry', label: 'Days to Expiry' },
                        { key: 'actions', label: 'Actions', align: 'right' },
                    ]}
                    items={items}
                    rowKey={(doc) => doc.id}
                    emptyState={emptyState}
                    pagination={{
                        meta: documents?.meta ?? documents,
                        links: documents?.links ?? documents?.meta?.links ?? [],
                        perPage: null,
                        onPerPageChange: undefined,
                    }}
                    renderRow={(doc) => {
                        const employee = doc.employee;
                        const name = employee ? fullName(employee) : '';
                        const label = employee ? (name || employee.employee_code) : '—';

                        return (
                            <tr className="hover:bg-amber-50/40">
                                <td className="px-4 py-3 text-sm font-medium text-slate-900">
                                    {employee?.employee_id ? (
                                        <div className="min-w-0">
                                            <div className="truncate">{label}</div>
                                            <div className="mt-0.5 text-xs text-slate-500">{employee.employee_code}</div>
                                        </div>
                                    ) : (
                                        '—'
                                    )}
                                </td>
                                <td className="px-4 py-3 text-sm text-slate-700">{doc.type}</td>
                                <td className="px-4 py-3 text-sm text-slate-700">{doc.expiry_date ?? '—'}</td>
                                <td className="px-4 py-3">
                                    <ExpiryStatusBadge status={doc.expiry_status} />
                                </td>
                                <td className="px-4 py-3 text-sm text-slate-700">
                                    {doc.days_to_expiry === null || doc.days_to_expiry === undefined ? '—' : doc.days_to_expiry}
                                </td>
                                <td className="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    <div className="flex items-center justify-end gap-3">
                                        {employee?.employee_id ? (
                                            <Link href={route('employees.show', employee.employee_id)} className="shrink-0">
                                                <SecondaryButton type="button">View Employee</SecondaryButton>
                                            </Link>
                                        ) : null}

                                        {employee?.employee_id ? (
                                            <a
                                                href={route('employees.documents.download', [employee.employee_id, doc.id])}
                                                className="text-amber-700 hover:text-amber-900 font-medium"
                                            >
                                                Download
                                            </a>
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
