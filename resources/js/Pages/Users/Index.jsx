import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import Table from '@/Components/Table';
import UserCreate from '@/Pages/Users/Create';
import UserEdit from '@/Pages/Users/Edit';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function Index({ auth, users, roles = [], filters = {} }) {
    const [query, setQuery] = useState(filters.q ?? '');
    const [perPage, setPerPage] = useState(filters.per_page ?? 10);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create');
    const [editingUser, setEditingUser] = useState(null);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deletingUser, setDeletingUser] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deletePhase, setDeletePhase] = useState('confirm');
    const [isLoading, setIsLoading] = useState(false);

    const flash = usePage().props.flash;

    const userItems = users?.data ?? [];

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
            if (pathname.startsWith('/users')) setIsLoading(true);
        });

        const unsubscribeFinish = router.on('finish', (event) => {
            const visit = event?.detail?.visit;
            const pathname = parsePathname(visit?.url);
            if (pathname.startsWith('/users')) setIsLoading(false);
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
                route('users.index'),
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
            route('users.index'),
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
        setEditingUser(null);
        setIsModalOpen(true);
    };

    const openEdit = (userRecord) => {
        setModalMode('edit');
        setEditingUser(userRecord);
        setIsModalOpen(true);
    };

    const onDelete = (userRecord) => {
        setDeletingUser(userRecord);
        setDeletePhase('confirm');
        setIsDeleteModalOpen(true);
    };

    const closeDeleteModal = () => {
        if (isDeleting) return;
        setIsDeleteModalOpen(false);
        setDeletingUser(null);
        setDeletePhase('confirm');
    };

    const confirmDelete = () => {
        if (!deletingUser?.id) return;

        router.delete(route('users.destroy', deletingUser.id), {
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
        if (userItems.length === 0 && (query ?? '') !== '') return 'No users match your search.';
        if (userItems.length === 0) return 'No users yet.';
        return null;
    }, [userItems.length, query]);

    return (
        <AuthenticatedLayout user={auth.user} header="Users" contentClassName="max-w-none">
            <Head title="Users" />

            <div className="w-full space-y-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="text-sm text-slate-600"></div>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <TextInput
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder="Search users…"
                            className="w-full sm:w-72"
                            aria-label="Search users"
                        />
                        <PrimaryButton
                            className="shrink-0"
                            type="button"
                            onClick={() => openCreate()}
                            disabled={isModalOpen && modalMode === 'create'}
                        >
                            Create User
                        </PrimaryButton>
                    </div>
                </div>

                <Table
                    loading={isLoading}
                    loadingText="Loading users…"
                    columns={[
                        { key: 'name', label: 'Name' },
                        { key: 'email', label: 'Email' },
                        { key: 'role', label: 'Role' },
                        { key: 'action', label: 'Action', align: 'right' },
                    ]}
                    items={userItems}
                    rowKey={(user) => user.id}
                    emptyState={emptyState}
                    pagination={{
                        meta: users?.meta ?? users,
                        links: users?.links ?? users?.meta?.links ?? [],
                        perPage,
                        onPerPageChange,
                    }}
                    renderRow={(user) => (
                        <tr>
                            <td className="px-4 py-3 text-sm font-medium text-slate-900">{user.name}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">{user.email}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">{user.role ?? 'admin'}</td>
                            <td className="px-4 py-3 text-right text-sm">
                                <div className="flex items-center justify-end gap-3">
                                    <button
                                        type="button"
                                        onClick={() => openEdit(user)}
                                        className="font-medium text-amber-700 hover:text-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:ring-offset-2 rounded"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => onDelete(user)}
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
                        {modalMode === 'create' ? 'Create User' : 'Edit User'}
                    </h2>

                    <div className="mt-5">
                        {modalMode === 'create' ? (
                            <UserCreate
                                auth={auth}
                                roles={roles}
                                inModal={true}
                                onCancel={closeModal}
                                onSuccess={closeModal}
                            />
                        ) : (
                            <UserEdit
                                auth={auth}
                                userRecord={editingUser}
                                roles={roles}
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
                            <h2 className="text-lg font-semibold text-gray-900">User Deleted</h2>
                            <p className="mt-2 text-sm text-gray-600">User deleted successfully.</p>

                            <div className="mt-6 flex items-center justify-end gap-3">
                                <SecondaryButton type="button" onClick={closeDeleteModal}>
                                    Close
                                </SecondaryButton>
                            </div>
                        </>
                    ) : (
                        <>
                            <h2 className="text-lg font-semibold text-gray-900">Delete User</h2>
                            <p className="mt-2 text-sm text-gray-600">
                                Are you sure you want to delete{' '}
                                <span className="font-medium text-gray-900">{deletingUser?.name ?? 'this user'}</span>?
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
