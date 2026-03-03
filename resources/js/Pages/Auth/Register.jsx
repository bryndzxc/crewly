import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo } from 'react';

export default function Register() {
    const props = usePage().props;
    const flash = props.flash || {};
    const submitted = useMemo(() => Boolean(flash?.success), [flash?.success]);
    const requestedPlanDefault = String(props?.requested_plan_default || '');

    const { data, setData, post, processing, errors, reset } = useForm({
        full_name: '',
        company_name: '',
        email: '',
        phone: '',
        employee_count_range: '',
        requested_plan: requestedPlanDefault,
        industry: '',
        current_process: '',
        biggest_pain: '',
        agree_to_terms: false,
        source_page: 'register',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('register'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
            },
        });
    };

    return (
        <div className="min-h-screen bg-slate-50">
            <Head title="Request access" />

            <div className="min-h-screen flex items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
                <div className="w-full max-w-2xl">
                    <div className="mb-8">
                        <div className="text-2xl font-semibold tracking-tight text-slate-900">Crewly</div>
                        <div className="mt-1 text-sm text-slate-600">Tell us about your business and we’ll set up your Crewly account.</div>
                    </div>

                    <div className="bg-white/80 backdrop-blur border border-slate-200/70 rounded-2xl shadow-lg shadow-slate-900/5 p-6 sm:p-8">
                        <div className="mb-6">
                            <h1 className="text-xl font-semibold text-slate-900">Request access</h1>
                            <p className="mt-1 text-sm text-slate-600">We’ll review your request and email you once it’s approved.</p>
                        </div>

                        {submitted && (
                            <div className="space-y-4">
                                <div className="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                                    {flash.success}
                                </div>

                                <div className="flex items-center justify-end">
                                    <SecondaryButton type="button" onClick={() => router.visit(route('home'))}>
                                        Back to home
                                    </SecondaryButton>
                                </div>
                            </div>
                        )}

                        {!!flash.error && (
                            <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                        )}

                        {!submitted && (
                            <form onSubmit={submit} className="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div>
                                    <InputLabel htmlFor="company_name" value="Company name" />
                                    <TextInput
                                        id="company_name"
                                        name="company_name"
                                        value={data.company_name}
                                        className="mt-1 block w-full rounded-xl"
                                        autoComplete="organization"
                                        isFocused={true}
                                        onChange={(e) => setData('company_name', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.company_name} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="full_name" value="Your name" />
                                    <TextInput
                                        id="full_name"
                                        name="full_name"
                                        value={data.full_name}
                                        className="mt-1 block w-full rounded-xl"
                                        autoComplete="name"
                                        onChange={(e) => setData('full_name', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.full_name} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="email" value="Email" />
                                    <TextInput
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={data.email}
                                        className="mt-1 block w-full rounded-xl"
                                        autoComplete="email"
                                        onChange={(e) => setData('email', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.email} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="phone" value="Mobile" />
                                    <TextInput
                                        id="phone"
                                        name="phone"
                                        value={data.phone}
                                        className="mt-1 block w-full rounded-xl"
                                        autoComplete="tel"
                                        onChange={(e) => setData('phone', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.phone} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="employee_count_range" value="Employee count" />
                                    <select
                                        id="employee_count_range"
                                        value={data.employee_count_range}
                                        onChange={(e) => setData('employee_count_range', e.target.value)}
                                        className="mt-1 block w-full rounded-xl border-slate-300 bg-white/90 px-3 py-2 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                        required
                                    >
                                        <option value="">Select…</option>
                                        <option value="1-20">1–20</option>
                                        <option value="21-50">21–50</option>
                                        <option value="51-100">51–100</option>
                                        <option value="101-200">101–200</option>
                                    </select>
                                    <InputError message={errors.employee_count_range} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="requested_plan" value="Plan" />
                                    <select
                                        id="requested_plan"
                                        value={data.requested_plan}
                                        onChange={(e) => setData('requested_plan', e.target.value)}
                                        className="mt-1 block w-full rounded-xl border-slate-300 bg-white/90 px-3 py-2 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                        required
                                    >
                                        <option value="">Select…</option>
                                        <option value="starter">Starter</option>
                                        <option value="growth">Growth</option>
                                        <option value="pro">Pro</option>
                                    </select>
                                    <InputError message={errors.requested_plan} className="mt-2" />
                                </div>

                                <div className="md:col-span-2 pt-1">
                                    <div className="h-px w-full bg-slate-200/70" />
                                </div>

                                <div className="md:col-span-2">
                                    <InputLabel htmlFor="industry" value="Industry (optional)" />
                                    <TextInput
                                        id="industry"
                                        name="industry"
                                        value={data.industry}
                                        className="mt-1 block w-full rounded-xl"
                                        onChange={(e) => setData('industry', e.target.value)}
                                    />
                                    <InputError message={errors.industry} className="mt-2" />
                                </div>

                                <div className="md:col-span-2">
                                    <InputLabel htmlFor="current_process" value="Current process (optional)" />
                                    <textarea
                                        id="current_process"
                                        value={data.current_process}
                                        onChange={(e) => setData('current_process', e.target.value)}
                                        rows={3}
                                        className="mt-1 block w-full rounded-xl border-slate-300 bg-white/90 px-3 py-2 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                        placeholder="How do you currently handle HR tasks (manual, spreadsheets, other tools)?"
                                    />
                                    <InputError message={errors.current_process} className="mt-2" />
                                </div>

                                <div className="md:col-span-2">
                                    <InputLabel htmlFor="biggest_pain" value="Biggest pain (optional)" />
                                    <textarea
                                        id="biggest_pain"
                                        value={data.biggest_pain}
                                        onChange={(e) => setData('biggest_pain', e.target.value)}
                                        rows={3}
                                        className="mt-1 block w-full rounded-xl border-slate-300 bg-white/90 px-3 py-2 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                                        placeholder="What’s the biggest HR challenge you want to solve?"
                                    />
                                    <InputError message={errors.biggest_pain} className="mt-2" />
                                </div>

                                <div className="md:col-span-2 rounded-xl border border-slate-200 bg-white/70 px-4 py-3">
                                    <label className="flex items-start gap-3">
                                        <input
                                            type="checkbox"
                                            className="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-2 focus:ring-amber-500/40"
                                            checked={Boolean(data.agree_to_terms)}
                                            onChange={(e) => setData('agree_to_terms', e.target.checked)}
                                            required
                                        />
                                        <span className="text-sm text-slate-700">
                                            I agree to the{' '}
                                            <Link href={route('public.terms')} className="font-medium text-slate-900 underline hover:text-slate-950">
                                                Terms
                                            </Link>{' '}
                                            and{' '}
                                            <Link href={route('public.privacy')} className="font-medium text-slate-900 underline hover:text-slate-950">
                                                Privacy Policy
                                            </Link>
                                            .
                                        </span>
                                    </label>
                                    <InputError message={errors.agree_to_terms} className="mt-2" />
                                </div>

                                <div className="md:col-span-2 flex flex-col-reverse gap-4 sm:flex-row sm:items-center sm:justify-between pt-2">
                                    <Link href={route('login')} className="text-sm text-slate-600 hover:text-slate-900 underline">
                                        Already have access?
                                    </Link>

                                    <PrimaryButton className="rounded-xl sm:px-6" disabled={processing}>
                                        {processing ? 'Submitting…' : 'Submit request'}
                                    </PrimaryButton>
                                </div>
                            </form>
                        )}
                    </div>

                    <div className="mt-6 text-center">
                        <Link
                            href={route('home')}
                            className="text-sm font-medium text-slate-600 hover:text-slate-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500"
                        >
                            Back to home
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
