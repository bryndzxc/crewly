import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import { Head, Link } from '@inertiajs/react';

function JsonBlock({ value }) {
    const text = value ? JSON.stringify(value, null, 2) : '—';
    return (
        <pre className="mt-2 max-h-[360px] overflow-auto rounded-xl bg-slate-900 text-slate-100 text-xs p-4 ring-1 ring-slate-800">
            {text}
        </pre>
    );
}

export default function AuditLogsShow({ auth, log }) {
    return (
        <AuthenticatedLayout user={auth.user} header="Audit Log" contentClassName="max-w-none">
            <Head title="Audit Log" />

            <PageHeader
                title="Audit Log"
                subtitle={`${log?.action ?? '—'}${log?.created_at ? ` • ${log.created_at}` : ''}`}
                actions={
                    <Link
                        href={route('audit-logs.index')}
                        className="inline-flex items-center rounded-md border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 shadow-sm hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2"
                    >
                        Back
                    </Link>
                }
            />

            <div className="w-full space-y-4">
                <Card className="p-6">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">User</div>
                            <div className="mt-1 text-sm text-slate-900">
                                {log?.user?.name || log?.actor_name || '—'}
                                {log?.user?.email ? <span className="text-slate-500"> ({log.user.email})</span> : null}
                            </div>
                        </div>
                        <div>
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Entity</div>
                            <div className="mt-1 text-sm text-slate-900">
                                {log?.model_type ? `${log.model_type} #${log.model_id ?? '—'}` : '—'}
                            </div>
                        </div>
                        <div className="md:col-span-2">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Description</div>
                            <div className="mt-1 text-sm text-slate-900">{log?.description || '—'}</div>
                        </div>
                    </div>
                </Card>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Card className="p-6">
                        <div className="text-sm font-semibold text-slate-900">Old Values</div>
                        <JsonBlock value={log?.old_values} />
                    </Card>
                    <Card className="p-6">
                        <div className="text-sm font-semibold text-slate-900">New Values</div>
                        <JsonBlock value={log?.new_values} />
                    </Card>
                </div>

                <Card className="p-6">
                    <div className="text-sm font-semibold text-slate-900">Metadata</div>
                    <JsonBlock value={log?.metadata} />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
