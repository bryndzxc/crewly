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
    if (s === 'DENIED' || s === 'REJECTED') return 'danger';
    return 'neutral';
}

export default function Index({ auth, requests }) {
    const flash = usePage().props.flash;
    const items = requests?.data ?? [];

    return (
        <AuthenticatedLayout user={auth.user} header="My Leave">
            <Head title="My Leave" />

            <PageHeader title="My Leave" subtitle="Your leave request history." />

            <div className="w-full space-y-4">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}

                <div className="flex items-center justify-end">
                    <Link href={route('my.leave.create')}>
                        <PrimaryButton>Request Leave</PrimaryButton>
                    </Link>
                </div>

                <Card className="p-0 overflow-hidden">
                    <Table
                        columns={[
                            { key: 'dates', label: 'Date Range' },
                            { key: 'type', label: 'Type' },
                            { key: 'status', label: 'Status' },
                            { key: 'created', label: 'Created At' },
                        ]}
                        items={items}
                        rowKey={(r) => r.id}
                        emptyState={items.length === 0 ? 'No leave requests yet.' : null}
                        pagination={{ meta: requests?.meta ?? requests, links: requests?.links ?? [] }}
                        renderRow={(r) => (
                            <tr className="hover:bg-amber-50/30">
                                <td className="px-4 py-3 text-sm text-slate-700">
                                    <div className="font-medium text-slate-900">{r.start_date || '—'} → {r.end_date || '—'}</div>
                                </td>
                                <td className="px-4 py-3 text-sm text-slate-700">{r.leave_type?.name ?? '—'}</td>
                                <td className="px-4 py-3 text-sm">
                                    <Badge tone={statusTone(r.status)}>{String(r.status || '—')}</Badge>
                                </td>
                                <td className="px-4 py-3 text-sm text-slate-700">{r.created_at ?? '—'}</td>
                            </tr>
                        )}
                    />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
