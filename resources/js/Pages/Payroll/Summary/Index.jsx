import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import StatCard from '@/Components/UI/StatCard';
import Badge from '@/Components/UI/Badge';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import DatePicker from '@/Components/DatePicker';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function PayrollSummaryIndex({
    auth,
    filters = {},
    run = null,
    rows = [],
    totals = {},
    actions = {},
}) {
    const flash = usePage().props.flash;
    const can = usePage().props.can || {};

    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');

    const canExport = Boolean(actions?.can_export ?? can.exportPayrollSummary);
    const canGenerate = Boolean(actions?.can_generate ?? can.generatePayroll);
    const canEdit = Boolean(actions?.can_edit ?? can.editPayrollDeductions);
    const canReview = Boolean(actions?.can_review ?? can.reviewPayroll);
    const canFinalize = Boolean(actions?.can_finalize ?? can.finalizePayroll);
    const canRelease = Boolean(actions?.can_release ?? can.releasePayroll);

    const [showBreakdown, setShowBreakdown] = useState(false);
    const [showEdit, setShowEdit] = useState(false);
    const [showConfirmFinalize, setShowConfirmFinalize] = useState(false);
    const [showConfirmRelease, setShowConfirmRelease] = useState(false);

    const [selectedRow, setSelectedRow] = useState(null);

    const [editTax, setEditTax] = useState('0');
    const [editOtherDeductions, setEditOtherDeductions] = useState('0');
    const [editNotes, setEditNotes] = useState('');

    const errors = usePage().props.errors || {};

    useEffect(() => {
        setFrom(filters.from || '');
        setTo(filters.to || '');
    }, [filters.from, filters.to]);

    const onLoad = () => {
        router.get(
            route('payroll.summary.index'),
            { from: from || undefined, to: to || undefined },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const onGenerateRun = () => {
        router.post(
            route('payroll.runs.generate'),
            { from: from || undefined, to: to || undefined },
            { preserveScroll: true }
        );
    };

    const exportCsvHref = useMemo(() => {
        if (!canExport) return null;
        if (!from || !to) return null;
        return route('payroll.summary.export', { from, to, format: 'csv' });
    }, [canExport, from, to]);

    const exportExcelHref = useMemo(() => {
        if (!canExport) return null;
        if (!from || !to) return null;
        return route('payroll.summary.export', { from, to, format: 'xlsx' });
    }, [canExport, from, to]);

    const items = Array.isArray(rows) ? rows : [];

    const fmtMoney = (value) => {
        const v = Number(value || 0);
        if (Number.isNaN(v)) return '0.00';
        return v.toFixed(2);
    };

    const runStatus = String(run?.status || '').toLowerCase();
    const isLocked = runStatus === 'finalized' || runStatus === 'released';
    const hasRun = Boolean(run?.id);

    const statusTone = useMemo(() => {
        if (runStatus === 'released') return 'success';
        if (runStatus === 'finalized') return 'amber';
        if (runStatus === 'reviewed') return 'amber';
        return 'neutral';
    }, [runStatus]);

    const cardTotals = {
        employees: Number(totals?.employees ?? items.length ?? 0),
        total_gross_pay: Number(totals?.total_gross_pay ?? 0),
        total_deductions: Number(totals?.total_deductions ?? 0),
        total_net_pay: Number(totals?.total_net_pay ?? 0),
    };

    const openBreakdown = (row) => {
        setSelectedRow(row);
        setShowBreakdown(true);
    };

    const openEdit = (row) => {
        setSelectedRow(row);
        setEditTax(String(row?.tax_deduction ?? 0));
        setEditOtherDeductions(String(row?.other_deductions ?? 0));
        setEditNotes(String(row?.deduction_notes ?? ''));
        setShowEdit(true);
    };

    const submitEdit = () => {
        if (!selectedRow?.id) return;

        router.patch(
            route('payroll.run-items.update', { item: selectedRow.id }),
            {
                tax_deduction: editTax,
                other_deductions: editOtherDeductions,
                deduction_notes: editNotes || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setShowEdit(false);
                },
            }
        );
    };

    const doMarkReviewed = () => {
        if (!run?.id) return;
        router.post(route('payroll.runs.review', { run: run.id }), {}, { preserveScroll: true });
    };

    const doFinalize = () => {
        if (!run?.id) return;
        router.post(
            route('payroll.runs.finalize', { run: run.id }),
            {},
            {
                preserveScroll: true,
                onSuccess: () => setShowConfirmFinalize(false),
            }
        );
    };

    const doRelease = () => {
        if (!run?.id) return;
        router.post(
            route('payroll.runs.release', { run: run.id }),
            {},
            {
                preserveScroll: true,
                onSuccess: () => setShowConfirmRelease(false),
            }
        );
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Payroll Summary" contentClassName="max-w-none">
            <Head title="Payroll Summary" />

            <PageHeader title="Payroll Summary" subtitle="Production payroll register (stored gross, deductions, net)." />

            <div className="w-full space-y-4">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}

                <Card className="relative p-6">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Date From</div>
                                <DatePicker value={from} onChange={setFrom} />
                            </div>
                            <div>
                                <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Date To</div>
                                <DatePicker value={to} onChange={setTo} />
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <SecondaryButton onClick={onLoad} disabled={!from || !to}>
                                Load
                            </SecondaryButton>

                            {canGenerate && (
                                <PrimaryButton onClick={onGenerateRun} disabled={!from || !to || (hasRun && isLocked)}>
                                    {hasRun ? 'Regenerate' : 'Generate'}
                                </PrimaryButton>
                            )}

                            {canExport && exportCsvHref && (
                                <a
                                    href={exportCsvHref}
                                    className="inline-flex items-center rounded-md border border-amber-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-amber-900 shadow-sm hover:bg-amber-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2"
                                >
                                    Export CSV
                                </a>
                            )}

                            {canExport && exportExcelHref && (
                                <a
                                    href={exportExcelHref}
                                    className="inline-flex items-center rounded-md border border-amber-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-amber-900 shadow-sm hover:bg-amber-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2"
                                >
                                    Export Excel
                                </a>
                            )}
                        </div>
                    </div>

                    <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
                        <div>
                            Period: {run?.period_start ?? (from || '—')} to {run?.period_end ?? (to || '—')}
                        </div>

                        <div className="flex items-center gap-2">
                            <Badge tone={statusTone}>{run?.status ? String(run.status).toUpperCase() : 'NO RUN'}</Badge>
                            <span>Generated: {run?.generated_at ?? '—'}</span>
                            <span>By: {run?.generated_by ?? '—'}</span>
                        </div>
                    </div>

                    {hasRun && (
                        <div className="mt-4 flex flex-wrap items-center gap-3">
                            {runStatus === 'draft' && canReview && (
                                <SecondaryButton onClick={doMarkReviewed}>Mark Reviewed</SecondaryButton>
                            )}

                            {runStatus === 'reviewed' && canFinalize && (
                                <DangerButton onClick={() => setShowConfirmFinalize(true)}>Finalize</DangerButton>
                            )}

                            {runStatus === 'finalized' && canRelease && (
                                <PrimaryButton onClick={() => setShowConfirmRelease(true)}>Release</PrimaryButton>
                            )}
                        </div>
                    )}
                </Card>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard title="Employees" value={cardTotals.employees} caption="Included in payroll" />
                    <StatCard title="Total Gross" value={fmtMoney(cardTotals.total_gross_pay)} caption="Sum of gross pay" />
                    <StatCard title="Total Deductions" value={fmtMoney(cardTotals.total_deductions)} caption="Sum of deductions" />
                    <StatCard title="Total Net" value={fmtMoney(cardTotals.total_net_pay)} caption="Sum of net pay" />
                </div>

                <Card className="p-6">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h3 className="text-base font-semibold text-slate-900">Payroll Register</h3>
                            <p className="mt-1 text-sm text-slate-600">Per-employee stored payroll values for the selected period.</p>
                        </div>
                    </div>

                    <div className="mt-4 overflow-x-auto">
                        <table className="min-w-[1700px] w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50 sticky top-0">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Code</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Employee</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Department</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Position</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Basic Pay</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Allowances</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Other Earnings</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Gross Pay</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Tax</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Other Deductions</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Total Deductions</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Net Pay</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 bg-white">
                                {items.length === 0 && (
                                    <tr>
                                        <td className="px-4 py-10" colSpan={13}>
                                            <div className="mx-auto max-w-xl rounded-2xl border border-amber-200/60 bg-amber-50/40 p-6">
                                                <div className="text-sm font-semibold text-slate-900">No data</div>
                                                <div className="mt-1 text-sm text-slate-600">Generate a payroll run to see stored register rows.</div>
                                            </div>
                                        </td>
                                    </tr>
                                )}

                                {items.map((r) => (
                                    <tr key={r.employee_id} className="hover:bg-amber-50/30">
                                        <td className="px-4 py-3 text-sm font-medium text-slate-900 whitespace-nowrap">{r.employee_code}</td>
                                        <td className="px-4 py-3 text-sm text-slate-900 whitespace-nowrap">{r.employee_name}</td>
                                        <td className="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">{r.department || '—'}</td>
                                        <td className="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">{r.position_title || '—'}</td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums">{fmtMoney(r.basic_pay)}</td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums">{fmtMoney(r.allowances_total)}</td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums">{fmtMoney(r.other_earnings)}</td>
                                        <td className="px-4 py-3 text-sm text-right font-semibold text-slate-900 tabular-nums">{fmtMoney(r.gross_pay)}</td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums">{fmtMoney(r.tax_deduction)}</td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums">{fmtMoney(r.other_deductions)}</td>
                                        <td className="px-4 py-3 text-sm text-right text-slate-700 tabular-nums">{fmtMoney(r.total_deductions)}</td>
                                        <td className="px-4 py-3 text-sm text-right font-semibold text-slate-900 tabular-nums">{fmtMoney(r.net_pay)}</td>
                                        <td className="px-4 py-3 text-sm text-right whitespace-nowrap">
                                            <div className="flex items-center justify-end gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => openBreakdown(r)}
                                                    className="inline-flex items-center rounded-md border border-slate-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-slate-900 shadow-sm hover:bg-slate-50"
                                                >
                                                    View
                                                </button>

                                                <button
                                                    type="button"
                                                    onClick={() => openEdit(r)}
                                                    disabled={!canEdit || isLocked}
                                                    className={
                                                        'inline-flex items-center rounded-md border px-3 py-2 text-xs font-semibold uppercase tracking-widest shadow-sm ' +
                                                        (!canEdit || isLocked
                                                            ? 'border-slate-200 bg-slate-50 text-slate-400'
                                                            : 'border-amber-200 bg-white text-amber-900 hover:bg-amber-50')
                                                    }
                                                >
                                                    Edit
                                                </button>

                                                {from && to ? (
                                                    <a
                                                        href={route('payroll.payslip.show', { employee: r.employee_id, period: `${from}_${to}` })}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="inline-flex items-center rounded-md border border-amber-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-amber-900 shadow-sm hover:bg-amber-50"
                                                    >
                                                        Payslip
                                                    </a>
                                                ) : null}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <Modal show={showBreakdown} onClose={() => setShowBreakdown(false)} maxWidth="2xl">
                <div className="p-6">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <div className="text-sm font-semibold text-slate-900">Payroll Breakdown</div>
                            <div className="mt-1 text-sm text-slate-600">{selectedRow?.employee_name || '—'} ({selectedRow?.employee_code || '—'})</div>
                        </div>
                        <SecondaryButton onClick={() => setShowBreakdown(false)}>Close</SecondaryButton>
                    </div>

                    <div className="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div className="rounded-lg border border-slate-200 p-4">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Earnings</div>
                            <div className="mt-3 space-y-2 text-sm">
                                <div className="flex items-center justify-between"><span>Basic Pay</span><span className="tabular-nums">{fmtMoney(selectedRow?.basic_pay)}</span></div>
                                <div className="flex items-center justify-between"><span>Allowances</span><span className="tabular-nums">{fmtMoney(selectedRow?.allowances_total)}</span></div>
                                <div className="flex items-center justify-between"><span>Other Earnings</span><span className="tabular-nums">{fmtMoney(selectedRow?.other_earnings)}</span></div>
                                <div className="mt-2 flex items-center justify-between border-t border-slate-200 pt-2 font-semibold"><span>Gross Pay</span><span className="tabular-nums">{fmtMoney(selectedRow?.gross_pay)}</span></div>
                            </div>
                        </div>

                        <div className="rounded-lg border border-slate-200 p-4">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Deductions</div>
                            <div className="mt-3 space-y-2 text-sm">
                                <div className="flex items-center justify-between"><span>SSS</span><span className="tabular-nums">{fmtMoney(selectedRow?.sss_employee)}</span></div>
                                <div className="flex items-center justify-between"><span>PhilHealth</span><span className="tabular-nums">{fmtMoney(selectedRow?.philhealth_employee)}</span></div>
                                <div className="flex items-center justify-between"><span>Pag-IBIG</span><span className="tabular-nums">{fmtMoney(selectedRow?.pagibig_employee)}</span></div>
                                <div className="flex items-center justify-between"><span>Cash Advance</span><span className="tabular-nums">{fmtMoney(selectedRow?.cash_advance_deduction)}</span></div>
                                <div className="flex items-center justify-between"><span>Tax</span><span className="tabular-nums">{fmtMoney(selectedRow?.tax_deduction)}</span></div>
                                <div className="flex items-center justify-between"><span>Other Deductions</span><span className="tabular-nums">{fmtMoney(selectedRow?.other_deductions)}</span></div>
                                <div className="mt-2 flex items-center justify-between border-t border-slate-200 pt-2 font-semibold"><span>Total Deductions</span><span className="tabular-nums">{fmtMoney(selectedRow?.total_deductions)}</span></div>
                                <div className="mt-2 flex items-center justify-between border-t border-slate-200 pt-2 font-semibold text-slate-900"><span>Net Pay</span><span className="tabular-nums">{fmtMoney(selectedRow?.net_pay)}</span></div>
                            </div>
                        </div>
                    </div>

                    {selectedRow?.deduction_notes ? (
                        <div className="mt-6 rounded-lg border border-slate-200 p-4">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Notes</div>
                            <div className="mt-2 text-sm text-slate-700 whitespace-pre-wrap">{String(selectedRow.deduction_notes)}</div>
                        </div>
                    ) : null}
                </div>
            </Modal>

            <Modal show={showEdit} onClose={() => setShowEdit(false)} maxWidth="2xl">
                <div className="p-6">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <div className="text-sm font-semibold text-slate-900">Edit Tax & Deductions</div>
                            <div className="mt-1 text-sm text-slate-600">{selectedRow?.employee_name || '—'} ({selectedRow?.employee_code || '—'})</div>
                        </div>
                        <SecondaryButton onClick={() => setShowEdit(false)}>Close</SecondaryButton>
                    </div>

                    {isLocked && (
                        <div className="mt-4 rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                            This payroll run is locked (finalized/released). Editing is disabled.
                        </div>
                    )}

                    <div className="mt-6 grid grid-cols-1 gap-4">
                        <div>
                            <InputLabel value="Tax Deduction" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={editTax}
                                onChange={(e) => setEditTax(e.target.value)}
                                disabled={!canEdit || isLocked}
                            />
                            <InputError message={errors.tax_deduction} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel value="Other Deductions" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={editOtherDeductions}
                                onChange={(e) => setEditOtherDeductions(e.target.value)}
                                disabled={!canEdit || isLocked}
                            />
                            <InputError message={errors.other_deductions} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel value="Notes (optional)" />
                            <textarea
                                className="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                rows={4}
                                value={editNotes}
                                onChange={(e) => setEditNotes(e.target.value)}
                                disabled={!canEdit || isLocked}
                            />
                            <InputError message={errors.deduction_notes} className="mt-2" />
                        </div>
                    </div>

                    <div className="mt-6 flex items-center justify-end gap-3">
                        <SecondaryButton onClick={() => setShowEdit(false)}>Cancel</SecondaryButton>
                        <PrimaryButton onClick={submitEdit} disabled={!canEdit || isLocked}>
                            Save
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>

            <Modal show={showConfirmFinalize} onClose={() => setShowConfirmFinalize(false)} maxWidth="lg">
                <div className="p-6">
                    <div className="text-sm font-semibold text-slate-900">Finalize Payroll Run?</div>
                    <div className="mt-2 text-sm text-slate-600">Finalized runs are locked and can’t be regenerated or edited.</div>
                    <div className="mt-6 flex items-center justify-end gap-3">
                        <SecondaryButton onClick={() => setShowConfirmFinalize(false)}>Cancel</SecondaryButton>
                        <DangerButton onClick={doFinalize}>Finalize</DangerButton>
                    </div>
                </div>
            </Modal>

            <Modal show={showConfirmRelease} onClose={() => setShowConfirmRelease(false)} maxWidth="lg">
                <div className="p-6">
                    <div className="text-sm font-semibold text-slate-900">Release Payroll Run?</div>
                    <div className="mt-2 text-sm text-slate-600">Released payrolls can be viewed by employees and should be treated as final.</div>
                    <div className="mt-6 flex items-center justify-end gap-3">
                        <SecondaryButton onClick={() => setShowConfirmRelease(false)}>Cancel</SecondaryButton>
                        <PrimaryButton onClick={doRelease}>Release</PrimaryButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
