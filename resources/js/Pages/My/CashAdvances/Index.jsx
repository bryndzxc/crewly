import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Table from '@/Components/Table';
import Badge from '@/Components/UI/Badge';
import { Head, Link, usePage } from '@inertiajs/react';

function statusTone(status) {
    const s = String(status || '').toUpperCase();
    if (s === 'PENDING') return 'amber';
    if (s === 'APPROVED') return 'success';
    if (s === 'REJECTED') return 'danger';
    if (s === 'COMPLETED') return 'neutral';
    return 'neutral';
}

export default function Index({ auth, cashAdvances }) {
    const flash = usePage().props.flash;
    const items = cashAdvances?.data ?? [];

    return (
        <AuthenticatedLayout user={auth.user} header="My Cash Advances">
            <Head title="My Cash Advances" />

            <PageHeader title="My Cash Advances" subtitle="Request and track cash advances." />

            <div className="w-full space-y-4">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}

                <div className="flex items-center justify-end">
                    <Link href={route('my.cash_advances.create')}>
                        <PrimaryButton>Request Cash Advance</PrimaryButton>
                    </Link>
                </div>

                <Card className="p-0 overflow-hidden">
                    <Table
                        columns={[
                            { key: 'requested_at', label: 'Date Requested' },
                            { key: 'amount', label: 'Amount' },
                            { key: 'status', label: 'Status' },
                            { key: 'remaining', label: 'Remaining' },
                            { key: 'created', label: 'Created At' },
                        ]}
                        items={items}
                        rowKey={(r) => r.id}
                        emptyState={items.length === 0 ? 'No cash advances yet.' : null}
                        pagination={{ meta: cashAdvances?.meta ?? cashAdvances, links: cashAdvances?.links ?? [] }}
                        renderRow={(r) => (
                            <tr className="hover:bg-amber-50/30">
                                <td className="px-4 py-3 text-sm text-slate-700">{r.requested_at ?? '—'}</td>
                                <td className="px-4 py-3 text-sm text-slate-700">{Number(r.amount || 0).toFixed(2)}</td>
                                <td className="px-4 py-3 text-sm">
                                    <Badge tone={statusTone(r.status)}>{String(r.status || '—')}</Badge>
                                </td>
                                <td className="px-4 py-3 text-sm text-slate-700">{Number(r.remaining_balance || 0).toFixed(2)}</td>
                                <td className="px-4 py-3 text-sm text-slate-700">{r.created_at ?? '—'}</td>
                            </tr>
                        )}
                    />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
