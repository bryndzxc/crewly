import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Table from '@/Components/Table';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

function formatDate(iso) {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return String(iso);
    }
}

export default function DeveloperFeedbackIndex({ auth, feedback }) {
    const data = feedback?.data || [];

    const [viewerOpen, setViewerOpen] = useState(false);
    const [viewerAttachment, setViewerAttachment] = useState(null);
    const [zoom, setZoom] = useState(1);

    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleteAttachment, setDeleteAttachment] = useState(null);

    const openViewer = (attachment) => {
        setViewerAttachment(attachment);
        setZoom(1);
        setViewerOpen(true);
    };

    const closeViewer = () => {
        setViewerOpen(false);
        setViewerAttachment(null);
        setZoom(1);
    };

    const openDelete = (attachment) => {
        setDeleteAttachment(attachment);
        setDeleteOpen(true);
    };

    const closeDelete = () => {
        setDeleteOpen(false);
        setDeleteAttachment(null);
    };

    const confirmDelete = () => {
        if (!deleteAttachment?.id) return;

        router.delete(route('developer.feedback_attachments.destroy', deleteAttachment.id), {
            preserveScroll: true,
            onFinish: () => {
                closeDelete();
                router.reload({ preserveScroll: true });
            },
        });
    };

    const isImage = (a) => String(a?.mime_type || '').toLowerCase().startsWith('image/');
    const formatBytes = (bytes) => {
        const size = Number(bytes || 0);
        if (!Number.isFinite(size) || size <= 0) return '';
        const kb = size / 1024;
        if (kb < 1024) return `${kb.toFixed(0)} KB`;
        const mb = kb / 1024;
        return `${mb.toFixed(1)} MB`;
    };

    const viewerSrc = useMemo(() => {
        if (!viewerAttachment?.id) return '';
        return route('developer.feedback_attachments.view', viewerAttachment.id);
    }, [viewerAttachment?.id]);

    const meta =
        feedback?.meta ??
        (feedback
            ? {
                  from: feedback.from ?? 0,
                  to: feedback.to ?? 0,
                  total: feedback.total ?? 0,
                  current_page: feedback.current_page ?? 1,
                  last_page: feedback.last_page ?? 1,
              }
            : null);

    const links = Array.isArray(feedback?.links)
        ? feedback.links
        : Array.isArray(feedback?.meta?.links)
          ? feedback.meta.links
          : [];

    return (
        <AuthenticatedLayout user={auth.user} header="Concerns Inbox" contentClassName="max-w-none">
            <Head title="Concerns Inbox" />

            <div className="w-full space-y-4">
                <Table
                    columns={[
                        { key: 'company', label: 'Company' },
                        { key: 'user', label: 'User' },
                        { key: 'message', label: 'Message' },
                        { key: 'created', label: 'Created' },
                    ]}
                    items={data}
                    rowKey={(f) => f.id}
                    emptyState="No concerns yet."
                    pagination={meta ? { meta, links, perPage: feedback?.per_page ?? meta?.per_page } : null}
                    renderRow={(f) => (
                        <tr className="hover:bg-amber-50/40">
                            <td className="px-4 py-3 text-sm text-slate-700">{f.company?.name || '-'}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">{f.user?.name || '-'}</td>
                            <td className="px-4 py-3 text-sm text-slate-900 whitespace-pre-wrap">
                                <div>{String(f.message || '')}</div>
                                {(Array.isArray(f.attachments) ? f.attachments : []).length > 0 ? (
                                    <div className="mt-2">
                                        <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Attachments</div>
                                        <div className="mt-1 space-y-2">
                                            {(f.attachments ?? []).map((a) => (
                                                <div key={a.id} className="flex items-center justify-between gap-3">
                                                    <div className="min-w-0">
                                                        <div className="truncate text-sm text-slate-900">
                                                            {a.original_name || 'attachment'}
                                                            {!!a.file_size ? (
                                                                <span className="ml-2 text-xs text-slate-500">{formatBytes(a.file_size)}</span>
                                                            ) : null}
                                                        </div>
                                                        {!!a.mime_type ? (
                                                            <div className="text-xs text-slate-500 truncate">{a.mime_type}</div>
                                                        ) : null}
                                                    </div>

                                                    <div className="flex items-center gap-2 shrink-0">
                                                        {isImage(a) ? (
                                                            <button
                                                                type="button"
                                                                className="text-sm font-semibold text-amber-700 hover:text-amber-800 underline"
                                                                onClick={() => openViewer(a)}
                                                            >
                                                                View
                                                            </button>
                                                        ) : null}

                                                        <a
                                                            className="text-sm font-semibold text-amber-700 hover:text-amber-800 underline"
                                                            href={route('developer.feedback_attachments.download', a.id)}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                        >
                                                            Download
                                                        </a>

                                                        <button
                                                            type="button"
                                                            className="text-sm font-semibold text-red-600 hover:text-red-700 underline"
                                                            onClick={() => openDelete(a)}
                                                        >
                                                            Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ) : null}
                            </td>
                            <td className="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">
                                {formatDate(f.created_at)}
                            </td>
                        </tr>
                    )}
                />
            </div>

            <Modal show={viewerOpen} maxWidth="6xl" onClose={closeViewer}>
                <div className="px-6 py-4 border-b border-slate-200 flex items-start justify-between gap-4">
                    <div className="min-w-0">
                        <div className="text-lg font-semibold text-slate-900">Attachment preview</div>
                        <div className="text-sm text-slate-600 truncate">
                            {viewerAttachment?.original_name || 'attachment'}
                        </div>
                    </div>
                    <SecondaryButton type="button" onClick={closeViewer}>
                        Close
                    </SecondaryButton>
                </div>

                <div className="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <SecondaryButton type="button" onClick={() => setZoom((z) => Math.max(0.5, Number((z - 0.25).toFixed(2))))}>
                            -
                        </SecondaryButton>
                        <div className="text-sm text-slate-700 w-24 text-center">{Math.round(zoom * 100)}%</div>
                        <SecondaryButton type="button" onClick={() => setZoom((z) => Math.min(4, Number((z + 0.25).toFixed(2))))}>
                            +
                        </SecondaryButton>
                        <SecondaryButton type="button" onClick={() => setZoom(1)}>
                            Reset
                        </SecondaryButton>
                    </div>

                    {viewerAttachment?.id ? (
                        <a
                            className="text-sm font-semibold text-amber-700 hover:text-amber-800 underline"
                            href={route('developer.feedback_attachments.download', viewerAttachment.id)}
                            target="_blank"
                            rel="noreferrer"
                        >
                            Download
                        </a>
                    ) : null}
                </div>

                <div className="p-6">
                    <div className="rounded-lg border border-slate-200 bg-slate-50 overflow-auto max-h-[70vh]">
                        {viewerSrc ? (
                            <div className="p-4 flex items-center justify-center">
                                <img
                                    src={viewerSrc}
                                    alt={viewerAttachment?.original_name || 'attachment'}
                                    className="max-w-none"
                                    style={{ transform: `scale(${zoom})`, transformOrigin: 'center center' }}
                                />
                            </div>
                        ) : (
                            <div className="p-6 text-sm text-slate-600">No attachment selected.</div>
                        )}
                    </div>
                </div>
            </Modal>

            <Modal show={deleteOpen} maxWidth="md" onClose={closeDelete}>
                <div className="p-6 space-y-4">
                    <div>
                        <div className="text-lg font-semibold text-slate-900">Delete attachment?</div>
                        <div className="mt-1 text-sm text-slate-600">
                            This will permanently remove the file from Crewly.
                        </div>
                    </div>

                    <div className="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        {deleteAttachment?.original_name || 'attachment'}
                    </div>

                    <div className="flex items-center justify-end gap-2">
                        <SecondaryButton type="button" onClick={closeDelete}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="button" className="bg-red-600 hover:bg-red-700 focus:bg-red-700 active:bg-red-800" onClick={confirmDelete}>
                            Delete
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
