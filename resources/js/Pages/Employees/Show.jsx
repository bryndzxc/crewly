import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DatePicker from '@/Components/DatePicker';
import GenerateMemoModal from '@/Components/Employees/GenerateMemoModal';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Fragment, useEffect, useMemo, useRef, useState } from 'react';

function fullName(employee) {
    const parts = [employee?.first_name, employee?.middle_name, employee?.last_name, employee?.suffix]
        .map((v) => String(v || '').trim())
        .filter(Boolean);

    return parts.join(' ');
}

export default function Show({
    auth,
    employee,
    departments = [],
    documents = [],
    notes = [],
    incidents = [],
    memoTemplates = [],
    memos = [],
    can = {},
}) {
    const flash = usePage().props?.flash;
    const departmentName = useMemo(() => {
        const found = (departments ?? []).find((d) => Number(d.department_id) === Number(employee?.department_id));
        return found?.name ?? employee?.department_id;
    }, [departments, employee?.department_id]);

    const [activeTab, setActiveTab] = useState('details');
    const photoInputRef = useRef(null);

    const uploadForm = useForm({
        type: '',
        files: [],
        issue_date: '',
        expiry_date: '',
        notes: '',
    });

    function statusFor(doc) {
        if (!doc?.expiry_date) return { label: 'OK', className: 'bg-amber-50 text-amber-800 border border-amber-200' };

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const expiry = new Date(`${doc.expiry_date}T00:00:00`);
        const diffDays = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));

        if (diffDays < 0) return { label: 'Expired', className: 'bg-red-50 text-red-800 border border-red-200' };
        if (diffDays <= 30) return { label: 'Expiring', className: 'bg-amber-100 text-amber-900 border border-amber-200' };

        return { label: 'OK', className: 'bg-amber-50 text-amber-800 border border-amber-200' };
    }

    function submitUpload(e) {
        e.preventDefault();

        uploadForm.post(route('employees.documents.store', employee.employee_id), {
            forceFormData: true,
            onSuccess: () => {
                uploadForm.reset('type', 'files', 'issue_date', 'expiry_date', 'notes');
            },
        });
    }

    function deleteDocument(docId) {
        if (!confirm('Delete this document? This cannot be undone.')) return;
        router.delete(route('employees.documents.destroy', [employee.employee_id, docId]));
    }

    const noteForm = useForm({
        note_type: 'GENERAL',
        note: '',
        follow_up_date: '',
        attachments: [],
    });

    function submitNote(e) {
        e.preventDefault();

        noteForm.post(route('employees.notes.store', employee.employee_id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                noteForm.reset('note', 'follow_up_date', 'attachments');
                noteForm.setData('note_type', 'GENERAL');
            },
        });
    }

    function deleteNote(noteId) {
        if (!confirm('Delete this note? This cannot be undone.')) return;
        router.delete(route('employees.notes.destroy', [employee.employee_id, noteId]), { preserveScroll: true });
    }

    const incidentForm = useForm({
        category: 'Attendance',
        incident_date: '',
        description: '',
        follow_up_date: '',
        attachments: [],
    });

    function submitIncident(e) {
        e.preventDefault();

        incidentForm.post(route('employees.incidents.store', employee.employee_id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                incidentForm.reset('incident_date', 'description', 'follow_up_date', 'attachments');
                incidentForm.setData('category', 'Attendance');
            },
        });
    }

    const [expandedIncidentId, setExpandedIncidentId] = useState(null);
    const expandedIncident = useMemo(() => {
        const items = Array.isArray(incidents) ? incidents : [];
        return items.find((i) => Number(i.id) === Number(expandedIncidentId)) ?? null;
    }, [incidents, expandedIncidentId]);

    const memoTemplatesItems = useMemo(() => (Array.isArray(memoTemplates) ? memoTemplates : []), [memoTemplates]);
    const memoItems = useMemo(() => (Array.isArray(memos) ? memos : []), [memos]);

    const memosByIncident = useMemo(() => {
        const map = new Map();
        for (const m of memoItems) {
            const incidentId = m?.incident_id;
            if (!incidentId) continue;
            const key = Number(incidentId);
            if (!map.has(key)) map.set(key, []);
            map.get(key).push(m);
        }
        return map;
    }, [memoItems]);

    const [showGenerateMemo, setShowGenerateMemo] = useState(false);
    const [memoIncident, setMemoIncident] = useState(null);

    function openGenerateMemo(incident) {
        setMemoIncident(incident);
        setShowGenerateMemo(true);
    }

    const incidentEditForm = useForm({
        status: 'OPEN',
        action_taken: '',
        follow_up_date: '',
        assigned_to: '',
    });

    useEffect(() => {
        if (!expandedIncident) return;
        incidentEditForm.setData({
            status: expandedIncident.status ?? 'OPEN',
            action_taken: expandedIncident.action_taken ?? '',
            follow_up_date: expandedIncident.follow_up_date ?? '',
            assigned_to: expandedIncident.assigned_to?.id ? String(expandedIncident.assigned_to.id) : '',
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [expandedIncidentId]);

    function submitIncidentUpdate(e) {
        e.preventDefault();
        if (!expandedIncidentId) return;

        incidentEditForm.patch(route('employees.incidents.update', [employee.employee_id, expandedIncidentId]), {
            preserveScroll: true,
        });
    }

    function deleteIncident(incidentId) {
        if (!confirm('Delete this incident? This cannot be undone.')) return;
        router.delete(route('employees.incidents.destroy', [employee.employee_id, incidentId]), { preserveScroll: true });
    }

    function deleteRelationAttachment(attachmentId) {
        if (!confirm('Delete this attachment? This cannot be undone.')) return;
        router.delete(route('relations.attachments.destroy', attachmentId), { preserveScroll: true });
    }

    function incidentStatusBadge(status) {
        const s = String(status || '').toUpperCase();
        if (s === 'UNDER_REVIEW') return { label: 'Under Review', className: 'bg-slate-100 text-slate-800 border border-slate-200' };
        if (s === 'RESOLVED') return { label: 'Resolved', className: 'bg-green-50 text-green-800 border border-green-200' };
        if (s === 'CLOSED') return { label: 'Closed', className: 'bg-slate-100 text-slate-800 border border-slate-200' };
        return { label: 'Open', className: 'bg-amber-100 text-amber-900 border border-amber-200' };
    }

    return (
        <>
        <AuthenticatedLayout user={auth.user} header="Employee" contentClassName="max-w-none">
            <Head title={employee ? `Employee - ${fullName(employee)}` : 'Employee'} />

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

                <div className="bg-white border border-gray-200 rounded-lg p-5 sm:p-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-4 min-w-0">
                            {employee?.photo_url ? (
                                <img
                                    src={employee.photo_url}
                                    alt="Employee photo"
                                    className="h-16 w-16 rounded-full border border-gray-200 object-cover"
                                />
                            ) : (
                                <div className="h-16 w-16 rounded-full border border-gray-200 bg-gray-50 flex items-center justify-center text-base font-semibold text-gray-600">
                                    {(String(employee?.first_name || '').trim().charAt(0) || 'E').toUpperCase()}
                                </div>
                            )}

                            <div className="min-w-0">
                                <div className="truncate text-xl font-semibold text-gray-900">
                                    {fullName(employee) || 'Employee'}
                                </div>
                                <div className="mt-1 flex flex-wrap items-center gap-2">
                                    {!!employee?.employee_code && (
                                        <span className="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                                            {employee.employee_code}
                                        </span>
                                    )}
                                    {!!employee?.status && (
                                        <span className="text-sm text-gray-600">{employee.status}</span>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <input
                                ref={photoInputRef}
                                type="file"
                                accept="image/jpeg,image/png"
                                className="hidden"
                                onChange={(e) => {
                                    const file = e.target.files?.[0] ?? null;
                                    if (!file) return;

                                    router.post(
                                        route('employees.photo.update', employee.employee_id),
                                        { photo: file },
                                        {
                                            forceFormData: true,
                                            preserveScroll: true,
                                            onFinish: () => {
                                                // allow re-uploading same file
                                                if (photoInputRef.current) photoInputRef.current.value = '';
                                            },
                                        }
                                    );
                                }}
                            />

                            <Link href={route('employees.index')}>
                                <SecondaryButton type="button">Back</SecondaryButton>
                            </Link>

                            {employee?.employee_id && (
                                <SecondaryButton
                                    type="button"
                                    onClick={() => photoInputRef.current?.click()}
                                >
                                    Upload Photo
                                </SecondaryButton>
                            )}

                            {!!employee?.photo_url && employee?.employee_id && (
                                <SecondaryButton
                                    type="button"
                                    onClick={() => router.delete(route('employees.photo.destroy', employee.employee_id), { preserveScroll: true })}
                                >
                                    Delete Photo
                                </SecondaryButton>
                            )}

                            {employee?.employee_id && (
                                <Link href={route('employees.edit', employee.employee_id)}>
                                    <PrimaryButton type="button">Edit</PrimaryButton>
                                </Link>
                            )}
                        </div>
                    </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg">
                    <div className="border-b border-gray-200 px-6">
                        <nav className="flex gap-6">
                            <button
                                type="button"
                                onClick={() => setActiveTab('details')}
                                className={`py-3 text-sm font-medium border-b-2 ${
                                    activeTab === 'details'
                                        ? 'border-amber-500 text-gray-900'
                                        : 'border-transparent text-gray-600 hover:text-gray-900'
                                }`}
                            >
                                Details
                            </button>
                            <button
                                type="button"
                                onClick={() => setActiveTab('documents')}
                                className={`py-3 text-sm font-medium border-b-2 ${
                                    activeTab === 'documents'
                                        ? 'border-amber-500 text-gray-900'
                                        : 'border-transparent text-gray-600 hover:text-gray-900'
                                }`}
                            >
                                Documents
                            </button>

                            {can?.employeeRelationsView && (
                                <button
                                    type="button"
                                    onClick={() => setActiveTab('relations')}
                                    className={`py-3 text-sm font-medium border-b-2 ${
                                        activeTab === 'relations'
                                            ? 'border-amber-500 text-gray-900'
                                            : 'border-transparent text-gray-600 hover:text-gray-900'
                                    }`}
                                >
                                    Notes &amp; Incidents
                                </button>
                            )}
                        </nav>
                    </div>

                    {activeTab === 'details' && (
                        <div className="p-6">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Department</div>
                                    <div className="mt-1 text-sm font-medium text-gray-900">{departmentName ?? '-'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Status</div>
                                    <div className="mt-1 text-sm font-medium text-gray-900">{employee?.status ?? '-'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Employment Type</div>
                                    <div className="mt-1 text-sm font-medium text-gray-900">{employee?.employment_type ?? '-'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Email</div>
                                    <div className="mt-1 text-sm font-medium text-gray-900">{employee?.email ?? '-'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Mobile Number</div>
                                    <div className="mt-1 text-sm font-medium text-gray-900">{employee?.mobile_number ?? '-'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Position Title</div>
                                    <div className="mt-1 text-sm font-medium text-gray-900">{employee?.position_title ?? '-'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Date Hired</div>
                                    <div className="mt-1 text-sm font-medium text-gray-900">{employee?.date_hired ?? '-'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Regularization Date</div>
                                    <div className="mt-1 text-sm font-medium text-gray-900">{employee?.regularization_date ?? '-'}</div>
                                </div>
                            </div>

                            <div className="mt-6">
                                <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Notes</div>
                                <div className="mt-2 whitespace-pre-wrap text-sm text-gray-900">{employee?.notes ?? '-'}</div>
                            </div>
                        </div>
                    )}

                    {activeTab === 'documents' && (
                        <div className="p-6 space-y-6">
                            {can?.employeeDocumentsUpload && (
                                <div className="border border-amber-200 bg-amber-50 rounded-lg p-4">
                                    <div className="text-sm font-semibold text-gray-900">Upload Document</div>
                                    <form className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-12" onSubmit={submitUpload}>
                                        <div className="lg:col-span-3">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">
                                                Type
                                            </label>
                                            <input
                                                className="mt-1 w-full rounded-md border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                                value={uploadForm.data.type}
                                                onChange={(e) => uploadForm.setData('type', e.target.value)}
                                                placeholder="e.g. 201 Contract"
                                            />
                                            {uploadForm.errors.type && (
                                                <div className="mt-1 text-sm text-red-600">{uploadForm.errors.type}</div>
                                            )}
                                        </div>

                                        <div className="lg:col-span-4">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">
                                                File (PDF/JPG/PNG, max 10MB)
                                            </label>
                                            <input
                                                type="file"
                                                accept="application/pdf,image/jpeg,image/png"
                                                multiple
                                                className="mt-1 block w-full text-sm"
                                                onChange={(e) => uploadForm.setData('files', Array.from(e.target.files ?? []))}
                                            />
                                            {uploadForm.errors.files && (
                                                <div className="mt-1 text-sm text-red-600">{uploadForm.errors.files}</div>
                                            )}
                                            {uploadForm.errors['files.0'] && (
                                                <div className="mt-1 text-sm text-red-600">{uploadForm.errors['files.0']}</div>
                                            )}
                                        </div>

                                        <div className="lg:col-span-2">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">
                                                Issue Date
                                            </label>
                                            <DatePicker
                                                value={uploadForm.data.issue_date}
                                                onChange={(v) => uploadForm.setData('issue_date', v)}
                                            />
                                            {uploadForm.errors.issue_date && (
                                                <div className="mt-1 text-sm text-red-600">{uploadForm.errors.issue_date}</div>
                                            )}
                                        </div>

                                        <div className="lg:col-span-2">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">
                                                Expiry Date
                                            </label>
                                            <DatePicker
                                                value={uploadForm.data.expiry_date}
                                                onChange={(v) => uploadForm.setData('expiry_date', v)}
                                            />
                                            {uploadForm.errors.expiry_date && (
                                                <div className="mt-1 text-sm text-red-600">{uploadForm.errors.expiry_date}</div>
                                            )}
                                        </div>

                                        <div className="lg:col-span-9">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">
                                                Notes
                                            </label>
                                            <textarea
                                                rows={2}
                                                className="mt-1 w-full rounded-md border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                                value={uploadForm.data.notes}
                                                onChange={(e) => uploadForm.setData('notes', e.target.value)}
                                            />
                                            {uploadForm.errors.notes && (
                                                <div className="mt-1 text-sm text-red-600">{uploadForm.errors.notes}</div>
                                            )}
                                        </div>

                                        <div className="lg:col-span-3 flex items-end justify-end">
                                            <PrimaryButton type="submit" disabled={uploadForm.processing}>
                                                {uploadForm.processing ? 'Uploading...' : 'Upload'}
                                            </PrimaryButton>
                                        </div>
                                    </form>
                                </div>
                            )}

                            <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                                <div className="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                                    <div className="text-sm font-semibold text-gray-900">Employee Documents</div>
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
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                    Issue
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                    Expiry
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                    Status
                                                </th>
                                                <th className="px-4 py-3" />
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 bg-white">
                                            {(documents ?? []).length === 0 && (
                                                <tr>
                                                    <td className="px-4 py-6 text-sm text-gray-600" colSpan={6}>
                                                        No documents uploaded yet.
                                                    </td>
                                                </tr>
                                            )}

                                            {(documents ?? []).map((doc) => {
                                                const badge = statusFor(doc);
                                                return (
                                                    <tr key={doc.id} className="hover:bg-amber-50/40">
                                                        <td className="px-4 py-3 text-sm font-medium text-gray-900">{doc.type}</td>
                                                        <td className="px-4 py-3 text-sm text-gray-700">
                                                            <div className="truncate max-w-[260px]" title={doc.original_name}>
                                                                {doc.original_name}
                                                            </div>
                                                            {doc.file_size ? (
                                                                <div className="text-xs text-gray-500">{Math.round(doc.file_size / 1024)} KB</div>
                                                            ) : null}
                                                        </td>
                                                        <td className="px-4 py-3 text-sm text-gray-700">{doc.issue_date ?? '-'}</td>
                                                        <td className="px-4 py-3 text-sm text-gray-700">{doc.expiry_date ?? '-'}</td>
                                                        <td className="px-4 py-3">
                                                            <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${badge.className}`}>
                                                                {badge.label}
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-3 text-sm text-right whitespace-nowrap">
                                                            {can?.employeeDocumentsDownload && (
                                                                <a
                                                                    href={route('employees.documents.download', [employee.employee_id, doc.id])}
                                                                    className="text-amber-700 hover:text-amber-900 font-medium"
                                                                >
                                                                    Download
                                                                </a>
                                                            )}

                                                            {can?.employeeDocumentsDelete && (
                                                                <button
                                                                    type="button"
                                                                    className="ml-4 text-red-600 hover:text-red-800 font-medium"
                                                                    onClick={() => deleteDocument(doc.id)}
                                                                >
                                                                    Delete
                                                                </button>
                                                            )}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    )}

                    {activeTab === 'relations' && can?.employeeRelationsView && (
                        <div className="p-6 space-y-8">
                            {/* Notes */}
                            <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                                <div className="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                                    <div className="text-sm font-semibold text-gray-900">Employee Notes</div>
                                    <div className="text-sm text-gray-600">{Array.isArray(notes) ? notes.length : 0} total</div>
                                </div>

                                {can?.employeeRelationsManage && (
                                    <div className="border-b border-amber-200 bg-amber-50 p-4">
                                        <div className="text-sm font-semibold text-gray-900">Add Note</div>
                                        <form className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-12" onSubmit={submitNote}>
                                            <div className="lg:col-span-3">
                                                <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">Type</label>
                                                <select
                                                    className="mt-1 w-full rounded-md border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                                    value={noteForm.data.note_type}
                                                    onChange={(e) => noteForm.setData('note_type', e.target.value)}
                                                >
                                                    {['GENERAL', 'COACHING', 'COMMENDATION', 'WARNING', 'OTHER'].map((t) => (
                                                        <option key={t} value={t}>
                                                            {t}
                                                        </option>
                                                    ))}
                                                </select>
                                                {noteForm.errors.note_type && <div className="mt-1 text-sm text-red-600">{noteForm.errors.note_type}</div>}
                                            </div>

                                            <div className="lg:col-span-5">
                                                <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">Note</label>
                                                <textarea
                                                    rows={3}
                                                    className="mt-1 w-full rounded-md border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                                    value={noteForm.data.note}
                                                    onChange={(e) => noteForm.setData('note', e.target.value)}
                                                    placeholder="Write a private HR note…"
                                                />
                                                {noteForm.errors.note && <div className="mt-1 text-sm text-red-600">{noteForm.errors.note}</div>}
                                            </div>

                                            <div className="lg:col-span-2">
                                                <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">Follow-up</label>
                                                <DatePicker value={noteForm.data.follow_up_date} onChange={(v) => noteForm.setData('follow_up_date', v)} />
                                                {noteForm.errors.follow_up_date && (
                                                    <div className="mt-1 text-sm text-red-600">{noteForm.errors.follow_up_date}</div>
                                                )}
                                            </div>

                                            <div className="lg:col-span-2">
                                                <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">
                                                    Attachments (optional)
                                                </label>
                                                <input
                                                    type="file"
                                                    accept="application/pdf,image/jpeg,image/png"
                                                    multiple
                                                    className="mt-1 block w-full text-sm"
                                                    onChange={(e) => noteForm.setData('attachments', Array.from(e.target.files ?? []))}
                                                />
                                                {noteForm.errors.attachments && (
                                                    <div className="mt-1 text-sm text-red-600">{noteForm.errors.attachments}</div>
                                                )}
                                                {noteForm.errors['attachments.0'] && (
                                                    <div className="mt-1 text-sm text-red-600">{noteForm.errors['attachments.0']}</div>
                                                )}
                                            </div>

                                            <div className="lg:col-span-12 flex items-end justify-end">
                                                <PrimaryButton type="submit" disabled={noteForm.processing}>
                                                    {noteForm.processing ? 'Saving…' : 'Save Note'}
                                                </PrimaryButton>
                                            </div>
                                        </form>
                                    </div>
                                )}

                                <div className="divide-y divide-gray-200">
                                    {(!Array.isArray(notes) || notes.length === 0) && (
                                        <div className="px-4 py-10">
                                            <div className="mx-auto max-w-2xl rounded-2xl border border-amber-200/60 bg-amber-50/40 p-6">
                                                <div className="text-sm font-semibold text-gray-900">No notes yet</div>
                                                <div className="mt-1 text-sm text-gray-600">Add a quick private note for HR tracking.</div>
                                            </div>
                                        </div>
                                    )}

                                    {(Array.isArray(notes) ? notes : []).map((n) => (
                                        <div key={n.id} className="px-4 py-4">
                                            <div className="flex flex-wrap items-center justify-between gap-3">
                                                <div className="flex items-center gap-2">
                                                    <span className="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                                                        {n.note_type}
                                                    </span>
                                                    <span className="text-xs text-gray-600">{n.created_at}</span>
                                                    {n.created_by?.name ? (
                                                        <span className="text-xs text-gray-600">• {n.created_by.name}</span>
                                                    ) : null}
                                                </div>

                                                {can?.employeeRelationsManage && (
                                                    <button
                                                        type="button"
                                                        className="text-xs font-semibold text-red-600 hover:text-red-800"
                                                        onClick={() => deleteNote(n.id)}
                                                    >
                                                        Delete
                                                    </button>
                                                )}
                                            </div>

                                            {n.follow_up_date ? (
                                                <div className="mt-2">
                                                    <span className="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                                                        Follow-up: {n.follow_up_date}
                                                    </span>
                                                </div>
                                            ) : null}

                                            <div className="mt-3 whitespace-pre-wrap text-sm text-gray-900">{n.note}</div>

                                            {(Array.isArray(n.attachments) ? n.attachments : []).length > 0 && (
                                                <div className="mt-4">
                                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Attachments</div>
                                                    <div className="mt-2 space-y-1">
                                                        {(n.attachments ?? []).map((a) => (
                                                            <div key={a.id} className="flex items-center justify-between gap-3">
                                                                <a
                                                                    href={route('relations.attachments.download', a.id)}
                                                                    className="text-sm font-medium text-amber-700 hover:text-amber-900 truncate"
                                                                    title={a.original_name}
                                                                >
                                                                    {a.original_name}
                                                                </a>

                                                                {can?.employeeRelationsManage && (
                                                                    <button
                                                                        type="button"
                                                                        className="text-xs font-semibold text-red-600 hover:text-red-800"
                                                                        onClick={() => deleteRelationAttachment(a.id)}
                                                                    >
                                                                        Delete
                                                                    </button>
                                                                )}
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Incidents */}
                            <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                                <div className="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                                    <div className="text-sm font-semibold text-gray-900">Employee Incidents</div>
                                    <div className="text-sm text-gray-600">{Array.isArray(incidents) ? incidents.length : 0} total</div>
                                </div>

                                {can?.employeeRelationsManage && (
                                    <div className="border-b border-amber-200 bg-amber-50 p-4">
                                        <div className="text-sm font-semibold text-gray-900">Create Incident</div>
                                        <form className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-12" onSubmit={submitIncident}>
                                            <div className="lg:col-span-3">
                                                <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">Category</label>
                                                <select
                                                    className="mt-1 w-full rounded-md border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                                    value={incidentForm.data.category}
                                                    onChange={(e) => incidentForm.setData('category', e.target.value)}
                                                >
                                                    {['Attendance', 'Conduct', 'Policy Violation', 'Performance', 'Other'].map((c) => (
                                                        <option key={c} value={c}>
                                                            {c}
                                                        </option>
                                                    ))}
                                                </select>
                                                {incidentForm.errors.category && <div className="mt-1 text-sm text-red-600">{incidentForm.errors.category}</div>}
                                            </div>

                                            <div className="lg:col-span-2">
                                                <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">Date</label>
                                                <DatePicker
                                                    value={incidentForm.data.incident_date}
                                                    onChange={(v) => incidentForm.setData('incident_date', v)}
                                                />
                                                {incidentForm.errors.incident_date && (
                                                    <div className="mt-1 text-sm text-red-600">{incidentForm.errors.incident_date}</div>
                                                )}
                                            </div>

                                            <div className="lg:col-span-5">
                                                <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">Description</label>
                                                <textarea
                                                    rows={3}
                                                    className="mt-1 w-full rounded-md border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                                    value={incidentForm.data.description}
                                                    onChange={(e) => incidentForm.setData('description', e.target.value)}
                                                    placeholder="What happened? Keep it factual…"
                                                />
                                                {incidentForm.errors.description && (
                                                    <div className="mt-1 text-sm text-red-600">{incidentForm.errors.description}</div>
                                                )}
                                            </div>

                                            <div className="lg:col-span-2">
                                                <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">Follow-up</label>
                                                <DatePicker
                                                    value={incidentForm.data.follow_up_date}
                                                    onChange={(v) => incidentForm.setData('follow_up_date', v)}
                                                />
                                                {incidentForm.errors.follow_up_date && (
                                                    <div className="mt-1 text-sm text-red-600">{incidentForm.errors.follow_up_date}</div>
                                                )}
                                            </div>

                                            <div className="lg:col-span-12">
                                                <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">
                                                    Attachments (optional)
                                                </label>
                                                <input
                                                    type="file"
                                                    accept="application/pdf,image/jpeg,image/png"
                                                    multiple
                                                    className="mt-1 block w-full text-sm"
                                                    onChange={(e) => incidentForm.setData('attachments', Array.from(e.target.files ?? []))}
                                                />
                                                {incidentForm.errors.attachments && (
                                                    <div className="mt-1 text-sm text-red-600">{incidentForm.errors.attachments}</div>
                                                )}
                                                {incidentForm.errors['attachments.0'] && (
                                                    <div className="mt-1 text-sm text-red-600">{incidentForm.errors['attachments.0']}</div>
                                                )}
                                            </div>

                                            <div className="lg:col-span-12 flex items-end justify-end">
                                                <PrimaryButton type="submit" disabled={incidentForm.processing}>
                                                    {incidentForm.processing ? 'Creating…' : 'Create Incident'}
                                                </PrimaryButton>
                                            </div>
                                        </form>
                                    </div>
                                )}

                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Category</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Date</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Follow-up</th>
                                                <th className="px-4 py-3" />
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 bg-white">
                                            {(!Array.isArray(incidents) || incidents.length === 0) && (
                                                <tr>
                                                    <td className="px-4 py-10" colSpan={5}>
                                                        <div className="mx-auto max-w-2xl rounded-2xl border border-amber-200/60 bg-amber-50/40 p-6">
                                                            <div className="text-sm font-semibold text-gray-900">No incidents</div>
                                                            <div className="mt-1 text-sm text-gray-600">Open cases will show up here.</div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            )}

                                            {(Array.isArray(incidents) ? incidents : []).map((i) => {
                                                const badge = incidentStatusBadge(i.status);
                                                const isExpanded = Number(expandedIncidentId) === Number(i.id);
                                                const incidentMemos = memosByIncident.get(Number(i.id)) ?? [];

                                                return (
                                                    <Fragment key={i.id}>
                                                        <tr className="hover:bg-amber-50/40">
                                                            <td className="px-4 py-3 text-sm font-medium text-gray-900">{i.category}</td>
                                                            <td className="px-4 py-3 text-sm text-gray-700">{i.incident_date ?? '—'}</td>
                                                            <td className="px-4 py-3">
                                                                <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${badge.className}`}>
                                                                    {badge.label}
                                                                </span>
                                                            </td>
                                                            <td className="px-4 py-3 text-sm text-gray-700">{i.follow_up_date ?? '—'}</td>
                                                            <td className="px-4 py-3 text-sm text-right whitespace-nowrap">
                                                                <button
                                                                    type="button"
                                                                    className="text-amber-700 hover:text-amber-900 font-medium"
                                                                    onClick={() => setExpandedIncidentId(isExpanded ? null : i.id)}
                                                                >
                                                                    {isExpanded ? 'Hide' : 'View'}
                                                                </button>

                                                                {can?.generateMemos && memoTemplatesItems.length > 0 && (
                                                                    <button
                                                                        type="button"
                                                                        className="ml-4 text-slate-700 hover:text-slate-900 font-medium"
                                                                        onClick={() => openGenerateMemo(i)}
                                                                    >
                                                                        Generate Memo
                                                                    </button>
                                                                )}

                                                                {can?.employeeRelationsManage && (
                                                                    <button
                                                                        type="button"
                                                                        className="ml-4 text-red-600 hover:text-red-800 font-medium"
                                                                        onClick={() => deleteIncident(i.id)}
                                                                    >
                                                                        Delete
                                                                    </button>
                                                                )}
                                                            </td>
                                                        </tr>

                                                        {isExpanded && (
                                                            <tr>
                                                                <td className="px-4 py-4 bg-amber-50/30" colSpan={5}>
                                                                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
                                                                        <div className="lg:col-span-7">
                                                                            <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Description</div>
                                                                            <div className="mt-2 whitespace-pre-wrap text-sm text-gray-900">{i.description}</div>

                                                                            <div className="mt-5">
                                                                                <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Action Taken</div>
                                                                                <div className="mt-2 whitespace-pre-wrap text-sm text-gray-900">{i.action_taken || '—'}</div>
                                                                            </div>

                                                                            {(Array.isArray(i.attachments) ? i.attachments : []).length > 0 && (
                                                                                <div className="mt-5">
                                                                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Attachments</div>
                                                                                    <div className="mt-2 space-y-1">
                                                                                        {(i.attachments ?? []).map((a) => (
                                                                                            <div key={a.id} className="flex items-center justify-between gap-3">
                                                                                                <a
                                                                                                    href={route('relations.attachments.download', a.id)}
                                                                                                    className="text-sm font-medium text-amber-700 hover:text-amber-900 truncate"
                                                                                                    title={a.original_name}
                                                                                                >
                                                                                                    {a.original_name}
                                                                                                </a>
                                                                                                {can?.employeeRelationsManage && (
                                                                                                    <button
                                                                                                        type="button"
                                                                                                        className="text-xs font-semibold text-red-600 hover:text-red-800"
                                                                                                        onClick={() => deleteRelationAttachment(a.id)}
                                                                                                    >
                                                                                                        Delete
                                                                                                    </button>
                                                                                                )}
                                                                                            </div>
                                                                                        ))}
                                                                                    </div>
                                                                                </div>
                                                                            )}

                                                                            {incidentMemos.length > 0 && (
                                                                                <div className="mt-5">
                                                                                    <div className="flex items-center justify-between gap-3">
                                                                                        <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Generated Memos</div>
                                                                                        <div className="text-xs text-slate-500">{incidentMemos.length}</div>
                                                                                    </div>
                                                                                    <div className="mt-2 space-y-1">
                                                                                        {incidentMemos.map((m) => (
                                                                                            <div key={m.id} className="flex items-center justify-between gap-3">
                                                                                                {can?.downloadMemos ? (
                                                                                                    <a
                                                                                                        href={route('memos.download', m.id)}
                                                                                                        className="text-sm font-medium text-amber-700 hover:text-amber-900 truncate"
                                                                                                        title={m.title}
                                                                                                    >
                                                                                                        {m.title}
                                                                                                    </a>
                                                                                                ) : (
                                                                                                    <div className="text-sm font-medium text-slate-900 truncate" title={m.title}>
                                                                                                        {m.title}
                                                                                                    </div>
                                                                                                )}
                                                                                                <div className="text-xs text-slate-500 whitespace-nowrap">{m.created_at ?? ''}</div>
                                                                                            </div>
                                                                                        ))}
                                                                                    </div>
                                                                                </div>
                                                                            )}
                                                                        </div>

                                                                        <div className="lg:col-span-5">
                                                                            {can?.employeeRelationsManage ? (
                                                                                <div className="rounded-lg border border-amber-200 bg-white p-4">
                                                                                    <div className="text-sm font-semibold text-gray-900">Update Incident</div>
                                                                                    <form className="mt-4 space-y-4" onSubmit={submitIncidentUpdate}>
                                                                                        <div>
                                                                                            <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">Status</label>
                                                                                            <select
                                                                                                className="mt-1 w-full rounded-md border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                                                                                value={incidentEditForm.data.status}
                                                                                                onChange={(e) => incidentEditForm.setData('status', e.target.value)}
                                                                                            >
                                                                                                {['OPEN', 'UNDER_REVIEW', 'RESOLVED', 'CLOSED'].map((s) => (
                                                                                                    <option key={s} value={s}>
                                                                                                        {s}
                                                                                                    </option>
                                                                                                ))}
                                                                                            </select>
                                                                                            {incidentEditForm.errors.status && (
                                                                                                <div className="mt-1 text-sm text-red-600">{incidentEditForm.errors.status}</div>
                                                                                            )}
                                                                                        </div>

                                                                                        <div>
                                                                                            <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">Follow-up</label>
                                                                                            <DatePicker
                                                                                                value={incidentEditForm.data.follow_up_date}
                                                                                                onChange={(v) => incidentEditForm.setData('follow_up_date', v)}
                                                                                            />
                                                                                            {incidentEditForm.errors.follow_up_date && (
                                                                                                <div className="mt-1 text-sm text-red-600">{incidentEditForm.errors.follow_up_date}</div>
                                                                                            )}
                                                                                        </div>

                                                                                        <div>
                                                                                            <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">Action Taken</label>
                                                                                            <textarea
                                                                                                rows={4}
                                                                                                className="mt-1 w-full rounded-md border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                                                                                value={incidentEditForm.data.action_taken}
                                                                                                onChange={(e) => incidentEditForm.setData('action_taken', e.target.value)}
                                                                                                placeholder="Outcome / next steps…"
                                                                                            />
                                                                                            {incidentEditForm.errors.action_taken && (
                                                                                                <div className="mt-1 text-sm text-red-600">{incidentEditForm.errors.action_taken}</div>
                                                                                            )}
                                                                                        </div>

                                                                                        <div className="flex items-center justify-end">
                                                                                            <PrimaryButton type="submit" disabled={incidentEditForm.processing}>
                                                                                                {incidentEditForm.processing ? 'Saving…' : 'Save'}
                                                                                            </PrimaryButton>
                                                                                        </div>
                                                                                    </form>

                                                                                    <div className="mt-5 border-t border-gray-200 pt-4">
                                                                                        <AttachmentUploader
                                                                                            attachableType="incidents"
                                                                                            attachableId={i.id}
                                                                                            canManage={can?.employeeRelationsManage}
                                                                                        />
                                                                                    </div>
                                                                                </div>
                                                                            ) : (
                                                                                <div className="rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-600">
                                                                                    You have view-only access.
                                                                                </div>
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        )}
                                                    </Fragment>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>

        <GenerateMemoModal
            show={showGenerateMemo}
            onClose={() => setShowGenerateMemo(false)}
            employee={employee}
            incident={memoIncident}
            templates={memoTemplatesItems}
            defaultSignatory={auth?.user?.name ?? ''}
        />
        </>
    );
}

function AttachmentUploader({ attachableType, attachableId, canManage }) {
    const form = useForm({
        type: '',
        files: [],
    });

    if (!canManage) return null;

    return (
        <div>
            <div className="text-sm font-semibold text-gray-900">Add Attachments</div>
            <form
                className="mt-3 grid grid-cols-1 gap-3"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(route('relations.attachments.store', [attachableType, attachableId]), {
                        forceFormData: true,
                        preserveScroll: true,
                        onSuccess: () => form.reset('type', 'files'),
                    });
                }}
            >
                <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">Type (optional)</label>
                    <input
                        className="mt-1 w-full rounded-md border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                        value={form.data.type}
                        onChange={(e) => form.setData('type', e.target.value)}
                        placeholder="e.g. Evidence, Memo"
                    />
                    {form.errors.type && <div className="mt-1 text-sm text-red-600">{form.errors.type}</div>}
                </div>

                <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-gray-600">Files</label>
                    <input
                        type="file"
                        accept="application/pdf,image/jpeg,image/png"
                        multiple
                        className="mt-1 block w-full text-sm"
                        onChange={(e) => form.setData('files', Array.from(e.target.files ?? []))}
                    />
                    {form.errors.files && <div className="mt-1 text-sm text-red-600">{form.errors.files}</div>}
                    {form.errors['files.0'] && <div className="mt-1 text-sm text-red-600">{form.errors['files.0']}</div>}
                </div>

                <div className="flex items-center justify-end">
                    <PrimaryButton type="submit" disabled={form.processing}>
                        {form.processing ? 'Uploading…' : 'Upload'}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    );
}
