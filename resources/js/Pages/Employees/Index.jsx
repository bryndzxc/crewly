import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import Table from '@/Components/Table';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

function fullName(employee) {
    const parts = [employee?.first_name, employee?.middle_name, employee?.last_name, employee?.suffix]
        .map((v) => String(v || '').trim())
        .filter(Boolean);

    return parts.join(' ');
}

export default function Index({ auth, employees, departments = [], filters = {} }) {
    const [query, setQuery] = useState(filters.q ?? '');
    const [perPage, setPerPage] = useState(filters.per_page ?? 10);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deletingEmployee, setDeletingEmployee] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deletePhase, setDeletePhase] = useState('confirm');
    const [isLoading, setIsLoading] = useState(false);

    const flash = usePage().props.flash;

    const employeeItems = employees?.data ?? [];

    const departmentNameById = useMemo(() => {
        const map = new Map();
        (departments ?? []).forEach((d) => {
            if (d?.department_id != null) map.set(Number(d.department_id), d.name);
        });
        return map;
    }, [departments]);

    useEffect(() => {
        const parsePathname = (url) => {
            try {
                return new URL(url, window.location.origin).pathname;
            } catch {
                return String(url || '');
            }
        };

        const unsubscribeStart = router.on('start', (event) => {
            const visit = event?.detail?.visit;
            const pathname = parsePathname(visit?.url);
            if (pathname.startsWith('/employees')) setIsLoading(true);
        });

        const unsubscribeFinish = router.on('finish', (event) => {
            const visit = event?.detail?.visit;
            const pathname = parsePathname(visit?.url);
            if (pathname.startsWith('/employees')) setIsLoading(false);
        });

        return () => {
            if (typeof unsubscribeStart === 'function') unsubscribeStart();
            if (typeof unsubscribeFinish === 'function') unsubscribeFinish();
        };
    }, []);

    useEffect(() => {
        if (!isDeleteModalOpen) return;
        if (deletePhase !== 'success') return;

        const timer = setTimeout(() => {
            closeDeleteModal();
        }, 900);

        return () => clearTimeout(timer);
    }, [isDeleteModalOpen, deletePhase]);

    useEffect(() => {
        const handler = setTimeout(() => {
            router.get(
                route('employees.index'),
                { q: query, per_page: perPage, page: 1 },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                }
            );
        }, 300);

        return () => clearTimeout(handler);
    }, [query]);

    const onPerPageChange = (nextPerPage) => {
        setPerPage(nextPerPage);
        router.get(
            route('employees.index'),
            { q: query, per_page: nextPerPage, page: 1 },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const onDelete = (employee) => {
        setDeletingEmployee(employee);
        setDeletePhase('confirm');
        setIsDeleteModalOpen(true);
    };

    const closeDeleteModal = () => {
        if (isDeleting) return;
        setIsDeleteModalOpen(false);
        setDeletingEmployee(null);
        setDeletePhase('confirm');
    };

    const confirmDelete = () => {
        if (!deletingEmployee?.employee_id) return;

        router.delete(route('employees.destroy', deletingEmployee.employee_id), {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setIsDeleting(true),
            onFinish: () => setIsDeleting(false),
            onSuccess: () => setDeletePhase('success'),
        });
    };

    const emptyState = useMemo(() => {
        if (employeeItems.length === 0 && (query ?? '') !== '') return 'No employees match your search.';
        if (employeeItems.length === 0) return 'No employees yet.';
        return null;
    }, [employeeItems.length, query]);

    return (
        <AuthenticatedLayout user={auth.user} header="Employees" contentClassName="max-w-none">
            <Head title="Employees" />

            <div className="w-full space-y-4">
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

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="text-sm text-slate-600"></div>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <TextInput
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder="Search by employee code or name…"
                            className="w-full sm:w-72"
                            aria-label="Search employees"
                        />
                        <Link href={route('employees.create')} className="shrink-0">
                            <PrimaryButton type="button">Create Employee</PrimaryButton>
                        </Link>
                    </div>
                </div>

                <Table
                    loading={isLoading}
                    loadingText="Loading employees…"
                    columns={[
                        { key: 'employee_code', label: 'Employee Code' },
                        { key: 'name', label: 'Name' },
                        { key: 'email', label: 'Email' },
                        { key: 'department', label: 'Department' },
                        { key: 'status', label: 'Status' },
                        { key: 'action', label: 'Action', align: 'right' },
                    ]}
                    items={employeeItems}
                    rowKey={(employee) => employee.employee_id}
                    emptyState={emptyState}
                    pagination={{
                        meta: employees?.meta ?? employees,
                        links: employees?.links ?? employees?.meta?.links ?? [],
                        perPage,
                        onPerPageChange,
                    }}
                    renderRow={(employee) => (
                        <tr>
                            <td className="px-4 py-3 text-sm font-medium text-slate-900">
                                {employee.employee_code}
                            </td>
                            <td className="px-4 py-3 text-sm text-slate-700">
                                <div className="flex items-center gap-3">
                                    {employee?.photo_url ? (
                                        <img
                                            src={employee.photo_url}
                                            alt="Employee photo"
                                            className="h-10 w-10 rounded-full border border-gray-200 object-cover"
                                            loading="lazy"
                                        />
                                    ) : (
                                        <div className="h-10 w-10 rounded-full border border-gray-200 bg-gray-50 flex items-center justify-center text-xs font-semibold text-gray-600">
                                            {(String(employee?.first_name || '').trim().charAt(0) || 'E').toUpperCase()}
                                        </div>
                                    )}
                                    <div className="min-w-0">
                                        <div className="truncate">{fullName(employee)}</div>
                                    </div>
                                </div>
                            </td>
                            <td className="px-4 py-3 text-sm text-slate-700">{employee.email}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">
                                {departmentNameById.get(Number(employee.department_id)) ?? employee.department_id}
                            </td>
                            <td className="px-4 py-3 text-sm text-slate-700">{employee.status ?? '-'}</td>
                            <td className="px-4 py-3 text-right text-sm">
                                <div className="flex items-center justify-end gap-3">
                                    <Link
                                        href={route('employees.show', employee.employee_id)}
                                        className="shrink-0"
                                    >
                                        <SecondaryButton type="button">View</SecondaryButton>
                                    </Link>
                                    <Link
                                        href={route('employees.edit', employee.employee_id)}
                                        className="shrink-0"
                                    >
                                        <SecondaryButton type="button">Edit</SecondaryButton>
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => onDelete(employee)}
                                        className="font-medium text-red-600 hover:text-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/30 focus:ring-offset-2 rounded"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    )}
                />
            </div>

            <Modal show={isDeleteModalOpen} onClose={closeDeleteModal} maxWidth="md">
                <div className="p-6">
                    {deletePhase === 'success' ? (
                        <>
                            <h2 className="text-lg font-semibold text-gray-900">Employee Deleted</h2>
                            <p className="mt-2 text-sm text-gray-600">Employee deleted successfully.</p>

                            <div className="mt-6 flex items-center justify-end gap-3">
                                <SecondaryButton type="button" onClick={closeDeleteModal}>
                                    Close
                                </SecondaryButton>
                            </div>
                        </>
                    ) : (
                        <>
                            <h2 className="text-lg font-semibold text-gray-900">Delete Employee</h2>
                            <p className="mt-2 text-sm text-gray-600">
                                Are you sure you want to delete{' '}
                                <span className="font-medium text-gray-900">
                                    {fullName(deletingEmployee) || deletingEmployee?.employee_code || 'this employee'}
                                </span>
                                ?
                            </p>

                            <div className="mt-6 flex items-center justify-end gap-3">
                                <SecondaryButton type="button" onClick={closeDeleteModal} disabled={isDeleting}>
                                    Cancel
                                </SecondaryButton>
                                <DangerButton type="button" onClick={confirmDelete} disabled={isDeleting}>
                                    Delete
                                </DangerButton>
                            </div>
                        </>
                    )}
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
