import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import Table from '@/Components/Table';
import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

function fullName(employee) {
    const parts = [employee?.first_name, employee?.middle_name, employee?.last_name, employee?.suffix]
        .map((v) => String(v || '').trim())
        .filter(Boolean);
    return parts.join(' ');
}

function monthOptions() {
    return [
        { value: 1, label: 'January' },
        { value: 2, label: 'February' },
        { value: 3, label: 'March' },
        { value: 4, label: 'April' },
        { value: 5, label: 'May' },
        { value: 6, label: 'June' },
        { value: 7, label: 'July' },
        { value: 8, label: 'August' },
        { value: 9, label: 'September' },
        { value: 10, label: 'October' },
        { value: 11, label: 'November' },
        { value: 12, label: 'December' },
    ];
}

export default function Index({ auth, summary, leaveTypes = [], filters = {} }) {
    const [query, setQuery] = useState(filters.q ?? '');
    const [month, setMonth] = useState(String(filters.month ?? new Date().getMonth() + 1));
    const [year, setYear] = useState(String(filters.year ?? new Date().getFullYear()));
    const [leaveTypeId, setLeaveTypeId] = useState(filters.leave_type_id ?? '');
    const [perPage, setPerPage] = useState(filters.per_page ?? 25);
    const [isLoading, setIsLoading] = useState(false);
    const didInit = useRef(false);

    const rows = summary?.data ?? [];

    const years = useMemo(() => {
        const y = Number(year) || new Date().getFullYear();
        const start = y - 5;
        const end = y + 1;
        const arr = [];
        for (let i = start; i <= end; i++) arr.push(i);
        return arr;
    }, [year]);

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
            if (pathname.startsWith('/leave/summary')) setIsLoading(true);
        });

        const unsubscribeFinish = router.on('finish', (event) => {
            const visit = event?.detail?.visit;
            const pathname = parsePathname(visit?.url);
            if (pathname.startsWith('/leave/summary')) setIsLoading(false);
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
                route('leave.summary.index'),
                {
                    q: query || undefined,
                    month: month || undefined,
                    year: year || undefined,
                    leave_type_id: leaveTypeId || undefined,
                    per_page: perPage,
                    page: 1,
                },
                { preserveState: true, preserveScroll: true, replace: true }
            );
        }, 250);

        return () => clearTimeout(handler);
    }, [query, month, year, leaveTypeId, perPage]);

    const onPerPageChange = (nextPerPage) => {
        setPerPage(nextPerPage);
        router.get(
            route('leave.summary.index'),
            {
                q: query || undefined,
                month: month || undefined,
                year: year || undefined,
                leave_type_id: leaveTypeId || undefined,
                per_page: nextPerPage,
                page: 1,
            },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const exportCsv = () => {
        const url = route('leave.summary.export', {
            q: query || undefined,
            month: month || undefined,
            year: year || undefined,
            leave_type_id: leaveTypeId || undefined,
        });
        window.location.href = url;
    };

    const emptyState = useMemo(() => {
        if (rows.length === 0 && (query ?? '') !== '') return 'No employees match your search.';
        if (rows.length === 0) return 'No leave summary rows found for the selected period.';
        return null;
    }, [rows.length, query]);

    const fmt = (v) => {
        const n = Number(v);
        if (!Number.isFinite(n)) return '0.00';
        return n.toFixed(2);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 leading-tight">Leave Summary</h2>}
            contentClassName="w-full"
        >
            <Head title="Leave Summary" />

            <div className="space-y-5">
                <div className="bg-white/80 backdrop-blur border border-slate-200/70 rounded-2xl shadow-lg shadow-slate-900/5 p-5">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
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
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Month</div>
                                <select
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                    value={month}
                                    onChange={(e) => setMonth(e.target.value)}
                                >
                                    {monthOptions().map((m) => (
                                        <option key={m.value} value={String(m.value)}>
                                            {m.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Year</div>
                                <select
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                    value={year}
                                    onChange={(e) => setYear(e.target.value)}
                                >
                                    {years.map((y) => (
                                        <option key={y} value={String(y)}>
                                            {y}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="lg:col-span-2">
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Leave Type</div>
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
                        </div>

                        <div className="shrink-0">
                            <SecondaryButton type="button" onClick={exportCsv} disabled={isLoading}>
                                Export CSV
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <Table
                    loading={isLoading}
                    loadingText="Loading leave summary…"
                    columns={[
                        { key: 'employee', label: 'Employee' },
                        { key: 'type', label: 'Type' },
                        { key: 'credits', label: 'Credits', align: 'right' },
                        { key: 'used_m', label: 'Used (Month)', align: 'right' },
                        { key: 'used_y', label: 'Used (Year)', align: 'right' },
                        { key: 'remaining', label: 'Remaining', align: 'right' },
                    ]}
                    items={rows}
                    rowKey={(r) => `${r.employee_id}-${r.leave_type_id}`}
                    emptyState={emptyState}
                    pagination={{
                        meta: summary?.meta ?? summary,
                        links: summary?.links ?? summary?.meta?.links ?? [],
                        perPage,
                        onPerPageChange,
                    }}
                    renderRow={(r) => (
                        <tr className="hover:bg-amber-50/30">
                            <td className="px-4 py-3 text-sm">
                                <div className="font-medium text-slate-900">{fullName(r.employee) || r.employee?.employee_code || 'Employee'}</div>
                                <div className="text-xs text-slate-500">{r.employee?.employee_code ?? '—'}</div>
                            </td>
                            <td className="px-4 py-3 text-sm text-slate-700">{r.leaveType?.name ?? '—'}</td>
                            <td className="px-4 py-3 text-sm text-slate-700 text-right tabular-nums">{fmt(r.total_credits)}</td>
                            <td className="px-4 py-3 text-sm text-slate-700 text-right tabular-nums">{fmt(r.used_monthly)}</td>
                            <td className="px-4 py-3 text-sm text-slate-700 text-right tabular-nums">{fmt(r.used_yearly)}</td>
                            <td className="px-4 py-3 text-sm text-slate-700 text-right tabular-nums">{fmt(r.remaining)}</td>
                        </tr>
                    )}
                />
            </div>
        </AuthenticatedLayout>
    );
}
