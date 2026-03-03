import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/UI/PageHeader';
import Card from '@/Components/UI/Card';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Create({ auth }) {
    const flash = usePage().props.flash;

    const form = useForm({
        amount: '',
        reason: '',
        requested_at: new Date().toISOString().slice(0, 10),
        attachment: null,
    });

    function submit(e) {
        e.preventDefault();

        form.post(route('my.cash_advances.store'), {
            forceFormData: true,
            preserveScroll: true,
        });
    }

    return (
        <AuthenticatedLayout user={auth.user} header="Request Cash Advance">
            <Head title="Request Cash Advance" />

            <PageHeader title="Request Cash Advance" subtitle="Submit an authorization to deduct / cash advance request." />

            <div className="w-full space-y-4">
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}

                <Card className="p-6">
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <InputLabel value="Amount Requested" />
                            <TextInput
                                type="number"
                                step="0.01"
                                value={form.data.amount}
                                onChange={(e) => form.setData('amount', e.target.value)}
                                className="mt-1 block w-full"
                                placeholder="0.00"
                            />
                            <InputError message={form.errors.amount} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel value="Reason" />
                            <textarea
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                rows={4}
                                value={form.data.reason}
                                onChange={(e) => form.setData('reason', e.target.value)}
                                placeholder="Why do you need this cash advance?"
                            />
                            <InputError message={form.errors.reason} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel value="Date Requested" />
                            <input
                                type="date"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.requested_at}
                                onChange={(e) => form.setData('requested_at', e.target.value)}
                            />
                            <InputError message={form.errors.requested_at} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel value="Attachment (optional)" />
                            <input
                                type="file"
                                accept="application/pdf,image/jpeg,image/png"
                                className="mt-1 block w-full text-sm"
                                onChange={(e) => {
                                    const file = e.target.files?.[0] ?? null;
                                    form.setData('attachment', file);
                                }}
                            />
                            <div className="mt-1 text-xs text-slate-500">Allowed: PDF/JPG/PNG up to 10MB.</div>
                            <InputError message={form.errors.attachment} className="mt-2" />
                        </div>

                        <div className="flex items-center justify-end gap-2">
                            <Link href={route('my.cash_advances.index')}>
                                <SecondaryButton type="button">Cancel</SecondaryButton>
                            </Link>
                            <PrimaryButton type="submit" disabled={form.processing}>
                                {form.processing ? 'Submitting…' : 'Submit Request'}
                            </PrimaryButton>
                        </div>
                    </form>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
