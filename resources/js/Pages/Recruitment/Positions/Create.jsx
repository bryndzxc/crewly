import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Create({ auth, inModal = false, onCancel, onSuccess }) {
    const [successMessage, setSuccessMessage] = useState('');

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        department: '',
        location: '',
        status: 'OPEN',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('recruitment.positions.store'), {
            preserveScroll: true,
            preserveState: inModal,
            onSuccess: () => {
                if (inModal) {
                    setSuccessMessage('Position created successfully.');
                    if (typeof onSuccess === 'function') setTimeout(() => onSuccess(), 900);
                    return;
                }
                if (typeof onSuccess === 'function') onSuccess();
            },
        });
    };

    const form = (
        <div className={inModal ? '' : 'max-w-2xl mx-auto'}>
            <div className={inModal ? '' : 'bg-white border border-gray-200 rounded-lg p-6'}>
                <form onSubmit={submit} className="space-y-5">
                    {inModal && !!successMessage && (
                        <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                            {successMessage}
                        </div>
                    )}
                    <div>
                        <InputLabel htmlFor="title" value="Title" />
                        <TextInput
                            id="title"
                            value={data.title}
                            className="mt-1 block w-full"
                            isFocused
                            onChange={(e) => setData('title', e.target.value)}
                        />
                        <InputError message={errors.title} className="mt-2" />
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="department" value="Department" />
                            <TextInput
                                id="department"
                                value={data.department}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('department', e.target.value)}
                            />
                            <InputError message={errors.department} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="location" value="Location" />
                            <TextInput
                                id="location"
                                value={data.location}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('location', e.target.value)}
                            />
                            <InputError message={errors.location} className="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Status" />
                        <select
                            className="mt-1 w-full rounded-md border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                        >
                            <option value="OPEN">OPEN</option>
                            <option value="CLOSED">CLOSED</option>
                        </select>
                        <InputError message={errors.status} className="mt-2" />
                    </div>

                    <div className="flex items-center justify-end gap-3">
                        {inModal ? (
                            <SecondaryButton type="button" onClick={onCancel} disabled={processing}>
                                Cancel
                            </SecondaryButton>
                        ) : (
                            <Link
                                href={route('recruitment.positions.index')}
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
        <AuthenticatedLayout user={auth.user} header="Create Position">
            <Head title="Create Position" />
            {form}
        </AuthenticatedLayout>
    );
}
