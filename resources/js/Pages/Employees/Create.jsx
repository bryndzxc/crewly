import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import DatePicker from '@/Components/DatePicker';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useMemo, useRef, useState } from 'react';

const statusChoices = ['Active', 'Inactive', 'On Leave', 'Terminated', 'Resigned'];
const employmentTypeChoices = ['Full-Time', 'Part-Time', 'Contractor', 'Intern'];

export default function Create({ auth, departments = [], inModal = false, onCancel, onSuccess }) {
    const [activeTab, setActiveTab] = useState('details');
    const [successMessage, setSuccessMessage] = useState('');
    const [uploadProgress, setUploadProgress] = useState(null);
    const [scanProcessing, setScanProcessing] = useState(false);
    const [scanError, setScanError] = useState('');
    const [scanInfo, setScanInfo] = useState('');
    const [photoPreviewUrl, setPhotoPreviewUrl] = useState('');
    const pageErrors = usePage().props?.errors ?? {};
    const flash = usePage().props?.flash;
    const canUploadDocument = usePage().props?.can?.employeeDocumentsUpload ?? false;

    const defaultDepartmentId = useMemo(() => {
        const first = (departments ?? [])[0];
        return first?.department_id ?? '';
    }, [departments]);

    const { data, setData, post, processing, errors: formErrors } = useForm({
        department_id: defaultDepartmentId,
        employee_code: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        suffix: '',
        photo: null,
        photo_present: 0,
        email: '',
        mobile_number: '',
        status: 'Active',
        position_title: '',
        date_hired: '',
        regularization_date: '',
        employment_type: 'Full-Time',
        notes: '',

        document_type: '',
        document_files: [],
        document_items: [],
        document_issue_date: '',
        document_expiry_date: '',
        document_notes: '',
    });

    const dataRef = useRef(data);
    useEffect(() => {
        dataRef.current = data;
    }, [data]);

    useEffect(() => {
        const file = data.photo;
        if (!file) {
            setPhotoPreviewUrl('');
            return;
        }

        const url = URL.createObjectURL(file);
        setPhotoPreviewUrl(url);

        return () => {
            URL.revokeObjectURL(url);
        };
    }, [data.photo]);

    const errors = Object.keys(formErrors ?? {}).length > 0 ? formErrors : pageErrors;

    const scanAndAutofill = async (files) => {
        if (!canUploadDocument) return;
        if (scanProcessing) return;
        if (!Array.isArray(files) || files.length === 0) return;

        setScanError('');
        setScanInfo('');

        const current = dataRef.current;
        const needsAny =
            !String(current.first_name ?? '').trim() ||
            !String(current.last_name ?? '').trim() ||
            !String(current.email ?? '').trim() ||
            !String(current.mobile_number ?? '').trim();

        if (!needsAny) return;

        setScanProcessing(true);
        try {
            const fd = new FormData();
            files.slice(0, 3).forEach((f) => fd.append('files[]', f));

            const res = await axios.post(route('employees.documents.scan'), fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            const extracted = res?.data ?? {};
            const metaErrors = extracted?._meta?.errors;
            const latest = dataRef.current;

            const before = {
                first_name: String(latest.first_name ?? '').trim(),
                last_name: String(latest.last_name ?? '').trim(),
                email: String(latest.email ?? '').trim(),
                mobile_number: String(latest.mobile_number ?? '').trim(),
            };

            if (!String(latest.first_name ?? '').trim() && extracted.first_name) {
                setData('first_name', extracted.first_name);
            }
            if (!String(latest.last_name ?? '').trim() && extracted.last_name) {
                setData('last_name', extracted.last_name);
            }
            if (!String(latest.email ?? '').trim() && extracted.email) {
                setData('email', extracted.email);
            }
            if (!String(latest.mobile_number ?? '').trim() && extracted.mobile_number) {
                setData('mobile_number', extracted.mobile_number);
            }

            const afterFill = {
                first_name: before.first_name || extracted.first_name,
                last_name: before.last_name || extracted.last_name,
                email: before.email || extracted.email,
                mobile_number: before.mobile_number || extracted.mobile_number,
            };

            const filledAnything = Object.values(afterFill).some((v) => String(v ?? '').trim() !== '');
            if (!filledAnything) {
                if (Array.isArray(metaErrors) && metaErrors.length > 0) {
                    setScanError(metaErrors[0]);
                } else {
                    setScanInfo('No matching name/email/mobile number found in the selected documents.');
                }
            }
        } catch (e) {
            const status = e?.response?.status;
            const message =
                e?.response?.data?.message ||
                (typeof status === 'number' ? `Scan failed (HTTP ${status}).` : 'Scan failed.');
            setScanError(message);
        } finally {
            setScanProcessing(false);
        }
    };

    const submit = (e) => {
        e.preventDefault();

        const hasDocuments = (data.document_items ?? []).length > 0 || (data.document_files ?? []).length > 0;

        post(route('employees.store'), {
            preserveScroll: true,
            preserveState: inModal,
            forceFormData: true,
            onStart: () => {
                if (hasDocuments) setUploadProgress(0);
            },
            onProgress: (progress) => {
                if (!hasDocuments) return;
                const pct = progress?.percentage;
                if (typeof pct === 'number') setUploadProgress(pct);
            },
            onSuccess: () => {
                if (inModal) {
                    setSuccessMessage('Employee created successfully.');
                    if (typeof onSuccess === 'function') {
                        setTimeout(() => onSuccess(), 900);
                    }
                    return;
                }

                if (typeof onSuccess === 'function') onSuccess();
            },
            onError: (errs) => {
                const keys = Object.keys(errs ?? {});
                const hasDocumentErrors = keys.some(
                    (k) => String(k).startsWith('document_') || String(k).startsWith('document_items')
                );
                setActiveTab(hasDocumentErrors ? 'documents' : 'details');
            },
            onFinish: () => {
                setUploadProgress(null);
            },
        });
    };

    const form = (
        <div className={inModal ? '' : 'w-full'}>
            <div className={inModal ? '' : 'bg-white border border-gray-200 rounded-lg p-8'}>
                {!!flash?.success && (
                    <div className="mb-6 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}
                {!!flash?.error && (
                    <div className="mb-6 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        {flash.error}
                    </div>
                )}

                {!inModal && (
                    <div className="mb-6">
                        <div className="text-base font-semibold text-slate-900">Create Employee</div>
                        <div className="mt-1 text-sm text-slate-600">Complete the details, then optionally upload documents.</div>
                    </div>
                )}

                <form onSubmit={submit} className="space-y-8">
                    {inModal && !!successMessage && (
                        <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                            {successMessage}
                        </div>
                    )}

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
                                    Employee Details
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
                            <div className="p-6 space-y-8">
                                <div>
                                    <InputLabel htmlFor="photo" value="Photo (optional)" />
                                    <input
                                        id="photo"
                                        name="photo"
                                        type="file"
                                        accept="image/jpeg,image/png"
                                        className="mt-1 block w-full text-sm text-gray-900"
                                        onChange={(e) => {
                                            const file = e.target.files?.[0] ?? null;
                                            setData('photo', file);
                                            setData('photo_present', file ? 1 : 0);
                                        }}
                                    />
                                    {!!photoPreviewUrl && (
                                        <img
                                            src={photoPreviewUrl}
                                            alt="Selected photo preview"
                                            className="mt-3 h-20 w-20 rounded-md object-cover border border-gray-200"
                                        />
                                    )}
                                    <InputError message={errors.photo} className="mt-2" />
                                </div>

                                <div className="grid grid-cols-1 gap-6 lg:gap-8 sm:grid-cols-2">
                                    <div>
                                        <InputLabel htmlFor="employee_code" value="Employee Code" />
                                        <TextInput
                                            id="employee_code"
                                            name="employee_code"
                                            value={data.employee_code}
                                            className="mt-1 block w-full"
                                            isFocused={true}
                                            onChange={(e) => setData('employee_code', e.target.value)}
                                            placeholder="e.g. EMP_ABC123"
                                        />
                                        <InputError message={errors.employee_code} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="department_id" value="Department" />
                                        <select
                                            id="department_id"
                                            name="department_id"
                                            value={data.department_id}
                                            onChange={(e) => setData('department_id', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            {(departments ?? []).map((d) => (
                                                <option key={d.department_id} value={d.department_id}>
                                                    {d.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.department_id} className="mt-2" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-6 lg:gap-8 sm:grid-cols-2">
                                    <div>
                                        <InputLabel htmlFor="first_name" value="First Name" />
                                        <TextInput
                                            id="first_name"
                                            name="first_name"
                                            value={data.first_name}
                                            className="mt-1 block w-full"
                                            onChange={(e) => setData('first_name', e.target.value)}
                                        />
                                        <InputError message={errors.first_name} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="middle_name" value="Middle Name (optional)" />
                                        <TextInput
                                            id="middle_name"
                                            name="middle_name"
                                            value={data.middle_name}
                                            className="mt-1 block w-full"
                                            onChange={(e) => setData('middle_name', e.target.value)}
                                        />
                                        <InputError message={errors.middle_name} className="mt-2" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-6 lg:gap-8 sm:grid-cols-2">
                                    <div>
                                        <InputLabel htmlFor="last_name" value="Last Name" />
                                        <TextInput
                                            id="last_name"
                                            name="last_name"
                                            value={data.last_name}
                                            className="mt-1 block w-full"
                                            onChange={(e) => setData('last_name', e.target.value)}
                                        />
                                        <InputError message={errors.last_name} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="suffix" value="Suffix (optional)" />
                                        <TextInput
                                            id="suffix"
                                            name="suffix"
                                            value={data.suffix}
                                            className="mt-1 block w-full"
                                            onChange={(e) => setData('suffix', e.target.value)}
                                        />
                                        <InputError message={errors.suffix} className="mt-2" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-6 lg:gap-8 sm:grid-cols-2">
                                    <div>
                                        <InputLabel htmlFor="email" value="Email" />
                                        <TextInput
                                            id="email"
                                            type="email"
                                            name="email"
                                            value={data.email}
                                            className="mt-1 block w-full"
                                            autoComplete="username"
                                            onChange={(e) => setData('email', e.target.value)}
                                        />
                                        <InputError message={errors.email} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="mobile_number" value="Mobile Number (optional)" />
                                        <TextInput
                                            id="mobile_number"
                                            name="mobile_number"
                                            value={data.mobile_number}
                                            className="mt-1 block w-full"
                                            onChange={(e) => setData('mobile_number', e.target.value)}
                                        />
                                        <InputError message={errors.mobile_number} className="mt-2" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-6 lg:gap-8 sm:grid-cols-2">
                                    <div>
                                        <InputLabel htmlFor="status" value="Status" />
                                        <select
                                            id="status"
                                            name="status"
                                            value={data.status}
                                            onChange={(e) => setData('status', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            {statusChoices.map((s) => (
                                                <option key={s} value={s}>
                                                    {s}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.status} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="employment_type" value="Employment Type" />
                                        <select
                                            id="employment_type"
                                            name="employment_type"
                                            value={data.employment_type}
                                            onChange={(e) => setData('employment_type', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            {employmentTypeChoices.map((t) => (
                                                <option key={t} value={t}>
                                                    {t}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.employment_type} className="mt-2" />
                                    </div>
                                </div>

                                <div>
                                    <InputLabel htmlFor="position_title" value="Position Title (optional)" />
                                    <TextInput
                                        id="position_title"
                                        name="position_title"
                                        value={data.position_title}
                                        className="mt-1 block w-full"
                                        onChange={(e) => setData('position_title', e.target.value)}
                                    />
                                    <InputError message={errors.position_title} className="mt-2" />
                                </div>

                                <div className="grid grid-cols-1 gap-6 lg:gap-8 sm:grid-cols-2">
                                    <div>
                                        <InputLabel htmlFor="date_hired" value="Date Hired (optional)" />
                                        <DatePicker
                                            id="date_hired"
                                            name="date_hired"
                                            value={data.date_hired}
                                            onChange={(v) => setData('date_hired', v)}
                                        />
                                        <InputError message={errors.date_hired} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="regularization_date" value="Regularization Date (optional)" />
                                        <DatePicker
                                            id="regularization_date"
                                            name="regularization_date"
                                            value={data.regularization_date}
                                            onChange={(v) => setData('regularization_date', v)}
                                        />
                                        <InputError message={errors.regularization_date} className="mt-2" />
                                    </div>
                                </div>

                                <div>
                                    <InputLabel htmlFor="notes" value="Notes (optional)" />
                                    <textarea
                                        id="notes"
                                        name="notes"
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        rows={5}
                                    />
                                    <InputError message={errors.notes} className="mt-2" />
                                </div>
                            </div>
                        )}

                        {activeTab === 'documents' && (
                            <div className="p-6 space-y-6">
                                {canUploadDocument ? (
                                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-6">
                                        <div className="text-base font-semibold text-slate-900">Initial document (optional)</div>
                                        <div className="mt-1 text-sm text-slate-600">
                                            Upload a 201 document now. It will be encrypted before being stored.
                                        </div>

                                        <div className="mt-6">
                                            <InputLabel htmlFor="document_files" value="Select Files (PDF/JPG/PNG, max 10MB each)" />
                                            <input
                                                id="document_files"
                                                name="document_files"
                                                type="file"
                                                multiple
                                                accept="application/pdf,image/jpeg,image/png"
                                                className="mt-1 block w-full text-sm text-gray-900"
                                                onChange={(e) => {
                                                    const files = Array.from(e.target.files ?? []);

                                                    // If user cancels the picker, do not clear existing selections.
                                                    if (files.length === 0) return;

                                                    const existing = Array.isArray(data.document_items) ? data.document_items : [];
                                                    const seen = new Set(
                                                        existing
                                                            .map((it) => it?.file)
                                                            .filter(Boolean)
                                                            .map((f) => `${f.name}|${f.size}|${f.lastModified ?? ''}`)
                                                    );

                                                    const appended = files
                                                        .filter((f) => {
                                                            const key = `${f.name}|${f.size}|${f.lastModified ?? ''}`;
                                                            if (seen.has(key)) return false;
                                                            seen.add(key);
                                                            return true;
                                                        })
                                                        .map((file) => ({ file, type: '', issue_date: '', expiry_date: '' }));

                                                    setData('document_items', [...existing, ...appended]);

                                                    // Best-effort scan for autofill (name/email/phone).
                                                    void scanAndAutofill(appended.map((x) => x.file));

                                                    // Allow selecting the same file again.
                                                    e.target.value = '';
                                                }}
                                            />
                                            {scanProcessing && (
                                                <div className="mt-2 text-sm text-slate-600">Scanning documents for autofill…</div>
                                            )}
                                            {!!scanError && (
                                                <div className="mt-2 text-sm text-red-600">{scanError}</div>
                                            )}
                                            {!!scanInfo && !scanError && !scanProcessing && (
                                                <div className="mt-2 text-sm text-slate-600">{scanInfo}</div>
                                            )}
                                            <InputError message={errors.document_items} className="mt-2" />
                                        </div>

                                        {(data.document_items ?? []).length > 0 && (
                                            <div className="mt-6 overflow-x-auto bg-white border border-amber-200 rounded-lg">
                                                <table className="min-w-full divide-y divide-gray-200">
                                                    <thead className="bg-gray-50">
                                                        <tr>
                                                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                                                File
                                                            </th>
                                                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                                                Size
                                                            </th>
                                                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                                                Type
                                                            </th>
                                                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                                                Issue Date
                                                            </th>
                                                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                                                Expiry Date
                                                            </th>
                                                            <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600">
                                                                Action
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-gray-200">
                                                        {(data.document_items ?? []).map((item, idx) => (
                                                            <tr key={idx}>
                                                                <td className="px-4 py-3 text-sm text-gray-900">
                                                                    {item?.file?.name ?? `File ${idx + 1}`}
                                                                    {errors[`document_items.${idx}.file`] && (
                                                                        <div className="mt-1 text-sm text-red-600">
                                                                            {errors[`document_items.${idx}.file`]}
                                                                        </div>
                                                                    )}
                                                                </td>
                                                                <td className="px-4 py-3 text-sm text-gray-700">
                                                                    {typeof item?.file?.size === 'number'
                                                                        ? `${Math.max(1, Math.ceil(item.file.size / 1024))} KB`
                                                                        : '-'}
                                                                </td>
                                                                <td className="px-4 py-3 text-sm text-gray-900">
                                                                    <TextInput
                                                                        value={item?.type ?? ''}
                                                                        className="block w-full"
                                                                        onChange={(e) => {
                                                                            const next = [...(data.document_items ?? [])];
                                                                            next[idx] = { ...next[idx], type: e.target.value };
                                                                            setData('document_items', next);
                                                                        }}
                                                                        placeholder="e.g. 201 Contract"
                                                                    />
                                                                    {errors[`document_items.${idx}.type`] && (
                                                                        <div className="mt-1 text-sm text-red-600">
                                                                            {errors[`document_items.${idx}.type`]}
                                                                        </div>
                                                                    )}
                                                                </td>

                                                                <td className="px-4 py-3 text-sm text-gray-900">
                                                                    <DatePicker
                                                                        value={item?.issue_date ?? ''}
                                                                        onChange={(v) => {
                                                                            const next = [...(data.document_items ?? [])];
                                                                            next[idx] = { ...next[idx], issue_date: v };
                                                                            setData('document_items', next);
                                                                        }}
                                                                    />
                                                                    {errors[`document_items.${idx}.issue_date`] && (
                                                                        <div className="mt-1 text-sm text-red-600">
                                                                            {errors[`document_items.${idx}.issue_date`]}
                                                                        </div>
                                                                    )}
                                                                </td>

                                                                <td className="px-4 py-3 text-sm text-gray-900">
                                                                    <DatePicker
                                                                        value={item?.expiry_date ?? ''}
                                                                        onChange={(v) => {
                                                                            const next = [...(data.document_items ?? [])];
                                                                            next[idx] = { ...next[idx], expiry_date: v };
                                                                            setData('document_items', next);
                                                                        }}
                                                                    />
                                                                    {errors[`document_items.${idx}.expiry_date`] && (
                                                                        <div className="mt-1 text-sm text-red-600">
                                                                            {errors[`document_items.${idx}.expiry_date`]}
                                                                        </div>
                                                                    )}
                                                                </td>
                                                                <td className="px-4 py-3 text-right text-sm">
                                                                    <button
                                                                        type="button"
                                                                        className="text-sm text-gray-600 hover:text-gray-900"
                                                                        onClick={() => {
                                                                            const next = [...(data.document_items ?? [])].filter(
                                                                                (_, i) => i !== idx
                                                                            );
                                                                            setData('document_items', next);
                                                                        }}
                                                                    >
                                                                        Remove
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        )}

                                        <div className="mt-6">
                                            <InputLabel htmlFor="document_notes" value="Document Notes (optional)" />
                                            <textarea
                                                id="document_notes"
                                                name="document_notes"
                                                value={data.document_notes}
                                                onChange={(e) => setData('document_notes', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                rows={3}
                                            />
                                            <InputError message={errors.document_notes} className="mt-2" />
                                        </div>
                                    </div>
                                ) : (
                                    <div className="text-sm text-gray-600">Document upload is not available for your account.</div>
                                )}
                            </div>
                        )}
                    </div>

                    <div className="flex items-center justify-end gap-3">
                        {inModal ? (
                            <SecondaryButton type="button" onClick={onCancel} disabled={processing}>
                                Cancel
                            </SecondaryButton>
                        ) : (
                            <Link href={route('employees.index')} className="text-sm text-gray-600 hover:text-gray-900">
                                Cancel
                            </Link>
                        )}

                        <div className="flex flex-col items-end">
                            <PrimaryButton disabled={processing}>
                                {processing
                                    ? (data.document_items ?? []).length > 0 || (data.document_files ?? []).length > 0
                                        ? uploadProgress !== null
                                            ? `Uploading… ${Math.round(uploadProgress)}%`
                                            : 'Uploading…'
                                        : 'Creating…'
                                    : 'Create'}
                            </PrimaryButton>

                            {processing && ((data.document_items ?? []).length > 0 || (data.document_files ?? []).length > 0) && uploadProgress !== null && (
                                <div className="mt-2 w-52">
                                    <div className="h-2 w-full rounded-full bg-gray-200 overflow-hidden">
                                        <div
                                            className="h-2 rounded-full bg-amber-500"
                                            style={{ width: `${Math.max(0, Math.min(100, uploadProgress))}%` }}
                                        />
                                    </div>
                                    <div className="mt-1 text-xs text-gray-600">Uploading documents…</div>
                                </div>
                            )}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );

    if (inModal) return form;

    return (
        <AuthenticatedLayout user={auth.user} header="Create Employee" contentClassName="max-w-none">
            <Head title="Create Employee" />
            {form}
        </AuthenticatedLayout>
    );
}
