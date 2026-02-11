import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import PageHeader from '@/Components/UI/PageHeader';
import DataTable from '@/Components/UI/DataTable';
import Badge, { toneFromStatus } from '@/Components/UI/Badge';
import Pagination from '@/Components/UI/Pagination';
import { dummyEmployees, departmentOptions, statusOptions } from '@/data/dummyEmployees';
import { Head, Link } from '@inertiajs/react';
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

function FilterChip({ active, children, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={
                'inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold ring-1 ring-inset transition focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:ring-offset-2 ' +
                (active
                    ? 'bg-amber-50 text-amber-800 ring-amber-200'
                    : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50')
            }
        >
            {children}
        </button>
    );
}

export default function Index({ auth }) {
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('All');
    const [department, setDepartment] = useState('All');
    const [page, setPage] = useState(1);

    const pageSize = 6;
    const pages = 5;

    const filtered = useMemo(() => {
        const q = String(query || '').trim().toLowerCase();
        return dummyEmployees.filter((e) => {
            const matchesQuery =
                q === '' ||
                e.fullName.toLowerCase().includes(q) ||
                e.email.toLowerCase().includes(q) ||
                e.employeeId.toLowerCase().includes(q);

            const matchesStatus = status === 'All' ? true : e.status === status;
            const matchesDept = department === 'All' ? true : e.department === department;
            return matchesQuery && matchesStatus && matchesDept;
        });
    }, [query, status, department]);

    const paged = useMemo(() => {
        const start = (page - 1) * pageSize;
        return filtered.slice(start, start + pageSize);
    }, [filtered, page]);

    return (
        <AuthenticatedLayout user={auth.user} header="Employees" contentClassName="max-w-none">
            <Head title="Employees" />

            <div className="w-full space-y-4">
                <PageHeader
                    title="Employees"
                    subtitle="Frontend-only UI with dummy data."
                    actions={
                        <Link href="/employees/create">
                            <PrimaryButton type="button">Add Employee</PrimaryButton>
                        </Link>
                    }
                />

                <div className="rounded-2xl border border-slate-200/70 bg-white/80 backdrop-blur shadow-lg shadow-slate-900/5 p-4">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <TextInput
                            value={query}
                            onChange={(e) => {
                                setQuery(e.target.value);
                                setPage(1);
                            }}
                            placeholder="Search employees…"
                            className="w-full lg:max-w-md"
                            aria-label="Search employees"
                        />

                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="text-xs font-semibold text-slate-600">Status</span>
                                {statusOptions.map((opt) => (
                                    <FilterChip
                                        key={opt}
                                        active={status === opt}
                                        onClick={() => {
                                            setStatus(opt);
                                            setPage(1);
                                        }}
                                    >
                                        {opt}
                                    </FilterChip>
                                ))}
                            </div>

                            <div className="flex flex-wrap items-center gap-2">
                                <span className="text-xs font-semibold text-slate-600">Department</span>
                                {departmentOptions.map((opt) => (
                                    <FilterChip
                                        key={opt}
                                        active={department === opt}
                                        onClick={() => {
                                            setDepartment(opt);
                                            setPage(1);
                                        }}
                                    >
                                        {opt}
                                    </FilterChip>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                <DataTable
                    rows={paged}
                    rowKey={(e) => e.id}
                    emptyState={query ? 'No employees match your search.' : 'No employees yet.'}
                    columns={[
                        {
                            key: 'employee',
                            header: 'Employee',
                            cell: (e) => (
                                <div className="flex items-center gap-3">
                                    <div className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-800 ring-1 ring-amber-200">
                                        <span className="text-xs font-semibold">{initials(e.fullName)}</span>
                                    </div>
                                    <div className="min-w-0">
                                        <div className="truncate font-semibold text-slate-900">{e.fullName}</div>
                                        <div className="truncate text-xs text-slate-500">{e.email}</div>
                                    </div>
                                </div>
                            ),
                        },
                        {
                            key: 'department',
                            header: 'Department',
                            cell: (e) => <span className="font-medium text-slate-800">{e.department}</span>,
                        },
                        {
                            key: 'position',
                            header: 'Position',
                            cell: (e) => e.position,
                        },
                        {
                            key: 'status',
                            header: 'Status',
                            cell: (e) => <Badge tone={toneFromStatus(e.status)}>{e.status}</Badge>,
                        },
                        {
                            key: 'hireDate',
                            header: 'Hire Date',
                            cell: (e) => e.hireDate,
                        },
                        {
                            key: 'actions',
                            header: 'Actions',
                            align: 'right',
                            cellClassName: 'px-4 py-3 text-right',
                            cell: (e) => (
                                <div className="flex items-center justify-end gap-3">
                                    <Link
                                        href={`/employees/${e.id}`}
                                        className="font-semibold text-amber-700 hover:text-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:ring-offset-2 rounded"
                                    >
                                        View
                                    </Link>
                                    <Link
                                        href={`/employees/${e.id}/edit`}
                                        className="font-semibold text-slate-700 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:ring-offset-2 rounded"
                                    >
                                        Edit
                                    </Link>
                                </div>
                            ),
                        },
                    ]}
                />

                <div className="rounded-2xl border border-slate-200/70 bg-white/80 backdrop-blur shadow-lg shadow-slate-900/5 p-4">
                    <Pagination page={page} pages={pages} onPageChange={setPage} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
