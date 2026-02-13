import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Pagination from '@/Components/Pagination';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

function severityDotClass(severity) {
    const s = String(severity || '').toUpperCase();
    if (s === 'DANGER') return 'bg-red-500';
    if (s === 'WARNING') return 'bg-amber-500';
    if (s === 'SUCCESS') return 'bg-green-500';
    return 'bg-slate-500';
}

export default function NotificationsIndex({ auth, filters = {}, notifications, types = [] }) {
    const flash = usePage().props.flash;

    const [status, setStatus] = useState(filters.status || '');
    const [type, setType] = useState(filters.type || '');

    const perPage = Number(filters.per_page || 15);

    useEffect(() => {
        setStatus(filters.status || '');
        setType(filters.type || '');
    }, [filters.status, filters.type]);

    const items = useMemo(() => {
        if (!notifications) return [];
        if (Array.isArray(notifications?.data)) return notifications.data;
        return [];
    }, [notifications]);

    const applyFilters = (next = {}) => {
        router.get(
            route('notifications.index'),
            {
                status: next.status !== undefined ? next.status || undefined : status || undefined,
                type: next.type !== undefined ? next.type || undefined : type || undefined,
                per_page: perPage || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const onMarkAllRead = () => {
        router.patch(route('notifications.read-all'), {}, { preserveScroll: true });
    };

    const onOpen = (n) => {
        const url = n?.url;
        router.patch(
            route('notifications.read', n.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (url) {
                        router.visit(url);
                    }
                },
            }
        );
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Notifications" contentClassName="max-w-none">
            <Head title="Notifications" />

            <PageHeader title="Notifications" subtitle="Alerts and reminders across the system." />

            <div className="w-full space-y-4">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}

                <Card className="p-6">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div>
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">View</div>
                                <div className="mt-1 inline-flex rounded-xl border border-slate-200 bg-white">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setStatus('');
                                            applyFilters({ status: '' });
                                        }}
                                        className={
                                            'px-4 py-2 text-xs font-semibold uppercase tracking-widest rounded-l-xl ' +
                                            (status !== 'unread' ? 'bg-amber-50 text-amber-900' : 'text-slate-700 hover:bg-slate-50')
                                        }
                                    >
                                        All
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setStatus('unread');
                                            applyFilters({ status: 'unread' });
                                        }}
                                        className={
                                            'px-4 py-2 text-xs font-semibold uppercase tracking-widest rounded-r-xl border-l border-slate-200 ' +
                                            (status === 'unread' ? 'bg-amber-50 text-amber-900' : 'text-slate-700 hover:bg-slate-50')
                                        }
                                    >
                                        Unread
                                    </button>
                                </div>
                            </div>

                            <div>
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Type</div>
                                <select
                                    value={type}
                                    onChange={(e) => {
                                        const next = e.target.value;
                                        setType(next);
                                        applyFilters({ type: next });
                                    }}
                                    className="mt-1 block w-64 rounded-md border-slate-300 bg-white/90 px-3 py-2 text-left text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                >
                                    {(types || []).map((t) => (
                                        <option key={t.value} value={t.value}>
                                            {t.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="flex items-center gap-3 justify-end">
                            <PrimaryButton type="button" onClick={onMarkAllRead}>
                                Mark all as read
                            </PrimaryButton>
                        </div>
                    </div>
                </Card>

                <div className="space-y-3">
                    {items.length === 0 && (
                        <div className="mx-auto max-w-xl rounded-2xl border border-amber-200/60 bg-amber-50/40 p-6">
                            <div className="text-sm font-semibold text-slate-900">No notifications</div>
                            <div className="mt-1 text-sm text-slate-600">You’re all caught up.</div>
                        </div>
                    )}

                    {items.map((n) => {
                        const isUnread = !n.read_at;
                        return (
                            <button
                                key={n.id}
                                type="button"
                                onClick={() => onOpen(n)}
                                className={
                                    'w-full text-left rounded-2xl border bg-white/80 backdrop-blur shadow-lg shadow-slate-900/5 transition hover:bg-amber-50/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 ' +
                                    (isUnread ? 'border-amber-200/70' : 'border-slate-200/70')
                                }
                            >
                                <div className="p-5">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2">
                                                <span className={'mt-1 inline-flex h-2.5 w-2.5 rounded-full ' + severityDotClass(n.severity)} />
                                                <div className="truncate text-sm font-semibold text-slate-900">{n.title}</div>
                                            </div>
                                            {!!n.body && <div className="mt-1 text-sm text-slate-700">{n.body}</div>}
                                            <div className="mt-2 text-xs text-slate-500">{n.created_at_human || n.created_at}</div>
                                        </div>

                                        {isUnread && (
                                            <span className="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800 ring-1 ring-amber-200">
                                                Unread
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </button>
                        );
                    })}
                </div>

                {notifications?.meta && Array.isArray(notifications?.links) && (
                    <Card className="p-4">
                        <Pagination meta={notifications.meta} links={notifications.links} perPage={perPage} />
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
