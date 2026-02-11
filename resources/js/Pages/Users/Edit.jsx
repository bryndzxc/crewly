import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Edit({ auth, userRecord, roles, inModal = false, onCancel, onSuccess }) {
    const [successMessage, setSuccessMessage] = useState('');

    const { data, setData, patch, processing, errors, reset } = useForm({
        name: userRecord.name || '',
        email: userRecord.email || '',
        role: userRecord.role || (roles?.[0]?.key ?? 'admin'),
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('users.update', userRecord.id), {
            preserveScroll: true,
            preserveState: inModal,
            onFinish: () => reset('password'),
            onSuccess: () => {
                if (inModal) {
                    setSuccessMessage('User updated successfully.');
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
                        <InputLabel htmlFor="role" value="Role" />
                        <select
                            id="role"
                            name="role"
                            value={data.role}
                            onChange={(e) => setData('role', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            {roles.map((r) => (
                                <option key={r.key} value={r.key}>
                                    {r.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.role} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="password" value="New Password (optional)" />
                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            className="mt-1 block w-full"
                            autoComplete="new-password"
                            onChange={(e) => setData('password', e.target.value)}
                        />
                        <InputError message={errors.password} className="mt-2" />
                    </div>

                    <div className="flex items-center justify-end gap-3">
                        {inModal ? (
                            <SecondaryButton type="button" onClick={onCancel} disabled={processing}>
                                Cancel
                            </SecondaryButton>
                        ) : (
                            <Link href={route('users.index')} className="text-sm text-gray-600 hover:text-gray-900">
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
        <AuthenticatedLayout user={auth.user} header="Edit User">
            <Head title="Edit User" />
            {form}
        </AuthenticatedLayout>
    );
}
