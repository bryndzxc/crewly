import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import Table from '@/Components/Table';
import DepartmentCreate from '@/Pages/Departments/Create';
import DepartmentEdit from '@/Pages/Departments/Edit';
import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function Index({ auth, departments, filters = {} }) {
    const [query, setQuery] = useState(filters.q ?? '');
    const [perPage, setPerPage] = useState(filters.per_page ?? 10);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create');
    const [editingDepartment, setEditingDepartment] = useState(null);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deletingDepartment, setDeletingDepartment] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deletePhase, setDeletePhase] = useState('confirm');
    const [isLoading, setIsLoading] = useState(false);

    const departmentItems = departments?.data ?? [];

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
            if (pathname.startsWith('/departments')) setIsLoading(true);
        });

        const unsubscribeFinish = router.on('finish', (event) => {
            const visit = event?.detail?.visit;
            const pathname = parsePathname(visit?.url);
            if (pathname.startsWith('/departments')) setIsLoading(false);
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
                route('departments.index'),
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
            route('departments.index'),
            { q: query, per_page: nextPerPage, page: 1 },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const openCreate = () => {
        setModalMode('create');
        setEditingDepartment(null);
        setIsModalOpen(true);
    };

    const openEdit = (department) => {
        setModalMode('edit');
        setEditingDepartment(department);
        setIsModalOpen(true);
    };

    const onDelete = (department) => {
        setDeletingDepartment(department);
        setDeletePhase('confirm');
        setIsDeleteModalOpen(true);
    };

    const closeDeleteModal = () => {
        if (isDeleting) return;
        setIsDeleteModalOpen(false);
        setDeletingDepartment(null);
        setDeletePhase('confirm');
    };

    const confirmDelete = () => {
        if (!deletingDepartment?.department_id) return;

        router.delete(route('departments.destroy', deletingDepartment.department_id), {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setIsDeleting(true),
            onFinish: () => setIsDeleting(false),
            onSuccess: () => setDeletePhase('success'),
        });
    };

    const closeModal = () => {
        setIsModalOpen(false);
    };

    const emptyState = useMemo(() => {
        if (departmentItems.length === 0 && (query ?? '') !== '') return 'No departments match your search.';
        if (departmentItems.length === 0) return 'No departments yet.';
        return null;
    }, [departmentItems.length, query]);

    return (
        <AuthenticatedLayout user={auth.user} header="Departments" contentClassName="max-w-none">
            <Head title="Departments" />

            <div className="w-full space-y-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="text-sm text-slate-600"></div>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <TextInput
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder="Search departments…"
                            className="w-full sm:w-72"
                            aria-label="Search departments"
                        />
                        <PrimaryButton
                            className="shrink-0"
                            type="button"
                            onClick={() => openCreate()}
                            disabled={isModalOpen && modalMode === 'create'}
                        >
                            Create Department
                        </PrimaryButton>
                    </div>
                </div>

                <Table
                    loading={isLoading}
                    loadingText="Loading departments…"
                    columns={[
                        { key: 'name', label: 'Name' },
                        { key: 'code', label: 'Code' },
                        { key: 'action', label: 'Action', align: 'right' },
                    ]}
                    items={departmentItems}
                    rowKey={(department) => department.department_id}
                    emptyState={emptyState}
                    pagination={{
                        meta: departments?.meta ?? departments,
                        links: departments?.links ?? departments?.meta?.links ?? [],
                        perPage,
                        onPerPageChange,
                    }}
                    renderRow={(department) => (
                        <tr>
                            <td className="px-4 py-3 text-sm font-medium text-slate-900">{department.name}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">{department.code}</td>
                            <td className="px-4 py-3 text-right text-sm">
                                <div className="flex items-center justify-end gap-3">
                                    <button
                                        type="button"
                                        onClick={() => openEdit(department)}
                                        className="font-medium text-amber-700 hover:text-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:ring-offset-2 rounded"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => onDelete(department)}
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

            <Modal show={isModalOpen} onClose={closeModal} maxWidth="lg">
                <div className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900">
                        {modalMode === 'create' ? 'Create Department' : 'Edit Department'}
                    </h2>

                    <div className="mt-5">
                        {modalMode === 'create' ? (
                            <DepartmentCreate auth={auth} inModal={true} onCancel={closeModal} onSuccess={closeModal} />
                        ) : (
                            <DepartmentEdit
                                auth={auth}
                                department={editingDepartment}
                                inModal={true}
                                onCancel={closeModal}
                                onSuccess={closeModal}
                            />
                        )}
                    </div>
                </div>
            </Modal>

            <Modal show={isDeleteModalOpen} onClose={closeDeleteModal} maxWidth="md">
                <div className="p-6">
                    {deletePhase === 'success' ? (
                        <>
                            <h2 className="text-lg font-semibold text-gray-900">Department Deleted</h2>
                            <p className="mt-2 text-sm text-gray-600">Department deleted successfully.</p>

                            <div className="mt-6 flex items-center justify-end gap-3">
                                <SecondaryButton type="button" onClick={closeDeleteModal}>
                                    Close
                                </SecondaryButton>
                            </div>
                        </>
                    ) : (
                        <>
                            <h2 className="text-lg font-semibold text-gray-900">Delete Department</h2>
                            <p className="mt-2 text-sm text-gray-600">
                                Are you sure you want to delete{' '}
                                <span className="font-medium text-gray-900">{deletingDepartment?.name ?? 'this department'}</span>?
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
