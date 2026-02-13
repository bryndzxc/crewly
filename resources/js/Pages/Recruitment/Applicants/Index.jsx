import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Table from '@/Components/Table';
import Badge from '@/Components/UI/Badge';
import PageHeader from '@/Components/UI/PageHeader';
import ApplicantCreate from '@/Pages/Recruitment/Applicants/Create';
import ApplicantEdit from '@/Pages/Recruitment/Applicants/Edit';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

function fullName(applicant) {
    const parts = [applicant?.first_name, applicant?.middle_name, applicant?.last_name, applicant?.suffix]
        .map((v) => String(v || '').trim())
        .filter(Boolean);
    return parts.join(' ');
}

function toneForStage(stage) {
    const s = String(stage || '').toUpperCase();
    if (s === 'HIRED') return 'success';
    if (s === 'REJECTED' || s === 'WITHDRAWN') return 'danger';
    if (s === 'OFFER' || s === 'INTERVIEW') return 'amber';
    return 'neutral';
}

export default function Index({ auth, applicants, positions = [], filters = {}, stages = [], can = {} }) {
    const flash = usePage().props.flash;

    const [stage, setStage] = useState(filters.stage ?? '');
    const [positionId, setPositionId] = useState(filters.position_id ?? '');
    const [isLoading, setIsLoading] = useState(false);

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState('create');
    const [editingApplicant, setEditingApplicant] = useState(null);

    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deletingApplicant, setDeletingApplicant] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [deletePhase, setDeletePhase] = useState('confirm');

    const items = applicants?.data ?? [];

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
            if (pathname.startsWith('/recruitment')) setIsLoading(true);
        });

        const unsubscribeFinish = router.on('finish', (event) => {
            const visit = event?.detail?.visit;
            const pathname = parsePathname(visit?.url);
            if (pathname.startsWith('/recruitment')) setIsLoading(false);
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

    const applyFilters = (next) => {
        router.get(
            route('recruitment.applicants.index'),
            {
                stage: next.stage ?? stage,
                position_id: next.position_id ?? positionId,
                page: 1,
            },
            { preserveScroll: true, preserveState: true, replace: true }
        );
    };

    const openCreate = () => {
        setModalMode('create');
        setEditingApplicant(null);
        setIsModalOpen(true);
    };

    const openEdit = (applicant) => {
        setModalMode('edit');
        setEditingApplicant(applicant);
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingApplicant(null);
    };

    const onDelete = (applicant) => {
        setDeletingApplicant(applicant);
        setDeletePhase('confirm');
        setIsDeleteModalOpen(true);
    };

    const closeDeleteModal = () => {
        if (isDeleting) return;
        setIsDeleteModalOpen(false);
        setDeletingApplicant(null);
        setDeletePhase('confirm');
    };

    const confirmDelete = () => {
        if (!deletingApplicant?.id) return;

        router.delete(route('recruitment.applicants.destroy', deletingApplicant.id), {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setIsDeleting(true),
            onFinish: () => setIsDeleting(false),
            onSuccess: () => setDeletePhase('success'),
        });
    };

    const emptyState = useMemo(() => {
        if (items.length === 0 && (stage || positionId)) return 'No applicants match your filters.';
        if (items.length === 0) return 'No applicants yet.';
        return null;
    }, [items.length, stage, positionId]);

    return (
        <AuthenticatedLayout user={auth.user} header="Recruitment" contentClassName="max-w-none">
            <Head title="Applicants" />

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
                    title="Applicants"
                    subtitle="Filter by stage and position (no partial search on encrypted fields)."
                    actions={
                        can?.recruitmentManage ? (
                            <PrimaryButton type="button" onClick={openCreate}>
                                Create Applicant
                            </PrimaryButton>
                        ) : null
                    }
                />

                <div className="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                Stage
                            </label>
                            <select
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={stage}
                                onChange={(e) => {
                                    const v = e.target.value;
                                    setStage(v);
                                    applyFilters({ stage: v });
                                }}
                            >
                                <option value="">All</option>
                                {(stages ?? []).map((s) => (
                                    <option key={s} value={s}>
                                        {s}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                Position
                            </label>
                            <select
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={positionId}
                                onChange={(e) => {
                                    const v = e.target.value;
                                    setPositionId(v);
                                    applyFilters({ position_id: v });
                                }}
                            >
                                <option value="">All</option>
                                {(positions ?? []).map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.title} {p.status === 'CLOSED' ? '(Closed)' : ''}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="sm:col-span-2 lg:col-span-2 flex items-end justify-end gap-2">
                            <Link
                                href={route('recruitment.positions.index')}
                                className="text-sm font-medium text-slate-700 hover:text-slate-900"
                            >
                                View Positions
                            </Link>
                        </div>
                    </div>
                </div>

                <Table
                    loading={isLoading}
                    loadingText="Loading applicants…"
                    columns={[
                        { key: 'name', label: 'Applicant' },
                        { key: 'position', label: 'Position' },
                        { key: 'stage', label: 'Stage' },
                        { key: 'applied_at', label: 'Applied' },
                        { key: 'last_activity_at', label: 'Last Activity' },
                        { key: 'action', label: 'Action', align: 'right' },
                    ]}
                    items={items}
                    rowKey={(a) => a.id}
                    emptyState={emptyState}
                    pagination={{
                        meta: applicants?.meta ?? applicants,
                        links: applicants?.links ?? applicants?.meta?.links ?? [],
                        perPage: 15,
                    }}
                    renderRow={(a) => (
                        <tr className="hover:bg-amber-50/40">
                            <td className="px-4 py-3 text-sm text-slate-900 font-medium">
                                <div className="truncate max-w-[320px]" title={fullName(a)}>
                                    {fullName(a) || '—'}
                                </div>
                                <div className="mt-1 text-xs text-slate-500 truncate max-w-[320px]">
                                    {a?.email ? a.email : 'No email'}
                                </div>
                            </td>
                            <td className="px-4 py-3 text-sm text-slate-700">{a?.position?.title ?? '—'}</td>
                            <td className="px-4 py-3">
                                <Badge tone={toneForStage(a?.stage)}>{a?.stage ?? '—'}</Badge>
                            </td>
                            <td className="px-4 py-3 text-sm text-slate-700">{a?.applied_at ?? '—'}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">{a?.last_activity_at ?? '—'}</td>
                            <td className="px-4 py-3 text-sm text-right whitespace-nowrap">
                                <Link
                                    href={route('recruitment.applicants.show', a.id)}
                                    className="text-amber-700 hover:text-amber-900 font-medium"
                                >
                                    View
                                </Link>
                                {can?.recruitmentManage && (
                                    <>
                                        <button
                                            type="button"
                                            className="ml-4 text-slate-700 hover:text-slate-900 font-medium"
                                            onClick={() => openEdit(a)}
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            className="ml-4 text-rose-700 hover:text-rose-900 font-medium"
                                            onClick={() => onDelete(a)}
                                        >
                                            Delete
                                        </button>
                                    </>
                                )}
                            </td>
                        </tr>
                    )}
                />
            </div>

            <Modal show={isDeleteModalOpen} onClose={closeDeleteModal} maxWidth="md">
                <div className="p-6">
                    {deletePhase === 'success' ? (
                        <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                            Applicant deleted successfully.
                        </div>
                    ) : (
                        <>
                            <div className="text-lg font-semibold text-slate-900">Delete applicant</div>
                            <div className="mt-1 text-sm text-slate-600">
                                This will permanently delete the applicant and related ATS records.
                            </div>
                            <div className="mt-5 flex items-center justify-end gap-2">
                                <SecondaryButton type="button" onClick={closeDeleteModal} disabled={isDeleting}>
                                    Cancel
                                </SecondaryButton>
                                <DangerButton type="button" onClick={confirmDelete} disabled={isDeleting}>
                                    {isDeleting ? 'Deleting…' : 'Delete'}
                                </DangerButton>
                            </div>
                        </>
                    )}
                </div>
            </Modal>

            <Modal show={isModalOpen} onClose={closeModal} maxWidth="lg">
                <div className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900">
                        {modalMode === 'create' ? 'Create Applicant' : 'Edit Applicant'}
                    </h2>

                    <div className="mt-5">
                        {modalMode === 'create' ? (
                            <ApplicantCreate
                                auth={auth}
                                positions={positions}
                                stages={stages}
                                inModal={true}
                                returnTo="index"
                                onCancel={closeModal}
                                onSuccess={closeModal}
                            />
                        ) : (
                            <ApplicantEdit
                                auth={auth}
                                applicant={editingApplicant}
                                positions={positions}
                                stages={stages}
                                inModal={true}
                                returnTo="index"
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
