import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Table from '@/Components/Table';
import Badge from '@/Components/UI/Badge';
import PageHeader from '@/Components/UI/PageHeader';
import PositionCreate from '@/Pages/Recruitment/Positions/Create';
import PositionEdit from '@/Pages/Recruitment/Positions/Edit';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

function toneForStatus(status) {
    return String(status || '').toUpperCase() === 'OPEN' ? 'success' : 'neutral';
}

export default function Index({ auth, positions, can = {} }) {
    const flash = usePage().props.flash;
    const items = positions?.data ?? [];

    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deleting, setDeleting] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create');
    const [editingPosition, setEditingPosition] = useState(null);

    useEffect(() => {
        if (!isDeleteModalOpen) return;
        const onEsc = (e) => {
            if (e.key === 'Escape') setIsDeleteModalOpen(false);
        };
        window.addEventListener('keydown', onEsc);
        return () => window.removeEventListener('keydown', onEsc);
    }, [isDeleteModalOpen]);

    const emptyState = useMemo(() => {
        if (items.length === 0) return 'No positions yet.';
        return null;
    }, [items.length]);

    const openCreate = () => {
        setModalMode('create');
        setEditingPosition(null);
        setIsModalOpen(true);
    };

    const openEdit = (pos) => {
        setModalMode('edit');
        setEditingPosition(pos);
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingPosition(null);
    };

    const openDelete = (pos) => {
        setDeleting(pos);
        setIsDeleteModalOpen(true);
    };
    const closeDelete = () => {
        if (isDeleting) return;
        setIsDeleteModalOpen(false);
        setDeleting(null);
    };
    const confirmDelete = () => {
        if (!deleting?.id) return;
        router.delete(route('recruitment.positions.destroy', deleting.id), {
            preserveScroll: true,
            onStart: () => setIsDeleting(true),
            onFinish: () => setIsDeleting(false),
            onSuccess: () => closeDelete(),
        });
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Recruitment" contentClassName="max-w-none">
            <Head title="Positions" />

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

                <PageHeader
                    title="Positions"
                    subtitle="Open/closed hiring positions."
                    actions={
                        can?.recruitmentManage ? (
                            <PrimaryButton type="button" onClick={openCreate}>
                                Create Position
                            </PrimaryButton>
                        ) : (
                            <Link href={route('recruitment.applicants.index')}>
                                <SecondaryButton type="button">Back to Applicants</SecondaryButton>
                            </Link>
                        )
                    }
                />

                <Table
                    columns={[
                        { key: 'title', label: 'Title' },
                        { key: 'department', label: 'Department' },
                        { key: 'location', label: 'Location' },
                        { key: 'status', label: 'Status' },
                        { key: 'action', label: 'Action', align: 'right' },
                    ]}
                    items={items}
                    rowKey={(p) => p.id}
                    emptyState={emptyState}
                    pagination={{
                        meta: positions?.meta ?? positions,
                        links: positions?.links ?? positions?.meta?.links ?? [],
                        perPage: 15,
                    }}
                    renderRow={(p) => (
                        <tr className="hover:bg-amber-50/40">
                            <td className="px-4 py-3 text-sm font-medium text-slate-900">{p.title}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">{p.department ?? '—'}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">{p.location ?? '—'}</td>
                            <td className="px-4 py-3">
                                <Badge tone={toneForStatus(p.status)}>{p.status}</Badge>
                            </td>
                            <td className="px-4 py-3 text-sm text-right whitespace-nowrap">
                                {can?.recruitmentManage ? (
                                    <>
                                        <button
                                            type="button"
                                            onClick={() => openEdit(p)}
                                            className="text-amber-700 hover:text-amber-900 font-medium"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            className="ml-4 text-rose-700 hover:text-rose-900 font-medium"
                                            onClick={() => openDelete(p)}
                                        >
                                            Delete
                                        </button>
                                    </>
                                ) : (
                                    <span className="text-slate-500">—</span>
                                )}
                            </td>
                        </tr>
                    )}
                />
            </div>

            <Modal show={isDeleteModalOpen} onClose={closeDelete} maxWidth="md">
                <div className="p-6">
                    <div className="text-lg font-semibold text-slate-900">Delete position</div>
                    <div className="mt-1 text-sm text-slate-600">This will delete the position record.</div>
                    <div className="mt-5 flex items-center justify-end gap-2">
                        <SecondaryButton type="button" onClick={closeDelete} disabled={isDeleting}>
                            Cancel
                        </SecondaryButton>
                        <DangerButton type="button" onClick={confirmDelete} disabled={isDeleting}>
                            {isDeleting ? 'Deleting…' : 'Delete'}
                        </DangerButton>
                    </div>
                </div>
            </Modal>

            <Modal show={isModalOpen} onClose={closeModal} maxWidth="lg">
                <div className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900">
                        {modalMode === 'create' ? 'Create Position' : 'Edit Position'}
                    </h2>

                    <div className="mt-5">
                        {modalMode === 'create' ? (
                            <PositionCreate auth={auth} inModal={true} onCancel={closeModal} onSuccess={closeModal} />
                        ) : (
                            <PositionEdit
                                auth={auth}
                                position={editingPosition}
                                inModal={true}
                                onCancel={closeModal}
                                onSuccess={closeModal}
                            />
                        )}
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
