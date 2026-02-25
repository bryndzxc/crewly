import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import DatePicker from '@/Components/DatePicker';
import Badge from '@/Components/UI/Badge';
import Tabs from '@/Components/UI/Tabs';
import PageHeader from '@/Components/UI/PageHeader';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

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

export default function Show({ auth, applicant, documents = [], interviews = [], positions = [], departments = [], stages = [], can = {} }) {
    const flash = usePage().props.flash;

    const isHired = String(applicant?.stage || '').toUpperCase() === 'HIRED';

    const stageOptions = useMemo(() => {
        const all = Array.isArray(stages) ? stages : [];
        if (isHired) return all;
        return all.filter((s) => String(s || '').toUpperCase() !== 'HIRED');
    }, [stages, isHired]);

    const [activeTab, setActiveTab] = useState('overview');
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const [isStageUpdating, setIsStageUpdating] = useState(false);

    const stageForm = useForm({ stage: applicant?.stage ?? 'APPLIED' });
    const uploadForm = useForm({ type: 'Resume', files: [], notes: '' });
    const interviewForm = useForm({ scheduled_at: '', notes: '' });
    const hireForm = useForm({
        department_id: departments?.[0]?.department_id ?? '',
        email: applicant?.email ?? '',
        mobile_number: applicant?.mobile_number ?? '',
        position_title: applicant?.position?.title ?? '',
        date_hired: new Date().toISOString().slice(0, 10),
        migrate_resume: true,
    });

    const tabs = useMemo(
        () => [
            { key: 'overview', label: 'Overview' },
            { key: 'documents', label: 'Documents' },
            { key: 'interviews', label: 'Interviews' },
            { key: 'notes', label: 'Notes' },
        ],
        []
    );

    const onUpdateStage = (e) => {
        if (isHired) return;
        const value = e.target.value;
        stageForm.setData('stage', value);
        stageForm.clearErrors('stage');
        // Use router.patch with explicit data to avoid stale state
        // when setData + submit happen in the same tick.
        router.patch(
            route('recruitment.applicants.stage.update', applicant.id),
            { stage: value },
            {
                preserveScroll: true,
                onStart: () => setIsStageUpdating(true),
                onFinish: () => setIsStageUpdating(false),
                onError: (errors) => {
                    if (errors?.stage) stageForm.setError('stage', errors.stage);
                },
            }
        );
    };

    const submitUpload = (e) => {
        e.preventDefault();
        uploadForm.post(route('recruitment.applicants.documents.store', applicant.id), {
            preserveScroll: true,
            onSuccess: () => uploadForm.reset('files', 'notes'),
        });
    };

    const deleteDocument = (docId) => {
        if (!docId) return;
        router.delete(route('recruitment.applicants.documents.destroy', [applicant.id, docId]), {
            preserveScroll: true,
        });
    };

    const submitInterview = (e) => {
        e.preventDefault();
        interviewForm.post(route('recruitment.applicants.interviews.store', applicant.id), {
            preserveScroll: true,
            onSuccess: () => interviewForm.reset('scheduled_at', 'notes'),
        });
    };

    const deleteInterview = (id) => {
        if (!id) return;
        router.delete(route('recruitment.applicants.interviews.destroy', [applicant.id, id]), {
            preserveScroll: true,
        });
    };

    const submitHire = (e) => {
        e.preventDefault();
        hireForm.post(route('recruitment.applicants.hire', applicant.id), {
            preserveScroll: true,
        });
    };

    const openDeleteModal = () => setIsDeleteModalOpen(true);
    const closeDeleteModal = () => {
        if (isDeleting) return;
        setIsDeleteModalOpen(false);
    };
    const confirmDelete = () => {
        router.delete(route('recruitment.applicants.destroy', applicant.id), {
            preserveScroll: true,
            onStart: () => setIsDeleting(true),
            onFinish: () => setIsDeleting(false),
            onSuccess: () => setIsDeleteModalOpen(false),
        });
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Recruitment" contentClassName="max-w-none">
            <Head title={`Applicant: ${fullName(applicant) || 'Applicant'}`} />

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
                    title={
                        <div className="flex flex-wrap items-center gap-3">
                            <span className="truncate">{fullName(applicant) || 'Applicant'}</span>
                            <Badge tone={toneForStage(applicant?.stage)}>{applicant?.stage ?? '—'}</Badge>
                        </div>
                    }
                    subtitle={applicant?.position?.title ? `Position: ${applicant.position.title}` : null}
                    actions={
                        <>
                            <Link href={route('recruitment.applicants.index')}>
                                <SecondaryButton type="button">Back</SecondaryButton>
                            </Link>
                            {can?.recruitmentManage && (
                                <Link href={route('recruitment.applicants.edit', applicant.id)}>
                                    <PrimaryButton type="button">Edit</PrimaryButton>
                                </Link>
                            )}
                        </>
                    }
                />

                <div className="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    <div className="px-6 pt-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <Tabs tabs={tabs} value={activeTab} onChange={setActiveTab} />
                            {can?.recruitmentStageUpdate && (
                                <div className="flex items-center gap-2">
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-600">Stage</div>
                                    <select
                                        className="rounded-md border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                                        value={stageForm.data.stage}
                                        onChange={onUpdateStage}
                                        disabled={stageForm.processing || isStageUpdating || isHired}
                                    >
                                        {stageOptions.map((s) => (
                                            <option key={s} value={s}>
                                                {s}
                                            </option>
                                        ))}
                                    </select>
                                    {isHired && (
                                        <div className="text-xs text-slate-500">Locked (already hired)</div>
                                    )}
                                </div>
                            )}
                        </div>
                        {!!stageForm.errors.stage && (
                            <div className="mt-2 text-sm text-rose-700">{stageForm.errors.stage}</div>
                        )}
                    </div>

                    {activeTab === 'overview' && (
                        <div className="p-6 space-y-6">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Email</div>
                                    <div className="mt-1 text-sm font-medium text-slate-900">{applicant?.email ?? '—'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Mobile</div>
                                    <div className="mt-1 text-sm font-medium text-slate-900">{applicant?.mobile_number ?? '—'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Source</div>
                                    <div className="mt-1 text-sm font-medium text-slate-900">{applicant?.source ?? '—'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Expected Salary</div>
                                    <div className="mt-1 text-sm font-medium text-slate-900">{applicant?.expected_salary ?? '—'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Applied At</div>
                                    <div className="mt-1 text-sm font-medium text-slate-900">{applicant?.applied_at ?? '—'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Last Activity</div>
                                    <div className="mt-1 text-sm font-medium text-slate-900">{applicant?.last_activity_at ?? '—'}</div>
                                </div>
                            </div>

                            {can?.recruitmentHire && applicant?.stage !== 'HIRED' && (
                                <div className="border border-emerald-200 bg-emerald-50 rounded-lg p-4">
                                    <div className="text-sm font-semibold text-slate-900">Hire Applicant</div>
                                    <div className="mt-1 text-sm text-slate-600">
                                        Creates an Employee and marks the applicant as HIRED.
                                    </div>

                                    <form className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-12" onSubmit={submitHire}>
                                        <div className="lg:col-span-4">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                                Department
                                            </label>
                                            <select
                                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                                value={hireForm.data.department_id}
                                                onChange={(e) => hireForm.setData('department_id', e.target.value)}
                                            >
                                                <option value="">Select…</option>
                                                {(departments ?? []).map((d) => (
                                                    <option key={d.department_id} value={d.department_id}>
                                                        {d.name}
                                                    </option>
                                                ))}
                                            </select>
                                            {!!hireForm.errors.department_id && (
                                                <div className="mt-1 text-sm text-rose-700">{hireForm.errors.department_id}</div>
                                            )}
                                        </div>

                                        <div className="lg:col-span-4">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                                Email
                                            </label>
                                            <input
                                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                                value={hireForm.data.email}
                                                onChange={(e) => hireForm.setData('email', e.target.value)}
                                                placeholder="required"
                                            />
                                            {!!hireForm.errors.email && (
                                                <div className="mt-1 text-sm text-rose-700">{hireForm.errors.email}</div>
                                            )}
                                        </div>

                                        <div className="lg:col-span-4">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                                Mobile
                                            </label>
                                            <input
                                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                                value={hireForm.data.mobile_number}
                                                onChange={(e) => hireForm.setData('mobile_number', e.target.value)}
                                            />
                                            {!!hireForm.errors.mobile_number && (
                                                <div className="mt-1 text-sm text-rose-700">{hireForm.errors.mobile_number}</div>
                                            )}
                                        </div>

                                        <div className="lg:col-span-5">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                                Position Title
                                            </label>
                                            <input
                                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                                value={hireForm.data.position_title}
                                                onChange={(e) => hireForm.setData('position_title', e.target.value)}
                                            />
                                            {!!hireForm.errors.position_title && (
                                                <div className="mt-1 text-sm text-rose-700">{hireForm.errors.position_title}</div>
                                            )}
                                        </div>

                                        <div className="lg:col-span-3">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                                Date Hired
                                            </label>
                                            <DatePicker
                                                value={hireForm.data.date_hired}
                                                onChange={(v) => hireForm.setData('date_hired', v)}
                                            />
                                            {!!hireForm.errors.date_hired && (
                                                <div className="mt-1 text-sm text-rose-700">{hireForm.errors.date_hired}</div>
                                            )}
                                        </div>

                                        <div className="lg:col-span-4 flex items-center gap-2">
                                            <input
                                                id="migrate_resume"
                                                type="checkbox"
                                                checked={!!hireForm.data.migrate_resume}
                                                onChange={(e) => hireForm.setData('migrate_resume', e.target.checked)}
                                                className="rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                                            />
                                            <label htmlFor="migrate_resume" className="text-sm text-slate-700">
                                                Copy latest resume into employee documents
                                            </label>
                                        </div>

                                        <div className="lg:col-span-12 flex items-center justify-end">
                                            <PrimaryButton type="submit" disabled={hireForm.processing}>
                                                {hireForm.processing ? 'Hiring…' : 'Hire'}
                                            </PrimaryButton>
                                        </div>
                                    </form>
                                </div>
                            )}

                            {can?.recruitmentManage && (
                                <div className="flex justify-end">
                                    <button
                                        type="button"
                                        onClick={openDeleteModal}
                                        className="text-sm font-medium text-rose-700 hover:text-rose-900"
                                    >
                                        Delete applicant
                                    </button>
                                </div>
                            )}
                        </div>
                    )}

                    {activeTab === 'documents' && (
                        <div className="p-6 space-y-6">
                            {can?.recruitmentDocumentsUpload && (
                                <div className="border border-amber-200 bg-amber-50 rounded-lg p-4">
                                    <div className="text-sm font-semibold text-slate-900">Upload Document</div>
                                    <form className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-12" onSubmit={submitUpload}>
                                        <div className="lg:col-span-3">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Type</label>
                                            <input
                                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                                value={uploadForm.data.type}
                                                onChange={(e) => uploadForm.setData('type', e.target.value)}
                                                placeholder="e.g. Resume"
                                            />
                                            {!!uploadForm.errors.type && (
                                                <div className="mt-1 text-sm text-rose-700">{uploadForm.errors.type}</div>
                                            )}
                                        </div>

                                        <div className="lg:col-span-5">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                                File (PDF/DOC/DOCX/IMG, max 10MB)
                                            </label>
                                            <input
                                                type="file"
                                                multiple
                                                className="mt-1 block w-full text-sm"
                                                onChange={(e) => uploadForm.setData('files', Array.from(e.target.files ?? []))}
                                            />
                                            {!!uploadForm.errors.files && (
                                                <div className="mt-1 text-sm text-rose-700">{uploadForm.errors.files}</div>
                                            )}
                                            {!!uploadForm.errors['files.0'] && (
                                                <div className="mt-1 text-sm text-rose-700">{uploadForm.errors['files.0']}</div>
                                            )}
                                        </div>

                                        <div className="lg:col-span-4">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Notes</label>
                                            <input
                                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                                value={uploadForm.data.notes}
                                                onChange={(e) => uploadForm.setData('notes', e.target.value)}
                                            />
                                            {!!uploadForm.errors.notes && (
                                                <div className="mt-1 text-sm text-rose-700">{uploadForm.errors.notes}</div>
                                            )}
                                        </div>

                                        <div className="lg:col-span-12 flex items-center justify-end">
                                            <PrimaryButton type="submit" disabled={uploadForm.processing}>
                                                {uploadForm.processing ? 'Uploading…' : 'Upload'}
                                            </PrimaryButton>
                                        </div>
                                    </form>
                                </div>
                            )}

                            <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                                <div className="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                                    <div className="text-sm font-semibold text-gray-900">Applicant Documents</div>
                                    <div className="text-sm text-gray-600">{(documents ?? []).length} total</div>
                                </div>

                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                    Type
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                    File
                                                </th>
                                                <th className="px-4 py-3" />
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 bg-white">
                                            {(documents ?? []).length === 0 && (
                                                <tr>
                                                    <td className="px-4 py-6 text-sm text-gray-600" colSpan={3}>
                                                        No documents uploaded yet.
                                                    </td>
                                                </tr>
                                            )}

                                            {(documents ?? []).map((doc) => (
                                                <tr key={doc.id} className="hover:bg-amber-50/40">
                                                    <td className="px-4 py-3 text-sm font-medium text-gray-900">{doc.type}</td>
                                                    <td className="px-4 py-3 text-sm text-gray-700">
                                                        <div className="truncate max-w-[360px]" title={doc.original_name}>
                                                            {doc.original_name}
                                                        </div>
                                                        {doc.file_size ? (
                                                            <div className="text-xs text-gray-500">{Math.round(doc.file_size / 1024)} KB</div>
                                                        ) : null}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-right whitespace-nowrap">
                                                        {can?.recruitmentDocumentsDownload && (
                                                            <a
                                                                href={route('recruitment.applicants.documents.download', [applicant.id, doc.id])}
                                                                className="text-amber-700 hover:text-amber-900 font-medium"
                                                            >
                                                                Download
                                                            </a>
                                                        )}
                                                        {can?.recruitmentDocumentsDelete && (
                                                            <button
                                                                type="button"
                                                                className="ml-4 text-rose-700 hover:text-rose-900 font-medium"
                                                                onClick={() => deleteDocument(doc.id)}
                                                            >
                                                                Delete
                                                            </button>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    )}

                    {activeTab === 'interviews' && (
                        <div className="p-6 space-y-6">
                            {can?.recruitmentInterviewsCreate && (
                                <div className="border border-slate-200 bg-slate-50 rounded-lg p-4">
                                    <div className="text-sm font-semibold text-slate-900">Add Interview Note</div>
                                    <form className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-12" onSubmit={submitInterview}>
                                        <div className="lg:col-span-3">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                                Date
                                            </label>
                                            <DatePicker
                                                value={interviewForm.data.scheduled_at}
                                                onChange={(v) => interviewForm.setData('scheduled_at', v)}
                                            />
                                            {!!interviewForm.errors.scheduled_at && (
                                                <div className="mt-1 text-sm text-rose-700">{interviewForm.errors.scheduled_at}</div>
                                            )}
                                        </div>

                                        <div className="lg:col-span-7">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                                Notes
                                            </label>
                                            <textarea
                                                rows={2}
                                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                                value={interviewForm.data.notes}
                                                onChange={(e) => interviewForm.setData('notes', e.target.value)}
                                            />
                                            {!!interviewForm.errors.notes && (
                                                <div className="mt-1 text-sm text-rose-700">{interviewForm.errors.notes}</div>
                                            )}
                                        </div>

                                        <div className="lg:col-span-2 flex items-end justify-end">
                                            <PrimaryButton type="submit" disabled={interviewForm.processing}>
                                                {interviewForm.processing ? 'Saving…' : 'Add'}
                                            </PrimaryButton>
                                        </div>
                                    </form>
                                </div>
                            )}

                            <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                                <div className="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                                    <div className="text-sm font-semibold text-gray-900">Interviews</div>
                                    <div className="text-sm text-gray-600">{(interviews ?? []).length} total</div>
                                </div>
                                <div className="divide-y divide-gray-200">
                                    {(interviews ?? []).length === 0 && (
                                        <div className="px-4 py-6 text-sm text-gray-600">No interviews yet.</div>
                                    )}

                                    {(interviews ?? []).map((it) => (
                                        <div key={it.id} className="px-4 py-4">
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="min-w-0">
                                                    <div className="text-sm font-semibold text-slate-900">
                                                        {it.scheduled_at ? String(it.scheduled_at) : 'No date'}
                                                    </div>
                                                    <div className="mt-1 whitespace-pre-wrap text-sm text-slate-700">
                                                        {it.notes || '—'}
                                                    </div>
                                                </div>
                                                {can?.recruitmentInterviewsManage && (
                                                    <button
                                                        type="button"
                                                        className="shrink-0 text-sm font-medium text-rose-700 hover:text-rose-900"
                                                        onClick={() => deleteInterview(it.id)}
                                                    >
                                                        Delete
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {activeTab === 'notes' && (
                        <div className="p-6">
                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Notes</div>
                            <div className="mt-2 whitespace-pre-wrap text-sm text-slate-900">{applicant?.notes ?? '—'}</div>
                        </div>
                    )}
                </div>
            </div>

            <Modal show={isDeleteModalOpen} onClose={closeDeleteModal} maxWidth="md">
                <div className="p-6">
                    <div className="text-lg font-semibold text-slate-900">Delete applicant</div>
                    <div className="mt-1 text-sm text-slate-600">This will permanently delete the applicant.</div>
                    <div className="mt-5 flex items-center justify-end gap-2">
                        <SecondaryButton type="button" onClick={closeDeleteModal} disabled={isDeleting}>
                            Cancel
                        </SecondaryButton>
                        <DangerButton type="button" onClick={confirmDelete} disabled={isDeleting}>
                            {isDeleting ? 'Deleting…' : 'Delete'}
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
