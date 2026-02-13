import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Edit({ auth, applicant, positions = [], stages = [], inModal = false, onCancel, onSuccess, returnTo = 'show' }) {
    const [successMessage, setSuccessMessage] = useState('');

    const { data, setData, patch, processing, errors } = useForm({
        position_id: applicant?.position_id ?? '',
        first_name: applicant?.first_name ?? '',
        middle_name: applicant?.middle_name ?? '',
        last_name: applicant?.last_name ?? '',
        suffix: applicant?.suffix ?? '',
        email: applicant?.email ?? '',
        mobile_number: applicant?.mobile_number ?? '',
        source: applicant?.source ?? '',
        stage: applicant?.stage ?? 'APPLIED',
        expected_salary: applicant?.expected_salary ?? '',
        applied_at: applicant?.applied_at ?? '',
        notes: applicant?.notes ?? '',
        return_to: returnTo,
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('recruitment.applicants.update', applicant.id), {
            preserveScroll: true,
            preserveState: inModal,
            onSuccess: () => {
                if (inModal) {
                    setSuccessMessage('Applicant updated successfully.');
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
                                href={route('recruitment.applicants.show', applicant.id)}
                                className="text-sm text-gray-600 hover:text-gray-900"
                            >
                                Cancel
                            </Link>
                        )}
                        <PrimaryButton disabled={processing}>Save</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    );

    if (inModal) return form;

    return (
        <AuthenticatedLayout user={auth.user} header="Edit Applicant">
            <Head title="Edit Applicant" />
            {form}
        </AuthenticatedLayout>
    );
}
