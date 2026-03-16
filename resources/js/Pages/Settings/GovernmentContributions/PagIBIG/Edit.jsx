import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Edit({ auth, setting }) {
    const flash = usePage().props.flash;

    const form = useForm({
        effective_from: setting?.effective_from || '',
        effective_to: setting?.effective_to || '',
        employee_rate_below_threshold: setting?.employee_rate_below_threshold ?? '',
        employee_rate_above_threshold: setting?.employee_rate_above_threshold ?? '',
        employer_rate: setting?.employer_rate ?? '',
        salary_threshold: setting?.salary_threshold ?? '',
        monthly_cap: setting?.monthly_cap ?? '',
        notes: setting?.notes ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.put(route('settings.government_contributions.pagibig.update', setting.id));
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Settings" contentClassName="max-w-4xl mx-auto">
            <Head title="Edit Pag-IBIG Setting" />

            <div className="space-y-4">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}

                <div className="flex items-center justify-between">
                    <div>
                        <div className="text-lg font-semibold text-slate-900">Edit Pag-IBIG setting</div>
                        <div className="text-sm text-slate-600">Update threshold, rates, and cap.</div>
                    </div>
                    <Link href={route('settings.government_contributions.pagibig.index')}>
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

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Salary threshold (optional)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.salary_threshold}
                                onChange={(e) => form.setData('salary_threshold', e.target.value)}
                            />
                            {form.errors.salary_threshold && <div className="mt-1 text-sm text-red-600">{form.errors.salary_threshold}</div>}
                        </div>
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Monthly cap (optional)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.monthly_cap}
                                onChange={(e) => form.setData('monthly_cap', e.target.value)}
                            />
                            {form.errors.monthly_cap && <div className="mt-1 text-sm text-red-600">{form.errors.monthly_cap}</div>}
                        </div>
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Employer rate (optional)</label>
                            <input
                                type="number"
                                step="0.0001"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.employer_rate}
                                onChange={(e) => form.setData('employer_rate', e.target.value)}
                            />
                            {form.errors.employer_rate && <div className="mt-1 text-sm text-red-600">{form.errors.employer_rate}</div>}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Employee rate below threshold (optional)</label>
                            <input
                                type="number"
                                step="0.0001"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.employee_rate_below_threshold}
                                onChange={(e) => form.setData('employee_rate_below_threshold', e.target.value)}
                            />
                            {form.errors.employee_rate_below_threshold && (
                                <div className="mt-1 text-sm text-red-600">{form.errors.employee_rate_below_threshold}</div>
                            )}
                        </div>
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Employee rate above threshold (optional)</label>
                            <input
                                type="number"
                                step="0.0001"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.employee_rate_above_threshold}
                                onChange={(e) => form.setData('employee_rate_above_threshold', e.target.value)}
                            />
                            {form.errors.employee_rate_above_threshold && (
                                <div className="mt-1 text-sm text-red-600">{form.errors.employee_rate_above_threshold}</div>
                            )}
                        </div>
                    </div>

                    <div>
                        <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Notes (optional)</label>
                        <input
                            className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                        />
                        {form.errors.notes && <div className="mt-1 text-sm text-red-600">{form.errors.notes}</div>}
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
