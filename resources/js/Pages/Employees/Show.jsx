import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DatePicker from '@/Components/DatePicker';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useRef, useState } from 'react';

function fullName(employee) {
    const parts = [employee?.first_name, employee?.middle_name, employee?.last_name, employee?.suffix]
        .map((v) => String(v || '').trim())
        .filter(Boolean);

    return parts.join(' ');
}

export default function Show({ auth, employee, departments = [], documents = [], can = {} }) {
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

    return (
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
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
