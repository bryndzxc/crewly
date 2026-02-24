import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function LeadForm({ sourcePage = null, title = 'Request a demo', subtitle = 'Tell us a bit about your team. We’ll reply within 1–2 business days.' }) {
    const flash = usePage().props.flash || {};
    const [submitted, setSubmitted] = useState(false);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        full_name: '',
        company_name: '',
        email: '',
        phone: '',
        company_size: '',
        message: '',
        source_page: sourcePage,
    });

    useEffect(() => {
        setData('source_page', sourcePage);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [sourcePage]);

    const showSuccess = useMemo(() => Boolean(submitted || flash.success), [submitted, flash.success]);

    const submit = (e) => {
        e.preventDefault();
        setSubmitted(false);
        clearErrors();

        post(route('public.leads.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setSubmitted(true);
                reset('message');
            },
        });
    };

    return (
        <div className="rounded-2xl border border-slate-200/70 bg-white/80 backdrop-blur shadow-lg shadow-slate-900/5">
            <div className="p-6 sm:p-8">
                <div className="mb-6">
                    <h2 className="text-lg font-semibold text-slate-900">{title}</h2>
                    {!!subtitle && <p className="mt-1 text-sm text-slate-600">{subtitle}</p>}
                </div>

                {showSuccess && (
                    <div className="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        Thanks — we received your demo request.
                    </div>
                )}

                {!!flash.error && (
                    <div className="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {flash.error}
                    </div>
                )}

                <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div className="sm:col-span-1">
                        <InputLabel htmlFor="full_name" value="Full name" />
                        <TextInput
                            id="full_name"
                            value={data.full_name}
                            onChange={(e) => setData('full_name', e.target.value)}
                            className="mt-1 block w-full rounded-xl"
                            autoComplete="name"
                            required
                        />
                        <InputError message={errors.full_name} className="mt-1" />
                    </div>

                    <div className="sm:col-span-1">
                        <InputLabel htmlFor="company_name" value="Company name" />
                        <TextInput
                            id="company_name"
                            value={data.company_name}
                            onChange={(e) => setData('company_name', e.target.value)}
                            className="mt-1 block w-full rounded-xl"
                            required
                        />
                        <InputError message={errors.company_name} className="mt-1" />
                    </div>

                    <div className="sm:col-span-1">
                        <InputLabel htmlFor="email" value="Email" />
                        <TextInput
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="mt-1 block w-full rounded-xl"
                            autoComplete="email"
                            required
                        />
                        <InputError message={errors.email} className="mt-1" />
                    </div>

                    <div className="sm:col-span-1">
                        <InputLabel htmlFor="phone" value="Phone (optional)" />
                        <TextInput
                            id="phone"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            className="mt-1 block w-full rounded-xl"
                            autoComplete="tel"
                        />
                        <InputError message={errors.phone} className="mt-1" />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="company_size" value="Company size (optional)" />
                        <select
                            id="company_size"
                            value={data.company_size}
                            onChange={(e) => setData('company_size', e.target.value)}
                            className="mt-1 block w-full rounded-xl border-slate-300 bg-white/90 px-3 py-2 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                        >
                            <option value="">Select…</option>
                            <option value="1-10">1–10</option>
                            <option value="11-50">11–50</option>
                            <option value="51-200">51–200</option>
                            <option value="200+">200+</option>
                        </select>
                        <InputError message={errors.company_size} className="mt-1" />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="message" value="Message (optional)" />
                        <textarea
                            id="message"
                            value={data.message}
                            onChange={(e) => setData('message', e.target.value)}
                            rows={4}
                            className="mt-1 block w-full rounded-xl border-slate-300 bg-white/90 px-3 py-2 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                            placeholder="What do you want to see in the demo?"
                        />
                        <InputError message={errors.message} className="mt-1" />
                    </div>

                    <div className="sm:col-span-2 flex items-center justify-between gap-4 pt-2">
                        <div className="text-xs text-slate-500">By submitting, you agree we may contact you about Crewly.</div>
                        <PrimaryButton className="rounded-xl" disabled={processing}>
                            {processing ? 'Submitting…' : 'Submit'}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    );
}
