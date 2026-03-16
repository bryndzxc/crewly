import React, { useMemo, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Table from '@/Components/Table';
import Badge from '@/Components/UI/Badge';
import SecondaryButton from '@/Components/SecondaryButton';
import PrimaryButton from '@/Components/PrimaryButton';

function toneForStatus(status) {
    const s = String(status || '').toLowerCase();
    if (s === 'ok') return 'success';
    if (s === 'pending_review') return 'amber';
    if (s === 'failed') return 'danger';
    if (s === 'changed') return 'neutral';
    return 'neutral';
}

function labelForStatus(status) {
    const s = String(status || '').toLowerCase();
    if (s === 'ok') return 'Up to date';
    if (s === 'pending_review') return 'Pending review';
    if (s === 'failed') return 'Failed';
    if (s === 'changed') return 'Change detected';
    return s || '—';
}

export default function Index() {
    const { props } = usePage();
    const auth = props.auth ?? {};
    const flash = props.flash ?? {};

    const monitors = Array.isArray(props.monitors) ? props.monitors : [];
    const draftsPaginator = props.drafts ?? null;
    const drafts = draftsPaginator?.data ?? [];
    const filters = props.filters ?? {};
    const sourceTypes = Array.isArray(props.source_types) ? props.source_types : [];

    const [sourceType, setSourceType] = useState(filters.source_type ?? '');
    const [perPage, setPerPage] = useState(filters.per_page ?? 15);

    const monitorColumns = useMemo(
        () => [
            { key: 'type', label: 'Source', className: 'px-4 py-3' },
            { key: 'url', label: 'URL', className: 'px-4 py-3' },
            { key: 'checked', label: 'Last checked', className: 'px-4 py-3' },
            { key: 'status', label: 'Status', className: 'px-4 py-3' },
            { key: 'actions', label: 'Actions', className: 'px-4 py-3 text-right whitespace-nowrap' },
        ],
        []
    );

    const draftColumns = useMemo(
        () => [
            { key: 'type', label: 'Source', className: 'px-4 py-3' },
            { key: 'detected', label: 'Detected at', className: 'px-4 py-3' },
            { key: 'hash', label: 'Hash', className: 'px-4 py-3' },
            { key: 'actions', label: 'Actions', className: 'px-4 py-3 text-right whitespace-nowrap' },
        ],
        []
    );

    const refreshDrafts = (next = {}) => {
        router.get(route('admin.government_updates.index'), { source_type: sourceType, per_page: perPage, ...next }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const onPerPageChange = (nextPerPage) => {
        setPerPage(nextPerPage);
        refreshDrafts({ per_page: nextPerPage, page: 1 });
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Government Update Monitor" contentClassName="max-w-none">
            <Head title="Government Update Monitor" />

            <div className="space-y-3">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}
            </div>

            <div className="mt-6 flex items-start justify-between gap-4 flex-col sm:flex-row">
                <div>
                    <h1 className="text-xl font-semibold text-slate-900">Government Update Monitor</h1>
                    <p className="mt-1 text-sm text-slate-600">Checks official sources and creates drafts for review (never auto-activates).</p>
                </div>
                <div className="flex items-center gap-2 flex-wrap">
                    <SecondaryButton type="button" onClick={() => router.post(route('admin.government_updates.check_all'), {}, { preserveScroll: true })}>
                        Check all
                    </SecondaryButton>
                </div>
            </div>

            <div className="mt-6">
                <Table
                    columns={monitorColumns}
                    items={monitors}
                    rowKey={(m) => m.id}
                    emptyState={<div className="text-sm">No monitors found.</div>}
                    renderRow={(m) => (
                        <tr className="align-top">
                            <td className="px-4 py-3">
                                <div className="font-semibold text-slate-900 uppercase">{m.source_type}</div>
                                {m.latest_draft ? (
                                    <div className="mt-1 text-xs text-slate-600">
                                        Latest draft: #{m.latest_draft.id} ({m.latest_draft.status})
                                    </div>
                                ) : (
                                    <div className="mt-1 text-xs text-slate-500">No drafts yet</div>
                                )}
                            </td>
                            <td className="px-4 py-3">
                                <div className="text-sm text-slate-700 break-all">{m.source_url || '—'}</div>
                                {m.last_error ? <div className="mt-1 text-xs text-red-700">{m.last_error}</div> : null}
                            </td>
                            <td className="px-4 py-3">
                                <div className="text-sm text-slate-700">{m.last_checked_at || '—'}</div>
                            </td>
                            <td className="px-4 py-3">
                                <Badge tone={toneForStatus(m.last_status)}>{labelForStatus(m.last_status)}</Badge>
                            </td>
                            <td className="px-4 py-3 text-right whitespace-nowrap">
                                <div className="flex items-center justify-end gap-2 flex-wrap">
                                    <SecondaryButton
                                        type="button"
                                        onClick={() =>
                                            router.post(route('admin.government_updates.check_one', m.source_type), {}, { preserveScroll: true })
                                        }
                                    >
                                        Check now
                                    </SecondaryButton>
                                    {m.latest_draft && String(m.latest_draft.status) === 'draft' ? (
                                        <PrimaryButton
                                            type="button"
                                            onClick={() => router.get(route('admin.government_updates.drafts.show', m.latest_draft.id))}
                                        >
                                            Review draft
                                        </PrimaryButton>
                                    ) : null}
                                </div>
                            </td>
                        </tr>
                    )}
                />
            </div>

            <div className="mt-10">
                <div className="flex items-center justify-between gap-3 flex-col sm:flex-row">
                    <div>
                        <div className="text-sm font-semibold text-slate-900">Draft updates</div>
                        <div className="mt-1 text-xs text-slate-600">Only draft-status updates are listed here.</div>
                    </div>
                    <div className="flex items-center gap-2 flex-wrap">
                        <select
                            className="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-amber-400 focus:ring-amber-400"
                            value={sourceType}
                            onChange={(e) => {
                                setSourceType(e.target.value);
                                router.get(route('admin.government_updates.index'), { source_type: e.target.value, per_page: perPage, page: 1 }, { preserveState: true, preserveScroll: true, replace: true });
                            }}
                        >
                            <option value="">All sources</option>
                            {sourceTypes.map((t) => (
                                <option key={t} value={t}>
                                    {String(t).toUpperCase()}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="mt-4">
                    <Table
                        columns={draftColumns}
                        items={drafts}
                        rowKey={(d) => d.id}
                        emptyState={<div className="text-sm">No draft updates.</div>}
                        pagination={
                            draftsPaginator
                                ? {
                                      meta: draftsPaginator?.meta ?? draftsPaginator,
                                      links: draftsPaginator?.links ?? draftsPaginator?.meta?.links ?? [],
                                      perPage,
                                      onPerPageChange,
                                  }
                                : null
                        }
                        renderRow={(d) => (
                            <tr className="align-top">
                                <td className="px-4 py-3">
                                    <div className="font-semibold text-slate-900 uppercase">{d.source_type}</div>
                                    <div className="text-xs text-slate-500">#{d.id}</div>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="text-sm text-slate-700">{d.detected_at || '—'}</div>
                                    <div className="text-xs text-slate-500 break-all">{d.source_url}</div>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="text-xs text-slate-600 break-all">{d.content_hash}</div>
                                </td>
                                <td className="px-4 py-3 text-right whitespace-nowrap">
                                    <SecondaryButton type="button" onClick={() => router.get(route('admin.government_updates.drafts.show', d.id))}>
                                        Review
                                    </SecondaryButton>
                                </td>
                            </tr>
                        )}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
