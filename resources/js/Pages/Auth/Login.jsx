import { useEffect, useState } from 'react';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthBrandPanel from '@/Components/Auth/AuthBrandPanel';
import PasswordVisibilityToggle from '@/Components/Auth/PasswordVisibilityToggle';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const initialEmail = (() => {
        try {
            const v = new URLSearchParams(window.location.search).get('email');
            return v ? String(v) : '';
        } catch {
            return '';
        }
    })();

    const { data, setData, post, processing, errors, reset } = useForm({
        email: initialEmail,
        password: '',
        remember: false,
    });

    const [showPassword, setShowPassword] = useState(false);

    useEffect(() => {
        return () => {
            reset('password');
        };
    }, []);

    const submit = (e) => {
        e.preventDefault();

        post(route('login'));
    };

    return (
        <div className="min-h-screen bg-slate-50">
            <Head title="Log in" />

            <div className="min-h-screen grid grid-cols-1 lg:grid-cols-2">
                <div className="hidden lg:block">
                    <AuthBrandPanel />
                </div>

                <div className="flex items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
                    <div className="w-full max-w-md">
                        <div className="lg:hidden mb-8">
                            <div className="text-2xl font-semibold tracking-tight text-slate-900">Crewly</div>
                            <div className="mt-1 text-sm text-slate-600">People operations, simplified.</div>
                        </div>

                        {status && <div className="mb-4 font-medium text-sm text-green-600">{status}</div>}

                        <div className="bg-white/80 backdrop-blur border border-slate-200/70 rounded-2xl shadow-lg shadow-slate-900/5 p-6 sm:p-8">
                            <div className="mb-6">
                                <h1 className="text-xl font-semibold text-slate-900">Sign in</h1>
                                <p className="mt-1 text-sm text-slate-600">Use your account to access the dashboard.</p>
                            </div>

                            <form onSubmit={submit} className="space-y-5">
                                <div>
                                    <InputLabel htmlFor="email" value="Email" />

                                    <TextInput
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={data.email}
                                        className="mt-1 block w-full rounded-xl"
                                        autoComplete="username"
                                        isFocused={true}
                                        onChange={(e) => setData('email', e.target.value)}
                                    />

                                    <InputError message={errors.email} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="password" value="Password" />

                                    <div className="relative mt-1">
                                        <TextInput
                                            id="password"
                                            type={showPassword ? 'text' : 'password'}
                                            name="password"
                                            value={data.password}
                                            className="block w-full pr-12 rounded-xl"
                                            autoComplete="current-password"
                                            onChange={(e) => setData('password', e.target.value)}
                                        />
                                        <PasswordVisibilityToggle
                                            shown={showPassword}
                                            onClick={() => setShowPassword((v) => !v)}
                                        />
                                    </div>

                                    <InputError message={errors.password} className="mt-2" />
                                </div>

                                <div className="flex items-center justify-between gap-4">
                                    <label className="flex items-center">
                                        <Checkbox
                                            name="remember"
                                            checked={data.remember}
                                            onChange={(e) => setData('remember', e.target.checked)}
                                        />
                                        <span className="ms-2 text-sm text-slate-600">Remember me</span>
                                    </label>

                                    {canResetPassword && (
                                        <Link
                                            href={route('password.request')}
                                            className="text-sm text-slate-600 hover:text-slate-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500"
                                        >
                                            Forgot password?
                                        </Link>
                                    )}
                                </div>

                                <div className="pt-2">
                                    <PrimaryButton className="w-full justify-center" disabled={processing}>
                                        Log in
                                    </PrimaryButton>
                                </div>

                                <div className="text-center">
                                    <Link
                                        href={route('home')}
                                        className="text-sm font-medium text-slate-600 hover:text-slate-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500"
                                    >
                                        Back to home
                                    </Link>
                                </div>

                                {/* <div className="text-center text-sm text-gray-600">
                                    <span>New to Crewly?</span>{' '}
                                    <Link
                                        href={route('register')}
                                        className="font-medium text-indigo-600 hover:text-indigo-500 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        Create an account
                                    </Link>
                                </div> */}
                            </form>
                        </div>

                        <div className="mt-6 text-center text-xs text-slate-500">
                            By continuing, you agree to our internal usage policies.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
