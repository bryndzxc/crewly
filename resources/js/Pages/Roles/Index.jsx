import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import Table from '@/Components/Table';
import RoleCreate from '@/Pages/Roles/Create';
import RoleEdit from '@/Pages/Roles/Edit';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function Index({ auth, roles, filters = {} }) {
    const flash = usePage().props.flash;
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create');
    const [editingRole, setEditingRole] = useState(null);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deletingRole, setDeletingRole] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deletePhase, setDeletePhase] = useState('confirm');
    const [perPage, setPerPage] = useState(filters.per_page ?? 10);
    const [query, setQuery] = useState(filters.q ?? '');
    const [isLoading, setIsLoading] = useState(false);

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
            if (pathname.startsWith('/roles')) setIsLoading(true);
        });

        const unsubscribeFinish = router.on('finish', (event) => {
            const visit = event?.detail?.visit;
            const pathname = parsePathname(visit?.url);
            if (pathname.startsWith('/roles')) setIsLoading(false);
        });

        return () => {
            if (typeof unsubscribeStart === 'function') unsubscribeStart();
            if (typeof unsubscribeFinish === 'function') unsubscribeFinish();
        };
    }, []);

    const openCreate = () => {
        setModalMode('create');
        setEditingRole(null);
        setIsModalOpen(true);
    };

    const openEdit = (role) => {
        setModalMode('edit');
        setEditingRole(role);
        setIsModalOpen(true);
    };

    const onDelete = (role) => {
        setDeletingRole(role);
        setDeletePhase('confirm');
        setIsDeleteModalOpen(true);
    };

    const closeDeleteModal = () => {
        if (isDeleting) return;
        setIsDeleteModalOpen(false);
        setDeletingRole(null);
        setDeletePhase('confirm');
    };

    const confirmDelete = () => {
        if (!deletingRole?.id) return;

        router.delete(route('roles.destroy', deletingRole.id), {
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

    const roleItems = roles?.data ?? [];

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
                route('roles.index'),
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
            route('roles.index'),
            { q: query, per_page: nextPerPage, page: 1 },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const emptyState = useMemo(() => {
        if (roleItems.length === 0 && (query ?? '') !== '') return 'No roles match your search.';
        if (roleItems.length === 0) return 'No roles yet.';
        return null;
    }, [roleItems.length, query]);

    return (
        <AuthenticatedLayout user={auth.user} header="Roles" contentClassName="max-w-none">
            <Head title="Roles" />

            <div className="w-full space-y-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="text-sm text-slate-600"></div>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <TextInput
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder="Search roles…"
                            className="w-full sm:w-72"
                            aria-label="Search roles"
                        />
                        <PrimaryButton
                            className="shrink-0"
                            type="button"
                            onClick={() => openCreate()}
                            disabled={isModalOpen && modalMode === 'create'}
                        >
                            Create Role
                        </PrimaryButton>
                    </div>
                </div>

                <Table
                    loading={isLoading}
                    loadingText="Loading roles…"
                    columns={[
                        { key: 'name', label: 'Name' },
                        { key: 'key', label: 'Key' },
                        { key: 'action', label: 'Action', align: 'right' },
                    ]}
                    items={roleItems}
                    rowKey={(role) => role.id}
                    emptyState={emptyState}
                    pagination={{
                        meta: roles?.meta ?? roles,
                        links: roles?.links ?? roles?.meta?.links ?? [],
                        perPage,
                        onPerPageChange,
                    }}
                    renderRow={(role) => (
                        <tr>
                            <td className="px-4 py-3 text-sm font-medium text-slate-900">{role.name}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">{role.key}</td>
                            <td className="px-4 py-3 text-right text-sm">
                                <div className="flex items-center justify-end gap-3">
                                    <button
                                        type="button"
                                        onClick={() => openEdit(role)}
                                        className="font-medium text-amber-700 hover:text-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:ring-offset-2 rounded"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => onDelete(role)}
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
                        {modalMode === 'create' ? 'Create Role' : 'Edit Role'}
                    </h2>

                    <div className="mt-5">
                        {modalMode === 'create' ? (
                            <RoleCreate auth={auth} inModal={true} onCancel={closeModal} onSuccess={closeModal} />
                        ) : (
                            <RoleEdit auth={auth} role={editingRole} inModal={true} onCancel={closeModal} onSuccess={closeModal} />
                        )}
                    </div>
                </div>
            </Modal>

            <Modal show={isDeleteModalOpen} onClose={closeDeleteModal} maxWidth="md">
                <div className="p-6">
                    {deletePhase === 'success' ? (
                        <>
                            <h2 className="text-lg font-semibold text-gray-900">Role Deleted</h2>
                            <p className="mt-2 text-sm text-gray-600">Role deleted successfully.</p>

                            <div className="mt-6 flex items-center justify-end gap-3">
                                <SecondaryButton type="button" onClick={closeDeleteModal}>
                                    Close
                                </SecondaryButton>
                            </div>
                        </>
                    ) : (
                        <>
                            <h2 className="text-lg font-semibold text-gray-900">Delete Role</h2>
                            <p className="mt-2 text-sm text-gray-600">
                                Are you sure you want to delete{' '}
                                <span className="font-medium text-gray-900">{deletingRole?.name ?? 'this role'}</span>?
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
