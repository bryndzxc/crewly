import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import DatePicker from '@/Components/DatePicker';
import TextInput from '@/Components/TextInput';
import Table from '@/Components/Table';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function AuditLogsIndex({ auth, filters = {}, logs, users = [], modules = [] }) {
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');
    const [action, setAction] = useState(filters.action || '');
    const [module, setModule] = useState(filters.module || '');
    const [userId, setUserId] = useState(filters.user_id || '');

    const perPage = Number(filters.per_page || 15);

    useEffect(() => {
        setFrom(filters.from || '');
        setTo(filters.to || '');
        setAction(filters.action || '');
        setModule(filters.module || '');
        setUserId(filters.user_id || '');
    }, [filters.from, filters.to, filters.action, filters.module, filters.user_id]);

    const items = useMemo(() => {
        if (!logs) return [];
        if (Array.isArray(logs?.data)) return logs.data;
        if (Array.isArray(logs)) return logs;
        return [];
    }, [logs]);

    const onApply = () => {
        router.get(
            route('developer.audit-logs.index'),
            {
                from: from || undefined,
                to: to || undefined,
                action: action || undefined,
                module: module || undefined,
                user_id: userId || undefined,
                per_page: perPage || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const onReset = () => {
        setFrom('');
        setTo('');
        setAction('');
        setModule('');
        setUserId('');
        router.get(route('developer.audit-logs.index'), {}, { preserveState: true, preserveScroll: true, replace: true });
    };

    const emptyState = (
        <div className="mx-auto max-w-xl rounded-2xl border border-amber-200/60 bg-amber-50/40 p-6">
            <div className="text-sm font-semibold text-slate-900">No audit logs</div>
            <div className="mt-1 text-sm text-slate-600">Try expanding the date range or clearing filters.</div>
        </div>
    );

    return (
        <AuthenticatedLayout user={auth.user} header="Audit Logs" contentClassName="max-w-none">
            <Head title="Audit Logs" />

            <PageHeader title="Audit Logs" subtitle="Sensitive actions and downloads across the system." />

            <div className="w-full space-y-4">
                <Card className="relative z-40 p-6">
                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-end">
                        <div className="lg:col-span-3">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Date From</div>
                            <DatePicker value={from} onChange={setFrom} />
                        </div>
                        <div className="lg:col-span-3">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Date To</div>
                            <DatePicker value={to} onChange={setTo} />
                        </div>
                        <div className="lg:col-span-3">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Module</div>
                            <select
                                value={module}
                                onChange={(e) => setModule(e.target.value)}
                                className="mt-1 block w-full rounded-md border-slate-300 bg-white/90 px-3 py-2 text-left text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                            >
                                {modules.map((m) => (
                                    <option key={m.value} value={m.value}>
                                        {m.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="lg:col-span-3">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">User</div>
                            <select
                                value={userId}
                                onChange={(e) => setUserId(e.target.value)}
                                className="mt-1 block w-full rounded-md border-slate-300 bg-white/90 px-3 py-2 text-left text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                            >
                                <option value="">All</option>
                                {users.map((u) => (
                                    <option key={u.id} value={u.id}>
                                        {u.name} {u.email ? `(${u.email})` : ''}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="lg:col-span-6">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Action</div>
                            <TextInput
                                value={action}
                                onChange={(e) => setAction(e.target.value)}
                                placeholder="e.g. leave.approved, document.downloaded"
                                className="mt-1 w-full"
                            />
                        </div>

                        <div className="lg:col-span-6 flex items-center gap-3 justify-end">
                            <PrimaryButton type="button" onClick={onApply}>
                                Apply
                            </PrimaryButton>
                            <button
                                type="button"
                                onClick={onReset}
                                className="inline-flex items-center rounded-md border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 shadow-sm hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                </Card>

                <Table
                    columns={[
                        { key: 'created_at', label: 'Date' },
                        { key: 'user', label: 'User' },
                        { key: 'action', label: 'Action' },
                        { key: 'description', label: 'Description' },
                        { key: 'entity', label: 'Entity' },
                        { key: 'ip', label: 'IP' },
                        { key: 'view', label: '', align: 'right' },
                    ]}
                    items={items}
                    rowKey={(l) => l.id}
                    emptyState={emptyState}
                    pagination={
                        logs && (logs?.meta || logs?.links)
                            ? {
                                  meta: logs?.meta ?? logs,
                                  links: logs?.links ?? logs?.meta?.links ?? [],
                                  perPage,
                              }
                            : null
                    }
                    renderRow={(l) => (
                        <tr className="hover:bg-amber-50/30">
                            <td className="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">{l.created_at}</td>
                            <td className="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">
                                {l.user?.name || l.actor_name || '—'}
                            </td>
                            <td className="px-4 py-3 text-sm font-medium text-slate-900 whitespace-nowrap">{l.action}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">{l.description || '—'}</td>
                            <td className="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">
                                {l.model_label ? `${l.model_label} #${l.model_id ?? '—'}` : '—'}
                            </td>
                            <td className="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">{l.ip || '—'}</td>
                            <td className="px-4 py-3 text-right text-sm whitespace-nowrap">
                                <Link
                                    href={route('developer.audit-logs.show', l.id)}
                                    className="font-medium text-amber-700 hover:text-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:ring-offset-2 rounded"
                                >
                                    View
                                </Link>
                            </td>
                        </tr>
                    )}
                />
            </div>
        </AuthenticatedLayout>
    );
}
