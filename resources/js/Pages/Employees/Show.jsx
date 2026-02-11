import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/UI/PageHeader';
import Card from '@/Components/UI/Card';
import Tabs from '@/Components/UI/Tabs';
import Badge, { toneFromStatus } from '@/Components/UI/Badge';
import PrimaryButton from '@/Components/PrimaryButton';
import DataTable from '@/Components/UI/DataTable';
import { dummyEmployees } from '@/data/dummyEmployees';
import { Head, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

function initials(name) {
    const parts = String(name || '')
        .trim()
        .split(/\s+/)
        .filter(Boolean);
    if (parts.length === 0) return 'U';
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0].slice(0, 1) + parts[parts.length - 1].slice(0, 1)).toUpperCase();
}

function parseEmployeeIdFromUrl(url) {
    const parts = String(url || '')
        .split('?')[0]
        .split('#')[0]
        .split('/')
        .filter(Boolean);

    const idx = parts.findIndex((p) => p === 'employees');
    if (idx === -1) return null;

    const id = parts[idx + 1];
    if (!id) return null;
    if (!/^\d+$/.test(id)) return null;
    return Number(id);
}

export default function Show({ auth }) {
    const { url } = usePage();
    const employeeId = parseEmployeeIdFromUrl(url) ?? 1;

    const employee = useMemo(() => {
        return dummyEmployees.find((e) => e.id === employeeId) ?? dummyEmployees[0];
    }, [employeeId]);

    const [tab, setTab] = useState('overview');

    return (
        <AuthenticatedLayout user={auth.user} header="Employees" contentClassName="max-w-none">
            <Head title={employee ? `Employee - ${employee.fullName}` : 'Employee'} />

            <div className="w-full space-y-4">
                <PageHeader
                    title={employee.fullName}
                    subtitle={employee.employeeId}
                    actions={<Badge tone={toneFromStatus(employee.status)}>{employee.status}</Badge>}
                />

                <Card className="p-5">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-4 min-w-0">
                            <div className="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-800 ring-1 ring-amber-200">
                                <span className="text-sm font-semibold">{initials(employee.fullName)}</span>
                            </div>
                            <div className="min-w-0">
                                <div className="truncate text-xl font-semibold tracking-tight text-slate-900">
                                    {employee.fullName}
                                </div>
                                <div className="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
                                    <span>{employee.department}</span>
                                    <span className="text-slate-300">•</span>
                                    <span>{employee.position}</span>
                                </div>
                            </div>
                        </div>

                        <div className="text-sm text-slate-600">
                            <div className="font-medium text-slate-900">Contact</div>
                            <div className="mt-0.5">{employee.email}</div>
                            <div>{employee.phone}</div>
                        </div>
                    </div>
                </Card>

                <Card className="p-5">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Department</div>
                            <div className="mt-1 text-sm font-medium text-slate-900">{employee.department}</div>
                        </div>
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Position</div>
                            <div className="mt-1 text-sm font-medium text-slate-900">{employee.position}</div>
                        </div>
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Email</div>
                            <div className="mt-1 text-sm font-medium text-slate-900">{employee.email}</div>
                        </div>
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Phone</div>
                            <div className="mt-1 text-sm font-medium text-slate-900">{employee.phone}</div>
                        </div>
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Hire Date</div>
                            <div className="mt-1 text-sm font-medium text-slate-900">{employee.hireDate}</div>
                        </div>
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Employee ID</div>
                            <div className="mt-1 text-sm font-medium text-slate-900">{employee.employeeId}</div>
                        </div>
                    </div>
                </Card>

                <Card className="p-5">
                    <Tabs
                        tabs={[
                            { key: 'overview', label: 'Overview' },
                            { key: 'documents', label: 'Documents' },
                            { key: 'history', label: 'Employment History' },
                            { key: 'notes', label: 'Notes' },
                        ]}
                        value={tab}
                        onChange={setTab}
                    />

                    <div className="pt-5">
                        {tab === 'overview' && (
                            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                <Card className="p-5">
                                    <div className="text-sm font-semibold text-slate-900">Overview</div>
                                    <p className="mt-2 text-sm text-slate-600">
                                        This is frontend-only dummy content. Use this tab to show a summary, key details,
                                        and quick insights once backend data is connected.
                                    </p>
                                    <div className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div className="rounded-2xl border border-slate-200 bg-white p-4">
                                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Status</div>
                                            <div className="mt-1">
                                                <Badge tone={toneFromStatus(employee.status)}>{employee.status}</Badge>
                                            </div>
                                        </div>
                                        <div className="rounded-2xl border border-slate-200 bg-white p-4">
                                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Department</div>
                                            <div className="mt-1 text-sm font-semibold text-slate-900">{employee.department}</div>
                                        </div>
                                    </div>
                                </Card>

                                <Card className="p-5">
                                    <div className="text-sm font-semibold text-slate-900">Key Info</div>
                                    <div className="mt-3 space-y-2 text-sm text-slate-700">
                                        <div className="flex items-center justify-between gap-4">
                                            <span className="text-slate-600">Position</span>
                                            <span className="font-medium text-slate-900">{employee.position}</span>
                                        </div>
                                        <div className="flex items-center justify-between gap-4">
                                            <span className="text-slate-600">Hire date</span>
                                            <span className="font-medium text-slate-900">{employee.hireDate}</span>
                                        </div>
                                        <div className="flex items-center justify-between gap-4">
                                            <span className="text-slate-600">Employee ID</span>
                                            <span className="font-medium text-slate-900">{employee.employeeId}</span>
                                        </div>
                                    </div>
                                </Card>
                            </div>
                        )}

                        {tab === 'documents' && (
                            <div className="space-y-4">
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <div className="text-sm font-semibold text-slate-900">Documents</div>
                                        <div className="mt-1 text-sm text-slate-600">Upload/download/delete are UI-only for now.</div>
                                    </div>
                                    <PrimaryButton type="button" onClick={() => alert('Frontend only')}>
                                        Upload
                                    </PrimaryButton>
                                </div>

                                <DataTable
                                    rows={employee.documents ?? []}
                                    rowKey={(d) => d.id}
                                    emptyState="No documents uploaded."
                                    columns={[
                                        {
                                            key: 'name',
                                            header: 'Document',
                                            cell: (d) => (
                                                <div className="min-w-0">
                                                    <div className="truncate font-semibold text-slate-900">{d.name}</div>
                                                    <div className="mt-0.5 text-xs text-slate-500">{d.type} • {d.size}</div>
                                                </div>
                                            ),
                                        },
                                        { key: 'uploadedAt', header: 'Uploaded', cell: (d) => d.uploadedAt },
                                        {
                                            key: 'actions',
                                            header: 'Actions',
                                            align: 'right',
                                            cellClassName: 'px-4 py-3 text-right',
                                            cell: () => (
                                                <div className="flex items-center justify-end gap-3">
                                                    <button
                                                        type="button"
                                                        onClick={() => alert('Frontend only')}
                                                        className="font-semibold text-amber-700 hover:text-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:ring-offset-2 rounded"
                                                    >
                                                        Download
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => alert('Frontend only')}
                                                        className="font-semibold text-rose-700 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-500/30 focus:ring-offset-2 rounded"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            ),
                                        },
                                    ]}
                                />
                            </div>
                        )}

                        {tab === 'history' && (
                            <div className="space-y-3">
                                <div className="text-sm font-semibold text-slate-900">Employment History</div>
                                <div className="space-y-3">
                                    {(employee.employmentHistory ?? []).map((item) => (
                                        <div key={item.id} className="rounded-2xl border border-slate-200 bg-white p-4">
                                            <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                                <div className="font-semibold text-slate-900">{item.title}</div>
                                                <div className="text-sm text-slate-600">{item.date}</div>
                                            </div>
                                            <div className="mt-2 text-sm text-slate-700">{item.detail}</div>
                                        </div>
                                    ))}
                                    {(employee.employmentHistory ?? []).length === 0 && (
                                        <div className="text-sm text-slate-600">No history entries.</div>
                                    )}
                                </div>
                            </div>
                        )}

                        {tab === 'notes' && (
                            <div className="space-y-3">
                                <div className="text-sm font-semibold text-slate-900">Notes</div>
                                <div className="space-y-3">
                                    {(employee.notes ?? []).map((note) => (
                                        <div key={note.id} className="rounded-2xl border border-slate-200 bg-white p-4">
                                            <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                                <div className="font-semibold text-slate-900">{note.author}</div>
                                                <div className="text-sm text-slate-600">{note.date}</div>
                                            </div>
                                            <div className="mt-2 text-sm text-slate-700">{note.body}</div>
                                        </div>
                                    ))}
                                    {(employee.notes ?? []).length === 0 && (
                                        <div className="text-sm text-slate-600">No notes yet.</div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
