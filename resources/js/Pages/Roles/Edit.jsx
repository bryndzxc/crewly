import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Edit({ auth, role, inModal = false, onCancel, onSuccess }) {
    const [successMessage, setSuccessMessage] = useState('');

    const { data, setData, patch, processing, errors } = useForm({
        name: role.name || '',
        key: role.key || '',
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('roles.update', role.id), {
            preserveScroll: true,
            preserveState: inModal,
            onSuccess: () => {
                if (inModal) {
                    setSuccessMessage('Role updated successfully.');
                    if (typeof onSuccess === 'function') {
                        setTimeout(() => onSuccess(), 900);
                    }
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
                        <InputLabel htmlFor="name" value="Name" />
                        <TextInput
                            id="name"
                            name="name"
                            value={data.name}
                            className="mt-1 block w-full"
                            isFocused={true}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="key" value="Key" />
                        <TextInput
                            id="key"
                            name="key"
                            value={data.key}
                            className="mt-1 block w-full"
                            onChange={(e) => setData('key', e.target.value)}
                        />
                        <InputError message={errors.key} className="mt-2" />
                    </div>

                    <div className="flex items-center justify-end gap-3">
                        {inModal ? (
                            <SecondaryButton type="button" onClick={onCancel} disabled={processing}>
                                Cancel
                            </SecondaryButton>
                        ) : (
                            <Link href={route('roles.index')} className="text-sm text-gray-600 hover:text-gray-900">
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
        <AuthenticatedLayout user={auth.user} header="Edit Role">
            <Head title="Edit Role" />
            {form}
        </AuthenticatedLayout>
    );
}
