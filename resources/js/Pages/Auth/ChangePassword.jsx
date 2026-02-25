import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function ChangePassword() {
    const flash = usePage().props.flash;

    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        form.put(route('password.update'), {
            preserveScroll: true,
            onFinish: () => form.reset('current_password', 'password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Change password" />

            <div className="space-y-4">
                <div>
                    <h1 className="text-lg font-semibold text-slate-900">Change password</h1>
                    <p className="mt-1 text-sm text-slate-600">
                        Please update your password to continue.
                    </p>
                </div>

                {!!flash?.warning && (
                    <div className="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                        {flash.warning}
                    </div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        {flash.error}
                    </div>
                )}
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <InputLabel htmlFor="current_password" value="Current password" />
                        <TextInput
                            id="current_password"
                            type="password"
                            name="current_password"
                            value={form.data.current_password}
                            className="mt-1 block w-full"
                            autoComplete="current-password"
                            isFocused
                            onChange={(e) => form.setData('current_password', e.target.value)}
                        />
                        <InputError message={form.errors.current_password} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="password" value="New password" />
                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            value={form.data.password}
                            className="mt-1 block w-full"
                            autoComplete="new-password"
                            onChange={(e) => form.setData('password', e.target.value)}
                        />
                        <InputError message={form.errors.password} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="password_confirmation" value="Confirm new password" />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            value={form.data.password_confirmation}
                            className="mt-1 block w-full"
                            autoComplete="new-password"
                            onChange={(e) => form.setData('password_confirmation', e.target.value)}
                        />
                        <InputError message={form.errors.password_confirmation} className="mt-2" />
                    </div>

                    <div className="pt-2">
                        <PrimaryButton disabled={form.processing} className="w-full justify-center">
                            Save
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
