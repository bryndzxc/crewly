import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import { Head } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';

export default function Edit({ auth, mustVerifyEmail, status }) {
    const role = String(auth?.user?.role || '').toLowerCase();
    const mustChangePassword = Boolean(auth?.user?.must_change_password);
    const restrictToPasswordOnly = role === 'employee' || mustChangePassword;

    const user = auth?.user;
    const photoUrl = user?.profile_photo_url;

    const initials = useMemo(() => {
        const name = String(user?.name || '');
        return (name.trim().slice(0, 1) || 'U').toUpperCase();
    }, [user?.name]);

    const [previewUrl, setPreviewUrl] = useState(null);

    const { data, setData, errors, post, processing } = useForm({
        photo: null,
    });

    const onPhotoChange = (e) => {
        const file = e.target.files?.[0] || null;
        setData('photo', file);

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
        setPreviewUrl(file ? URL.createObjectURL(file) : null);
    };

    const submitPhoto = (e) => {
        e.preventDefault();

        post(route('profile.photo'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                if (previewUrl) {
                    URL.revokeObjectURL(previewUrl);
                }
                setPreviewUrl(null);
                setData('photo', null);
            },
        });
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Profile" contentClassName="max-w-screen-2xl mx-auto">
            <Head title="Profile" />

            <PageHeader title="Profile" subtitle="Manage your account settings." />

            <div className="space-y-4">
                {restrictToPasswordOnly ? (
                    <Card className="p-6">
                        <UpdatePasswordForm className="w-full" />
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                        <div className="space-y-4 lg:col-span-1">
                            <Card className="p-6">
                                <div className="flex items-center gap-4">
                                    <div className="h-20 w-20 overflow-hidden rounded-2xl bg-amber-100 ring-1 ring-amber-200 flex items-center justify-center">
                                        {photoUrl || previewUrl ? (
                                            <img
                                                src={previewUrl || photoUrl}
                                                alt="Profile photo"
                                                className="h-full w-full object-cover"
                                            />
                                        ) : (
                                            <span className="text-2xl font-semibold text-amber-800">{initials}</span>
                                        )}
                                    </div>
                                    <div className="min-w-0">
                                        <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Signed in as</div>
                                        <div className="mt-1 truncate text-sm font-semibold text-slate-900">{user?.name || '—'}</div>
                                        <div className="mt-0.5 truncate text-sm text-slate-700">{user?.email || '—'}</div>
                                    </div>
                                </div>

                                <div className="mt-6">
                                    <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Profile Photo</div>
                                    <p className="mt-1 text-sm text-slate-600">Upload a new profile picture for your account.</p>

                                    <form onSubmit={submitPhoto} className="mt-3">
                                        <div className="flex flex-col gap-2">
                                            <input
                                                type="file"
                                                accept="image/png,image/jpeg"
                                                onChange={onPhotoChange}
                                                className="block w-full text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-800 hover:file:bg-slate-200"
                                            />
                                            <InputError message={errors.photo} />
                                            <div className="flex items-center justify-end">
                                                <PrimaryButton disabled={processing || !data.photo}>Upload</PrimaryButton>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </Card>

                            <Card className="p-6">
                                <UpdatePasswordForm className="w-full" />
                            </Card>
                        </div>

                        <div className="lg:col-span-2">
                            <Card className="p-6">
                                <UpdateProfileInformationForm
                                    mustVerifyEmail={mustVerifyEmail}
                                    status={status}
                                    className="w-full"
                                />
                            </Card>
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
