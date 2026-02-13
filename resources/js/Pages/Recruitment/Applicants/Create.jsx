import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { extractResumeFields } from '@/utils/resumeExtract';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Create({ auth, positions = [], stages = [], inModal = false, onCancel, onSuccess, returnTo = 'show' }) {
    const [successMessage, setSuccessMessage] = useState('');
    const [scanMessage, setScanMessage] = useState('');
    const [scanError, setScanError] = useState('');
    const [isScanning, setIsScanning] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        position_id: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        suffix: '',
        email: '',
        mobile_number: '',
        source: '',
        stage: 'APPLIED',
        expected_salary: '',
        applied_at: '',
        notes: '',
        resume: null,
        return_to: returnTo,
    });

    const onResumeChange = async (file) => {
        setData('resume', file ?? null);
        setScanMessage('');
        setScanError('');
        if (!file) return;

        setIsScanning(true);
        try {
            const extracted = await extractResumeFields(file);

            if (!data.first_name && extracted.first_name) setData('first_name', extracted.first_name);
            if (!data.last_name && extracted.last_name) setData('last_name', extracted.last_name);
            if (!data.email && extracted.email) setData('email', extracted.email);
            if (!data.mobile_number && extracted.mobile_number) setData('mobile_number', extracted.mobile_number);

            if (extracted.first_name || extracted.last_name || extracted.email || extracted.mobile_number) {
                setScanMessage('Resume scanned and fields auto-filled when possible.');
            } else {
                setScanMessage('Resume attached. Auto-fill not available for this file.');
            }
        } catch {
            setScanError('Resume attached. Auto-fill failed for this file.');
        } finally {
            setIsScanning(false);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('recruitment.applicants.store'), {
            preserveScroll: true,
            preserveState: inModal,
            forceFormData: true,
            onSuccess: () => {
                if (inModal) {
                    setSuccessMessage('Applicant created successfully.');
                    reset('resume');
                    if (typeof onSuccess === 'function') setTimeout(() => onSuccess(), 900);
                    return;
                }
                if (typeof onSuccess === 'function') onSuccess();
            },
        });
    };

    const form = (
        <div className={inModal ? '' : 'max-w-3xl mx-auto'}>
            <div className={inModal ? '' : 'bg-white border border-gray-200 rounded-lg p-6'}>
                <form onSubmit={submit} className="space-y-5">
                    {inModal && !!successMessage && (
                        <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                            {successMessage}
                        </div>
                    )}

                    <div>
                        <InputLabel value="Position" />
                        <select
                            className="mt-1 w-full rounded-md border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            value={data.position_id}
                            onChange={(e) => setData('position_id', e.target.value)}
                        >
                            <option value="">None</option>
                            {(positions ?? []).map((p) => (
                                <option key={p.id} value={p.id}>
                                    {p.title} {p.status === 'CLOSED' ? '(Closed)' : ''}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.position_id} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="resume" value="Resume (optional)" />
                        <input
                            id="resume"
                            type="file"
                            accept=".pdf,.doc,.docx"
                            className="mt-1 block w-full text-sm"
                            onChange={(e) => onResumeChange(e.target.files?.[0] ?? null)}
                        />
                        <div className="mt-2 text-xs text-slate-500">
                            PDF/DOC/DOCX, max 10MB. {isScanning ? 'Scanning…' : 'We’ll try to auto-fill fields when possible.'}
                        </div>
                        {!!errors.resume && <InputError message={errors.resume} className="mt-2" />}
                        {!!scanMessage && <div className="mt-2 text-xs text-green-700">{scanMessage}</div>}
                        {!!scanError && <div className="mt-2 text-xs text-rose-700">{scanError}</div>}
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="first_name" value="First Name" />
                            <TextInput
                                id="first_name"
                                value={data.first_name}
                                className="mt-1 block w-full"
                                isFocused
                                onChange={(e) => setData('first_name', e.target.value)}
                            />
                            <InputError message={errors.first_name} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="last_name" value="Last Name" />
                            <TextInput
                                id="last_name"
                                value={data.last_name}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('last_name', e.target.value)}
                            />
                            <InputError message={errors.last_name} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="middle_name" value="Middle Name" />
                            <TextInput
                                id="middle_name"
                                value={data.middle_name}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('middle_name', e.target.value)}
                            />
                            <InputError message={errors.middle_name} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="suffix" value="Suffix" />
                            <TextInput
                                id="suffix"
                                value={data.suffix}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('suffix', e.target.value)}
                            />
                            <InputError message={errors.suffix} className="mt-2" />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="email" value="Email (optional)" />
                            <TextInput
                                id="email"
                                value={data.email}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('email', e.target.value)}
                            />
                            <InputError message={errors.email} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="mobile_number" value="Mobile Number (optional)" />
                            <TextInput
                                id="mobile_number"
                                value={data.mobile_number}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('mobile_number', e.target.value)}
                                placeholder="Max 20 characters"
                            />
                            <InputError message={errors.mobile_number} className="mt-2" />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <InputLabel value="Stage" />
                            <select
                                className="mt-1 w-full rounded-md border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                value={data.stage}
                                onChange={(e) => setData('stage', e.target.value)}
                            >
                                {(stages ?? []).map((s) => (
                                    <option key={s} value={s}>
                                        {s}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.stage} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="source" value="Source" />
                            <TextInput
                                id="source"
                                value={data.source}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('source', e.target.value)}
                                placeholder="e.g. LinkedIn"
                            />
                            <InputError message={errors.source} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="applied_at" value="Applied At" />
                            <TextInput
                                id="applied_at"
                                type="date"
                                value={data.applied_at}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('applied_at', e.target.value)}
                            />
                            <InputError message={errors.applied_at} className="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel htmlFor="expected_salary" value="Expected Salary" />
                        <TextInput
                            id="expected_salary"
                            value={data.expected_salary}
                            className="mt-1 block w-full"
                            onChange={(e) => setData('expected_salary', e.target.value)}
                            placeholder="e.g. 50000"
                        />
                        <InputError message={errors.expected_salary} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="notes" value="Notes" />
                        <textarea
                            id="notes"
                            rows={4}
                            className="mt-1 w-full rounded-md border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                        <InputError message={errors.notes} className="mt-2" />
                    </div>

                    <div className="flex items-center justify-end gap-3">
                        {inModal ? (
                            <SecondaryButton type="button" onClick={onCancel} disabled={processing}>
                                Cancel
                            </SecondaryButton>
                        ) : (
                            <Link
                                href={route('recruitment.applicants.index')}
                                className="text-sm text-gray-600 hover:text-gray-900"
                            >
                                Cancel
                            </Link>
                        )}
                        <PrimaryButton disabled={processing}>Create</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    );

    if (inModal) return form;

    return (
        <AuthenticatedLayout user={auth.user} header="Create Applicant">
            <Head title="Create Applicant" />
            {form}
        </AuthenticatedLayout>
    );
}
