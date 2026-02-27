import React, { useMemo, useRef, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Table from '@/Components/Table';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import { Head, router, usePage } from '@inertiajs/react';

export default function Index() {
    const { props } = usePage();
    const auth = props.auth ?? {};
    const flash = props.flash ?? {};

    const filters = props.filters ?? {};
    const leadsPaginator = props.leads ?? null;
    const leads = leadsPaginator?.data ?? (Array.isArray(leadsPaginator) ? leadsPaginator : []);

    const [perPage, setPerPage] = useState(filters.per_page ?? 10);
    const [actionState, setActionState] = useState({ leadId: null, action: null });
    const actionLockRef = useRef(false);

    const onPerPageChange = (nextPerPage) => {
        setPerPage(nextPerPage);
        router.get(
            route('developer.demo_requests.index'),
            { per_page: nextPerPage, page: 1 },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const columns = useMemo(
        () => [
            { key: 'created_at', label: 'Submitted', className: 'px-4 py-3 whitespace-nowrap' },
            { key: 'full_name', label: 'Full name', className: 'px-4 py-3' },
            { key: 'company_name', label: 'Company', className: 'px-4 py-3' },
            { key: 'email', label: 'Email', className: 'px-4 py-3' },
            { key: 'phone', label: 'Phone', className: 'px-4 py-3' },
            { key: 'company_size', label: 'Size', className: 'px-4 py-3' },
            { key: 'status', label: 'Status', className: 'px-4 py-3 whitespace-nowrap' },
            { key: 'message', label: 'Message', className: 'px-4 py-3' },
            { key: 'actions', label: 'Actions', className: 'px-4 py-3 text-right whitespace-nowrap' },
        ],
        []
    );

    const statusBadgeClass = (status) => {
        const s = String(status || 'pending').toLowerCase();
        if (s === 'approved') return 'bg-green-100 text-green-800 ring-1 ring-green-200';
        if (s === 'declined') return 'bg-red-100 text-red-800 ring-1 ring-red-200';
        return 'bg-amber-100 text-amber-800 ring-1 ring-amber-200';
    };

    const statusLabel = (status) => {
        const s = String(status || 'pending').toLowerCase();
        if (s === 'approved') return 'Approved';
        if (s === 'declined') return 'Declined';
        return 'Pending';
    };

    const approve = (leadId) => {
        if (actionLockRef.current) return;
        actionLockRef.current = true;

        setActionState({ leadId, action: 'approve' });
        router.post(
            route('developer.demo_requests.approve', leadId),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    actionLockRef.current = false;
                    setActionState({ leadId: null, action: null });
                },
            }
        );
    };

    const decline = (leadId) => {
        if (actionLockRef.current) return;
        actionLockRef.current = true;

        setActionState({ leadId, action: 'decline' });
        router.post(
            route('developer.demo_requests.decline', leadId),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    actionLockRef.current = false;
                    setActionState({ leadId: null, action: null });
                },
            }
        );
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Developer Settings" contentClassName="max-w-none">
            <Head title="Developer Settings / Demo requests" />

            <div className="space-y-4">
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

                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-slate-900">Demo requests</h1>
                </div>

                <div className="mt-2">
                    <Table
                        columns={columns}
                        items={leads}
                        rowKey={(l) => l.id}
                        emptyState={<div className="text-sm">No demo requests found.</div>}
                        pagination={
                            leadsPaginator
                                ? {
                                      meta: leadsPaginator?.meta ?? leadsPaginator,
                                      links: leadsPaginator?.links ?? leadsPaginator?.meta?.links ?? [],
                                      perPage,
                                      onPerPageChange,
                                  }
                                : null
                        }
                        renderRow={(lead) => (
                            <tr className="align-top">
                                <td className="px-4 py-3 whitespace-nowrap">
                                    <div className="text-sm text-slate-700">{lead.created_at || '—'}</div>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="font-medium text-slate-900">{lead.full_name || '—'}</div>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="text-sm text-slate-700">{lead.company_name || '—'}</div>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="text-sm text-slate-700">{lead.email || '—'}</div>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="text-sm text-slate-700">{lead.phone || '—'}</div>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="text-sm text-slate-700">{lead.company_size || '—'}</div>
                                </td>
                                <td className="px-4 py-3 whitespace-nowrap">
                                    <span className={'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ' + statusBadgeClass(lead.status)}>
                                        {statusLabel(lead.status)}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="text-sm text-slate-700 whitespace-pre-wrap break-words max-w-2xl">{lead.message || '—'}</div>
                                </td>
                                <td className="px-4 py-3 text-right whitespace-nowrap">
                                    {(() => {
                                        const isPending = String(lead.status || 'pending').toLowerCase() === 'pending';
                                        const isRowProcessing = actionState.leadId === lead.id;
                                        const isApproving = isRowProcessing && actionState.action === 'approve';
                                        const isDeclining = isRowProcessing && actionState.action === 'decline';

                                        return (
                                    <div className="flex items-center justify-end gap-2">
                                        <PrimaryButton
                                            type="button"
                                            className="px-3 py-2"
                                            onClick={() => approve(lead.id)}
                                            disabled={!isPending || actionState.leadId !== null}
                                        >
                                            <span className="inline-flex items-center gap-2">
                                                {isApproving && (
                                                    <span
                                                        aria-hidden="true"
                                                        className="h-4 w-4 rounded-full border-2 border-slate-900/30 border-t-slate-900 animate-spin"
                                                    />
                                                )}
                                                {isApproving ? 'Approving…' : 'Approve'}
                                            </span>
                                        </PrimaryButton>
                                        <DangerButton
                                            type="button"
                                            className="px-3 py-2"
                                            onClick={() => decline(lead.id)}
                                            disabled={!isPending || actionState.leadId !== null}
                                        >
                                            <span className="inline-flex items-center gap-2">
                                                {isDeclining && (
                                                    <span
                                                        aria-hidden="true"
                                                        className="h-4 w-4 rounded-full border-2 border-white/30 border-t-white animate-spin"
                                                    />
                                                )}
                                                {isDeclining ? 'Declining…' : 'Decline'}
                                            </span>
                                        </DangerButton>
                                    </div>
                                        );
                                    })()}
                                </td>
                            </tr>
                        )}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
