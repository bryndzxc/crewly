import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import TextInput from '@/Components/TextInput';
import Table from '@/Components/Table';
import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

function fullName(employee) {
    const parts = [employee?.first_name, employee?.middle_name, employee?.last_name, employee?.suffix]
        .map((v) => String(v || '').trim())
        .filter(Boolean);
    return parts.join(' ');
}

function statusTone(status) {
    const s = String(status || '').toUpperCase();
    if (s === 'PENDING') return 'amber';
    if (s === 'APPROVED') return 'success';
    if (s === 'REJECTED') return 'danger';
    if (s === 'COMPLETED') return 'neutral';
    return 'neutral';
}

export default function Index({ auth, cashAdvances, employees = [], filters = {}, monthlySummary = [] }) {
    const flash = usePage().props.flash;

    const [query, setQuery] = useState(filters.q ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [employeeId, setEmployeeId] = useState(filters.employee_id ?? '');
    const [perPage, setPerPage] = useState(filters.per_page ?? 10);
    const [summaryYear, setSummaryYear] = useState(filters.summary_year ?? new Date().getFullYear());
    const [summaryMonth, setSummaryMonth] = useState(filters.summary_month ?? new Date().getMonth() + 1);
    const [isLoading, setIsLoading] = useState(false);
    const didInit = useRef(false);

    const items = cashAdvances?.data ?? [];

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
            if (pathname.startsWith('/cash-advances')) setIsLoading(true);
        });

        const unsubscribeFinish = router.on('finish', (event) => {
            const visit = event?.detail?.visit;
            const pathname = parsePathname(visit?.url);
            if (pathname.startsWith('/cash-advances')) setIsLoading(false);
        });

        return () => {
            if (typeof unsubscribeStart === 'function') unsubscribeStart();
            if (typeof unsubscribeFinish === 'function') unsubscribeFinish();
        };
    }, []);

    useEffect(() => {
        if (!didInit.current) {
            didInit.current = true;
            return;
        }

        const handler = setTimeout(() => {
            router.get(
                route('cash_advances.index'),
                {
                    q: query,
                    status: status || undefined,
                    employee_id: employeeId || undefined,
                    per_page: perPage,
                    summary_year: summaryYear,
                    summary_month: summaryMonth,
                    page: 1,
                },
                { preserveState: true, preserveScroll: true, replace: true }
            );
        }, 250);

        return () => clearTimeout(handler);
    }, [query, status, employeeId, perPage, summaryYear, summaryMonth]);

    const onPerPageChange = (nextPerPage) => {
        setPerPage(nextPerPage);
        router.get(
            route('cash_advances.index'),
            {
                q: query,
                status: status || undefined,
                employee_id: employeeId || undefined,
                per_page: nextPerPage,
                summary_year: summaryYear,
                summary_month: summaryMonth,
                page: 1,
            },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const emptyState = useMemo(() => {
        if (items.length === 0 && (query ?? '') !== '') return 'No cash advances match your search.';
        if (items.length === 0) return 'No cash advances found.';
        return null;
    }, [items.length, query]);

    return (
        <AuthenticatedLayout user={auth.user} header="Cash Advances" contentClassName="max-w-none">
            <Head title="Cash Advances" />

            <div className="w-full space-y-4">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}

                <PageHeader title="Cash Advances" subtitle="Approval workflow and deduction tracking." />

                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6 lg:gap-4 w-full">
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
                            <option value="REJECTED">Rejected</option>
                            <option value="COMPLETED">Completed</option>
                        </select>
                    </div>

                    <div>
                        <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Employee (exact)</div>
                        <select
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                            value={employeeId}
                            onChange={(e) => setEmployeeId(e.target.value)}
                        >
                            <option value="">All</option>
                            {(employees ?? []).map((e) => (
                                <option key={e.employee_id} value={e.employee_id}>
                                    {e.employee_code} - {fullName(e)}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Summary Year</div>
                        <TextInput
                            type="number"
                            value={summaryYear}
                            onChange={(e) => setSummaryYear(e.target.value)}
                            className="mt-1 block w-full"
                        />
                    </div>

                    <div>
                        <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Summary Month</div>
                        <select
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                            value={summaryMonth}
                            onChange={(e) => setSummaryMonth(e.target.value)}
                        >
                            {Array.from({ length: 12 }).map((_, i) => (
                                <option key={i + 1} value={i + 1}>
                                    {i + 1}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <Table
                    loading={isLoading}
                    loadingText="Loading cash advances…"
                    columns={[
                        { key: 'employee', label: 'Employee' },
                        { key: 'amount', label: 'Amount' },
                        { key: 'requested', label: 'Date Requested' },
                        { key: 'status', label: 'Status' },
                        { key: 'remaining', label: 'Remaining' },
                        { key: 'action', label: 'Action', align: 'right' },
                    ]}
                    items={items}
                    rowKey={(r) => r.id}
                    emptyState={emptyState}
                    pagination={{
                        meta: cashAdvances?.meta ?? cashAdvances,
                        links: cashAdvances?.links ?? cashAdvances?.meta?.links ?? [],
                        perPage,
                        onPerPageChange,
                    }}
                    renderRow={(r) => (
                        <tr className="hover:bg-amber-50/30">
                            <td className="px-4 py-3 text-sm">
                                <div className="font-medium text-slate-900">{fullName(r.employee) || r.employee?.employee_code || 'Employee'}</div>
                                <div className="text-xs text-slate-500">{r.employee?.employee_code ?? '—'}</div>
                            </td>
                            <td className="px-4 py-3 text-sm text-slate-700">{Number(r.amount || 0).toFixed(2)}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">{r.requested_at ?? '—'}</td>
                            <td className="px-4 py-3 text-sm"><Badge tone={statusTone(r.status)}>{String(r.status || '—')}</Badge></td>
                            <td className="px-4 py-3 text-sm text-slate-700">{Number(r.remaining_balance || 0).toFixed(2)}</td>
                            <td className="px-4 py-3 text-sm text-right">
                                <Link
                                    className="text-amber-700 hover:text-amber-800 font-medium"
                                    href={route('cash_advances.show', r.id)}
                                >
                                    View
                                </Link>
                            </td>
                        </tr>
                    )}
                />

                <Card className="p-6">
                    <div className="text-sm font-semibold text-slate-900">Monthly Deduction Summary</div>
                    <div className="mt-1 text-xs text-slate-500">Totals for the selected month/year.</div>

                    <div className="mt-4 overflow-x-auto">
                        <table className="min-w-[520px] w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Employee</th>
                                    <th className="px-3 py-2 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Total Deducted</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {(monthlySummary ?? []).length === 0 && (
                                    <tr>
                                        <td className="px-3 py-3 text-sm text-slate-600" colSpan={2}>No deductions for this period.</td>
                                    </tr>
                                )}
                                {(monthlySummary ?? []).map((row, idx) => (
                                    <tr key={idx}>
                                        <td className="px-3 py-3 text-sm">
                                            <div className="font-medium text-slate-900">{fullName(row.employee) || row.employee?.employee_code || 'Employee'}</div>
                                            <div className="text-xs text-slate-500">{row.employee?.employee_code ?? '—'}</div>
                                        </td>
                                        <td className="px-3 py-3 text-sm text-right text-slate-700">{Number(row.total_amount || 0).toFixed(2)}</td>
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
