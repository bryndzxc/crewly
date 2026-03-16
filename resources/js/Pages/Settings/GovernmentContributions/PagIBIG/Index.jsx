import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import EmptyState from '@/Components/UI/EmptyState';
import Modal from '@/Components/Modal';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ auth, settings = [] }) {
    const flash = usePage().props.flash;
    const can = usePage().props.can ?? {};
    const items = Array.isArray(settings) ? settings : [];

    const [confirmOpen, setConfirmOpen] = useState(false);

    const archive = (id) => {
        router.patch(route('settings.government_contributions.pagibig.archive', id), {}, { preserveScroll: true });
    };

    const loadDefaults = () => {
        router.post(route('settings.government_defaults.pagibig_2025'), {}, { preserveScroll: true, onFinish: () => setConfirmOpen(false) });
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Settings" contentClassName="max-w-none">
            <Head title="Pag-IBIG Contribution Settings" />

            <div className="space-y-4">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}

                <div className="flex items-center justify-between gap-3">
                    <div>
                        <div className="text-lg font-semibold text-slate-900">Pag-IBIG settings</div>
                        <div className="text-sm text-slate-600">Effectivity-based thresholds, rates, and caps.</div>
                    </div>

                    <div className="flex items-center gap-2">
                        {can.manageGovernmentContributions ? (
                            <SecondaryButton type="button" onClick={() => setConfirmOpen(true)}>
                                Load Default 2025 Rates
                            </SecondaryButton>
                        ) : null}
                        <Link href={route('settings.government_contributions.pagibig.create')}>
                            <PrimaryButton>Add setting</PrimaryButton>
                        </Link>
                    </div>
                </div>

                <div className="bg-white border border-slate-200 rounded-lg overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-[1200px] w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Effective</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Threshold</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Emp. rate below</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Emp. rate above</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Employer rate</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Monthly cap</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Notes</th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 bg-white">
                                {items.length === 0 && (
                                    <tr>
                                        <td colSpan={8} className="px-4 py-10 text-center text-sm text-slate-600">
                                            <EmptyState
                                                title="No Pag-IBIG settings yet"
                                                description="Add at least one setting so payroll can compute Pag-IBIG contributions via DB config."
                                                actionLabel="Add Pag-IBIG setting"
                                                actionHref={route('settings.government_contributions.pagibig.create')}
                                            />
                                        </td>
                                    </tr>
                                )}

                                {items.map((s) => (
                                    <tr key={s.id} className="hover:bg-amber-50/30">
                                        <td className="px-4 py-3 text-sm text-slate-900 whitespace-nowrap">
                                            <div className="font-medium">{s.effective_from || '—'} → {s.effective_to || 'Present'}</div>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums whitespace-nowrap">
                                            {s.salary_threshold === null ? '—' : Number(s.salary_threshold || 0).toFixed(2)}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums whitespace-nowrap">
                                            {s.employee_rate_below_threshold === null ? '—' : Number(s.employee_rate_below_threshold || 0).toFixed(4)}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums whitespace-nowrap">
                                            {s.employee_rate_above_threshold === null ? '—' : Number(s.employee_rate_above_threshold || 0).toFixed(4)}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums whitespace-nowrap">
                                            {s.employer_rate === null ? '—' : Number(s.employer_rate || 0).toFixed(4)}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums whitespace-nowrap">
                                            {s.monthly_cap === null ? '—' : Number(s.monthly_cap || 0).toFixed(2)}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-slate-700 max-w-[360px] truncate">{s.notes || '—'}</td>
                                        <td className="px-4 py-3 text-right whitespace-nowrap">
                                            <Link
                                                href={route('settings.government_contributions.pagibig.edit', s.id)}
                                                className="text-amber-700 hover:text-amber-900 text-sm font-medium"
                                            >
                                                Edit
                                            </Link>
                                            <SecondaryButton type="button" className="ml-3" onClick={() => archive(s.id)}>
                                                Archive
                                            </SecondaryButton>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <Modal show={confirmOpen} onClose={() => setConfirmOpen(false)} maxWidth="lg">
                <div className="px-6 py-5 border-b border-slate-200">
                    <div className="text-base font-semibold text-slate-900">Load Default 2025 Rates</div>
                    <div className="mt-1 text-sm text-slate-600">
                        Are you sure you want to load the default 2025 rates? Existing entries with the same effective date should not be duplicated.
                    </div>
                </div>

                <div className="px-6 py-5">
                    <div className="flex items-center justify-end gap-2">
                        <SecondaryButton type="button" onClick={() => setConfirmOpen(false)}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="button" onClick={loadDefaults}>
                            Load defaults
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
