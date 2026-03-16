import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Edit({ auth, rule }) {
    const flash = usePage().props.flash;

    const form = useForm({
        effective_from: rule?.effective_from || '',
        effective_to: rule?.effective_to || '',
        range_from: rule?.range_from ?? '',
        range_to: rule?.range_to ?? '',
        monthly_salary_credit: rule?.monthly_salary_credit ?? '',
        employee_share: rule?.employee_share ?? '',
        employer_share: rule?.employer_share ?? '',
        ec_share: rule?.ec_share ?? '',
        notes: rule?.notes ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.put(route('settings.government_contributions.sss.update', rule.id));
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Settings" contentClassName="max-w-4xl mx-auto">
            <Head title="Edit SSS Rule" />

            <div className="space-y-4">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}

                <div className="flex items-center justify-between">
                    <div>
                        <div className="text-lg font-semibold text-slate-900">Edit SSS contribution rule</div>
                        <div className="text-sm text-slate-600">Update the effectivity period, salary range, and shares.</div>
                    </div>
                    <Link href={route('settings.government_contributions.sss.index')}>
                        <SecondaryButton>Back</SecondaryButton>
                    </Link>
                </div>

                <form onSubmit={submit} className="bg-white border border-slate-200 rounded-lg p-5 space-y-4">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Effective from</label>
                            <input
                                type="date"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.effective_from}
                                onChange={(e) => form.setData('effective_from', e.target.value)}
                            />
                            {form.errors.effective_from && <div className="mt-1 text-sm text-red-600">{form.errors.effective_from}</div>}
                        </div>
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Effective to (optional)</label>
                            <input
                                type="date"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.effective_to}
                                onChange={(e) => form.setData('effective_to', e.target.value)}
                            />
                            {form.errors.effective_to && <div className="mt-1 text-sm text-red-600">{form.errors.effective_to}</div>}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Range from</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.range_from}
                                onChange={(e) => form.setData('range_from', e.target.value)}
                            />
                            {form.errors.range_from && <div className="mt-1 text-sm text-red-600">{form.errors.range_from}</div>}
                        </div>
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Range to</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.range_to}
                                onChange={(e) => form.setData('range_to', e.target.value)}
                            />
                            {form.errors.range_to && <div className="mt-1 text-sm text-red-600">{form.errors.range_to}</div>}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">MSC (optional)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.monthly_salary_credit}
                                onChange={(e) => form.setData('monthly_salary_credit', e.target.value)}
                            />
                            {form.errors.monthly_salary_credit && <div className="mt-1 text-sm text-red-600">{form.errors.monthly_salary_credit}</div>}
                        </div>
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Employee share</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.employee_share}
                                onChange={(e) => form.setData('employee_share', e.target.value)}
                            />
                            {form.errors.employee_share && <div className="mt-1 text-sm text-red-600">{form.errors.employee_share}</div>}
                        </div>
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Employer share</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.employer_share}
                                onChange={(e) => form.setData('employer_share', e.target.value)}
                            />
                            {form.errors.employer_share && <div className="mt-1 text-sm text-red-600">{form.errors.employer_share}</div>}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">EC share (optional)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.ec_share}
                                onChange={(e) => form.setData('ec_share', e.target.value)}
                            />
                            {form.errors.ec_share && <div className="mt-1 text-sm text-red-600">{form.errors.ec_share}</div>}
                        </div>
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Notes (optional)</label>
                            <input
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.notes}
                                onChange={(e) => form.setData('notes', e.target.value)}
                                placeholder="Reference / remarks"
                            />
                            {form.errors.notes && <div className="mt-1 text-sm text-red-600">{form.errors.notes}</div>}
                        </div>
                    </div>

                    <div className="flex items-center justify-end">
                        <PrimaryButton type="submit" disabled={form.processing}>
                            {form.processing ? 'Saving…' : 'Save changes'}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
