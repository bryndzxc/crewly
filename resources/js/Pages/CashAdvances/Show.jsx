import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/UI/PageHeader';
import Card from '@/Components/UI/Card';
import Badge from '@/Components/UI/Badge';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useMemo } from 'react';

function fullName(employee) {
    const parts = [employee?.first_name, employee?.middle_name, employee?.last_name, employee?.suffix]
        .map((v) => String(v || '').trim())
        .filter(Boolean);
    return parts.join(' ');
}

function statusTone(status) {
    const s = String(status || '').toUpperCase();
    if (s === 'PENDING') return 'amber';
    if (s === 'APPROVED') return 'success';
    if (s === 'REJECTED') return 'danger';
    if (s === 'COMPLETED') return 'neutral';
    return 'neutral';
}

export default function Show({ auth, cashAdvance, employee, actors = {}, deductions = [], actions = {} }) {
    const flash = usePage().props.flash;

    const approveForm = useForm({
        decision_remarks: cashAdvance?.decision_remarks ?? '',
        approved_at: '',
        installment_amount: cashAdvance?.installment_amount ?? '',
        installments_count: cashAdvance?.installments_count ?? '',
    });

    const rejectForm = useForm({
        decision_remarks: cashAdvance?.decision_remarks ?? '',
        rejected_at: '',
    });

    const deductionForm = useForm({
        deducted_at: new Date().toISOString().slice(0, 10),
        amount: '',
        notes: '',
    });

    const remaining = Number(cashAdvance?.remaining_balance || 0);

    const canApprove = Boolean(actions?.approve);
    const canReject = Boolean(actions?.reject);
    const canAddDeduction = Boolean(actions?.addDeduction);

    const showDecisionSection = useMemo(() => {
        const status = String(cashAdvance?.status || '').toUpperCase();
        return status === 'PENDING';
    }, [cashAdvance?.status]);

    function submitApprove(e) {
        e.preventDefault();
        approveForm.post(route('cash_advances.approve', cashAdvance.id), {
            preserveScroll: true,
        });
    }

    function submitReject(e) {
        e.preventDefault();
        rejectForm.post(route('cash_advances.reject', cashAdvance.id), {
            preserveScroll: true,
        });
    }

    function submitDeduction(e) {
        e.preventDefault();
        deductionForm.post(route('cash_advances.deductions.store', cashAdvance.id), {
            preserveScroll: true,
            onSuccess: () => deductionForm.reset('amount', 'notes'),
        });
    }

    return (
        <AuthenticatedLayout user={auth.user} header="Cash Advance" contentClassName="max-w-none">
            <Head title="Cash Advance" />

            <div className="w-full space-y-4">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}

                <div className="flex items-center justify-between gap-2">
                    <PageHeader
                        title="Cash Advance"
                        subtitle={`Employee: ${fullName(employee) || employee?.employee_code || 'Employee'}`}
                    />
                    <Link href={route('cash_advances.index')}>
                        <SecondaryButton type="button">Back</SecondaryButton>
                    </Link>
                </div>

                <Card className="p-6">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div className="text-sm font-semibold text-slate-900">Request Details</div>
                            <div className="mt-2 grid grid-cols-1 gap-2 text-sm text-slate-700">
                                <div><span className="text-slate-500">Amount:</span> {Number(cashAdvance.amount || 0).toFixed(2)}</div>
                                <div><span className="text-slate-500">Date Requested:</span> {cashAdvance.requested_at ?? '—'}</div>
                                <div><span className="text-slate-500">Status:</span> <Badge tone={statusTone(cashAdvance.status)}>{String(cashAdvance.status || '—')}</Badge></div>
                                <div><span className="text-slate-500">Remaining Balance:</span> {remaining.toFixed(2)}</div>
                                {!!cashAdvance?.reason && (
                                    <div><span className="text-slate-500">Reason:</span> {cashAdvance.reason}</div>
                                )}
                            </div>
                        </div>

                        <div className="text-sm text-slate-700">
                            <div className="text-sm font-semibold text-slate-900">Attachment</div>
                            <div className="mt-2">
                                {cashAdvance?.has_attachment && cashAdvance?.attachment_download_url ? (
                                    <a
                                        className="text-amber-700 hover:text-amber-800 font-medium"
                                        href={cashAdvance.attachment_download_url}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        Download ({cashAdvance.attachment_original_name || 'attachment'})
                                    </a>
                                ) : (
                                    <div className="text-slate-500">No attachment.</div>
                                )}
                            </div>
                        </div>
                    </div>
                </Card>

                {showDecisionSection && (canApprove || canReject) && (
                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        {canApprove && (
                            <Card className="p-6">
                                <div className="text-sm font-semibold text-slate-900">Approve</div>
                                <form onSubmit={submitApprove} className="mt-4 space-y-3">
                                    <div>
                                        <InputLabel value="Installment Amount" />
                                        <TextInput
                                            type="number"
                                            step="0.01"
                                            value={approveForm.data.installment_amount}
                                            onChange={(e) => approveForm.setData('installment_amount', e.target.value)}
                                            className="mt-1 block w-full"
                                        />
                                        <InputError message={approveForm.errors.installment_amount} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel value="Number of Installments" />
                                        <TextInput
                                            type="number"
                                            value={approveForm.data.installments_count}
                                            onChange={(e) => approveForm.setData('installments_count', e.target.value)}
                                            className="mt-1 block w-full"
                                        />
                                        <InputError message={approveForm.errors.installments_count} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel value="Approval Date (optional)" />
                                        <input
                                            type="date"
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                            value={approveForm.data.approved_at}
                                            onChange={(e) => approveForm.setData('approved_at', e.target.value)}
                                        />
                                        <InputError message={approveForm.errors.approved_at} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel value="Remarks (optional)" />
                                        <textarea
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                            rows={3}
                                            value={approveForm.data.decision_remarks}
                                            onChange={(e) => approveForm.setData('decision_remarks', e.target.value)}
                                        />
                                        <InputError message={approveForm.errors.decision_remarks} className="mt-2" />
                                    </div>
                                    <div className="flex items-center justify-end">
                                        <PrimaryButton type="submit" disabled={approveForm.processing}>
                                            {approveForm.processing ? 'Approving…' : 'Approve'}
                                        </PrimaryButton>
                                    </div>
                                </form>
                            </Card>
                        )}

                        {canReject && (
                            <Card className="p-6">
                                <div className="text-sm font-semibold text-slate-900">Reject</div>
                                <form onSubmit={submitReject} className="mt-4 space-y-3">
                                    <div>
                                        <InputLabel value="Rejection Date (optional)" />
                                        <input
                                            type="date"
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                            value={rejectForm.data.rejected_at}
                                            onChange={(e) => rejectForm.setData('rejected_at', e.target.value)}
                                        />
                                        <InputError message={rejectForm.errors.rejected_at} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel value="Remarks (optional)" />
                                        <textarea
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                            rows={3}
                                            value={rejectForm.data.decision_remarks}
                                            onChange={(e) => rejectForm.setData('decision_remarks', e.target.value)}
                                        />
                                        <InputError message={rejectForm.errors.decision_remarks} className="mt-2" />
                                    </div>
                                    <div className="flex items-center justify-end">
                                        <PrimaryButton type="submit" disabled={rejectForm.processing}>
                                            {rejectForm.processing ? 'Rejecting…' : 'Reject'}
                                        </PrimaryButton>
                                    </div>
                                </form>
                            </Card>
                        )}
                    </div>
                )}

                <Card className="p-6">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="text-sm font-semibold text-slate-900">Deduction History</div>
                            <div className="mt-1 text-xs text-slate-500">Log of all deductions applied to this cash advance.</div>
                        </div>
                    </div>

                    {canAddDeduction && remaining > 0 && (
                        <form onSubmit={submitDeduction} className="mt-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                            <div>
                                <InputLabel value="Deducted At" />
                                <input
                                    type="date"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                    value={deductionForm.data.deducted_at}
                                    onChange={(e) => deductionForm.setData('deducted_at', e.target.value)}
                                />
                                <InputError message={deductionForm.errors.deducted_at} className="mt-2" />
                            </div>
                            <div>
                                <InputLabel value="Amount" />
                                <TextInput
                                    type="number"
                                    step="0.01"
                                    value={deductionForm.data.amount}
                                    onChange={(e) => deductionForm.setData('amount', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={deductionForm.errors.amount} className="mt-2" />
                            </div>
                            <div className="md:col-span-2">
                                <InputLabel value="Notes (optional)" />
                                <TextInput
                                    value={deductionForm.data.notes}
                                    onChange={(e) => deductionForm.setData('notes', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="e.g., March payroll"
                                />
                                <InputError message={deductionForm.errors.notes} className="mt-2" />
                            </div>
                            <div className="md:col-span-4 flex items-center justify-end">
                                <PrimaryButton type="submit" disabled={deductionForm.processing}>
                                    {deductionForm.processing ? 'Saving…' : 'Add Deduction'}
                                </PrimaryButton>
                            </div>
                        </form>
                    )}

                    <div className="mt-4 overflow-x-auto">
                        <table className="min-w-[640px] w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Deducted At</th>
                                    <th className="px-3 py-2 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Amount</th>
                                    <th className="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Notes</th>
                                    <th className="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Recorded By</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {(deductions ?? []).length === 0 && (
                                    <tr>
                                        <td className="px-3 py-3 text-sm text-slate-600" colSpan={4}>No deductions yet.</td>
                                    </tr>
                                )}
                                {(deductions ?? []).map((d) => (
                                    <tr key={d.id}>
                                        <td className="px-3 py-3 text-sm text-slate-700">{d.deducted_at ?? '—'}</td>
                                        <td className="px-3 py-3 text-sm text-right text-slate-700">{Number(d.amount || 0).toFixed(2)}</td>
                                        <td className="px-3 py-3 text-sm text-slate-700">{d.notes || '—'}</td>
                                        <td className="px-3 py-3 text-sm text-slate-700">{d.created_by?.name ?? '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
