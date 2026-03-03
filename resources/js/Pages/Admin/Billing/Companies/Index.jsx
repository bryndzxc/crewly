import React, { useMemo, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import Table from '@/Components/Table';
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';
import Badge from '@/Components/UI/Badge';

function statusTone(status) {
    const s = String(status || '').toLowerCase();
    if (s === 'active') return 'success';
    if (s === 'trial') return 'neutral';
    if (s === 'past_due') return 'amber';
    if (s === 'suspended') return 'danger';
    return 'neutral';
}

export default function Index() {
    const { props } = usePage();
    const auth = props.auth ?? {};
    const filters = props.filters ?? {};
    const companiesPaginator = props.companies ?? null;
    const companies = companiesPaginator?.data ?? [];
    const statuses = Array.isArray(props.statuses) ? props.statuses : [];

    const [q, setQ] = useState(filters.q ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [perPage, setPerPage] = useState(filters.per_page ?? 15);

    const submitSearch = (e) => {
        e.preventDefault();
        router.get(
            route('admin.billing.companies.index'),
            { q, status, per_page: perPage, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const onPerPageChange = (nextPerPage) => {
        setPerPage(nextPerPage);
        router.get(
            route('admin.billing.companies.index'),
            { q, status, per_page: nextPerPage, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const columns = useMemo(
        () => [
            { key: 'company', label: 'Company', className: 'px-4 py-3' },
            { key: 'plan', label: 'Plan', className: 'px-4 py-3' },
            { key: 'status', label: 'Status', className: 'px-4 py-3' },
            { key: 'next', label: 'Next billing', className: 'px-4 py-3' },
            { key: 'actions', label: 'Actions', className: 'px-4 py-3 text-right whitespace-nowrap' },
        ],
        []
    );

    return (
        <AuthenticatedLayout user={auth.user} header="Admin Billing" contentClassName="max-w-none">
            <Head title="Admin Billing / Companies" />

            <div className="flex items-start justify-between gap-4 flex-col sm:flex-row">
                <div>
                    <h1 className="text-xl font-semibold text-slate-900">Companies</h1>
                    <p className="mt-1 text-sm text-slate-600">Manual subscription management (super-admin only).</p>
                </div>
            </div>

            <form onSubmit={submitSearch} className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                <div className="flex-1">
                    <TextInput
                        className="w-full"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Search company name or slug…"
                    />
                </div>
                <div>
                    <select
                        className="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-amber-400 focus:ring-amber-400"
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                    >
                        <option value="">All statuses</option>
                        {statuses.map((s) => (
                            <option key={s} value={s}>
                                {String(s).replace('_', ' ')}
                            </option>
                        ))}
                    </select>
                </div>
                <SecondaryButton type="submit">Search</SecondaryButton>
            </form>

            <div className="mt-6">
                <Table
                    columns={columns}
                    items={companies}
                    rowKey={(c) => c.id}
                    emptyState={<div className="text-sm">No companies found.</div>}
                    pagination={
                        companiesPaginator
                            ? {
                                  meta: companiesPaginator?.meta ?? companiesPaginator,
                                  links: companiesPaginator?.links ?? companiesPaginator?.meta?.links ?? [],
                                  perPage,
                                  onPerPageChange,
                              }
                            : null
                    }
                    renderRow={(company) => (
                        <tr className="align-top">
                            <td className="px-4 py-3">
                                <div className="font-semibold text-slate-900">{company.name}</div>
                                <div className="text-xs text-slate-500">{company.slug || '—'}</div>
                            </td>
                            <td className="px-4 py-3">
                                <div className="text-sm text-slate-700">{(company.plan_name || 'starter').toUpperCase()}</div>
                                <div className="text-xs text-slate-500">Max {company.max_employees || 0} employees</div>
                            </td>
                            <td className="px-4 py-3">
                                <Badge tone={statusTone(company.subscription_status)}>
                                    {String(company.subscription_status || '').replace('_', ' ') || '—'}
                                </Badge>
                            </td>
                            <td className="px-4 py-3">
                                <div className="text-sm text-slate-700">{company.next_billing_at || '—'}</div>
                                <div className="text-xs text-slate-500">Last paid: {company.last_payment_at || '—'}</div>
                            </td>
                            <td className="px-4 py-3 text-right whitespace-nowrap">
                                <SecondaryButton
                                    type="button"
                                    onClick={() => router.get(route('admin.billing.companies.show', company.id))}
                                >
                                    View
                                </SecondaryButton>
                            </td>
                        </tr>
                    )}
                />
            </div>
        </AuthenticatedLayout>
    );
}
