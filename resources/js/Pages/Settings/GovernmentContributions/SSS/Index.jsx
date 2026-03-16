import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import EmptyState from '@/Components/UI/EmptyState';
import Modal from '@/Components/Modal';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ auth, rules = [] }) {
    const flash = usePage().props.flash;
    const can = usePage().props.can ?? {};
    const items = Array.isArray(rules) ? rules : [];

    const [confirmOpen, setConfirmOpen] = useState(false);

    const archive = (id) => {
        router.patch(route('settings.government_contributions.sss.archive', id), {}, { preserveScroll: true });
    };

    const loadDefaults = () => {
        router.post(route('settings.government_defaults.sss_2025'), {}, { preserveScroll: true, onFinish: () => setConfirmOpen(false) });
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Settings" contentClassName="max-w-none">
            <Head title="SSS Contribution Tables" />

            <div className="space-y-4">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}

                <div className="flex items-center justify-between gap-3">
                    <div>
                        <div className="text-lg font-semibold text-slate-900">SSS contribution table</div>
                        <div className="text-sm text-slate-600">Effectivity-based salary ranges and shares.</div>
                    </div>

                    <div className="flex items-center gap-2">
                        {can.manageGovernmentContributions ? (
                            <SecondaryButton type="button" onClick={() => setConfirmOpen(true)}>
                                Load Default 2025 Rates
                            </SecondaryButton>
                        ) : null}
                        <Link href={route('settings.government_contributions.sss.create')}>
                            <PrimaryButton>Add rule</PrimaryButton>
                        </Link>
                    </div>
                </div>

                <div className="bg-white border border-slate-200 rounded-lg overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-[1100px] w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Effective</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Range</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">MSC</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Employee</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Employer</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">EC</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Notes</th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 bg-white">
                                {items.length === 0 && (
                                    <tr>
                                        <td colSpan={8} className="px-4 py-10 text-center text-sm text-slate-600">
                                            <EmptyState
                                                title="No SSS rules yet"
                                                description="Add at least one rule so payroll can compute SSS contributions via DB config."
                                                actionLabel="Add SSS rule"
                                                actionHref={route('settings.government_contributions.sss.create')}
                                            />
                                        </td>
                                    </tr>
                                )}

                                {items.map((r) => (
                                    <tr key={r.id} className="hover:bg-amber-50/30">
                                        <td className="px-4 py-3 text-sm text-slate-900 whitespace-nowrap">
                                            <div className="font-medium">{r.effective_from || '—'} → {r.effective_to || 'Present'}</div>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums whitespace-nowrap">
                                            {Number(r.range_from || 0).toFixed(2)} - {Number(r.range_to || 0).toFixed(2)}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums whitespace-nowrap">
                                            {r.monthly_salary_credit === null ? '—' : Number(r.monthly_salary_credit || 0).toFixed(2)}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums whitespace-nowrap">{Number(r.employee_share || 0).toFixed(2)}</td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums whitespace-nowrap">{Number(r.employer_share || 0).toFixed(2)}</td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums whitespace-nowrap">
                                            {r.ec_share === null ? '—' : Number(r.ec_share || 0).toFixed(2)}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-slate-700 max-w-[360px] truncate">{r.notes || '—'}</td>
                                        <td className="px-4 py-3 text-right whitespace-nowrap">
                                            <Link
                                                href={route('settings.government_contributions.sss.edit', r.id)}
                                                className="text-amber-700 hover:text-amber-900 text-sm font-medium"
                                            >
                                                Edit
                                            </Link>
                                            <SecondaryButton type="button" className="ml-3" onClick={() => archive(r.id)}>
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
