import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Edit({ auth, setting }) {
    const flash = usePage().props.flash;

    const form = useForm({
        effective_from: setting?.effective_from || '',
        effective_to: setting?.effective_to || '',
        premium_rate: setting?.premium_rate ?? '',
        salary_floor: setting?.salary_floor ?? '',
        salary_ceiling: setting?.salary_ceiling ?? '',
        employee_share_percent: setting?.employee_share_percent ?? '',
        employer_share_percent: setting?.employer_share_percent ?? '',
        notes: setting?.notes ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.put(route('settings.government_contributions.philhealth.update', setting.id));
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Settings" contentClassName="max-w-4xl mx-auto">
            <Head title="Edit PhilHealth Setting" />

            <div className="space-y-4">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}

                <div className="flex items-center justify-between">
                    <div>
                        <div className="text-lg font-semibold text-slate-900">Edit PhilHealth setting</div>
                        <div className="text-sm text-slate-600">Update rate, clamp, and split percentages.</div>
                    </div>
                    <Link href={route('settings.government_contributions.philhealth.index')}>
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
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Premium rate</label>
                            <input
                                type="number"
                                step="0.0001"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.premium_rate}
                                onChange={(e) => form.setData('premium_rate', e.target.value)}
                            />
                            {form.errors.premium_rate && <div className="mt-1 text-sm text-red-600">{form.errors.premium_rate}</div>}
                        </div>
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Salary floor</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.salary_floor}
                                onChange={(e) => form.setData('salary_floor', e.target.value)}
                            />
                            {form.errors.salary_floor && <div className="mt-1 text-sm text-red-600">{form.errors.salary_floor}</div>}
                        </div>
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Salary ceiling</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.salary_ceiling}
                                onChange={(e) => form.setData('salary_ceiling', e.target.value)}
                            />
                            {form.errors.salary_ceiling && <div className="mt-1 text-sm text-red-600">{form.errors.salary_ceiling}</div>}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Employee share percent</label>
                            <input
                                type="number"
                                step="0.0001"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.employee_share_percent}
                                onChange={(e) => form.setData('employee_share_percent', e.target.value)}
                            />
                            {form.errors.employee_share_percent && <div className="mt-1 text-sm text-red-600">{form.errors.employee_share_percent}</div>}
                        </div>
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Employer share percent</label>
                            <input
                                type="number"
                                step="0.0001"
                                min="0"
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.employer_share_percent}
                                onChange={(e) => form.setData('employer_share_percent', e.target.value)}
                            />
                            {form.errors.employer_share_percent && <div className="mt-1 text-sm text-red-600">{form.errors.employer_share_percent}</div>}
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
