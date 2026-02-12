import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Badge from '@/Components/UI/Badge';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

function fullName(employee) {
    const parts = [employee?.first_name, employee?.middle_name, employee?.last_name, employee?.suffix]
        .map((v) => String(v || '').trim())
        .filter(Boolean);
    return parts.join(' ');
}

function statusTone(status) {
    if (status === 'PENDING') return 'amber';
    if (status === 'APPROVED') return 'success';
    if (status === 'DENIED') return 'danger';
    return 'neutral';
}

export default function Show({ auth, request, employee, leaveType, actors, actions }) {
    const flash = usePage().props.flash;
    const [approveOpen, setApproveOpen] = useState(false);
    const [denyOpen, setDenyOpen] = useState(false);

    const approveForm = useForm({ decision_notes: '' });
    const denyForm = useForm({ decision_notes: '' });

    const employeeLabel = useMemo(() => fullName(employee) || employee?.employee_code || 'Employee', [employee]);

    const onCancel = () => {
        router.post(route('leave.requests.cancel', request.id), {}, { preserveScroll: true });
    };

    const onApprove = (e) => {
        e.preventDefault();
        approveForm.post(route('leave.requests.approve', request.id), {
            preserveScroll: true,
            onSuccess: () => {
                setApproveOpen(false);
                approveForm.reset();
            },
        });
    };

    const onDeny = (e) => {
        e.preventDefault();
        denyForm.post(route('leave.requests.deny', request.id), {
            preserveScroll: true,
            onSuccess: () => {
                setDenyOpen(false);
                denyForm.reset();
            },
        });
    };

    return (
        <AuthenticatedLayout user={auth.user} header={`Leave Request #${request.id}`}>
            <Head title={`Leave Request #${request.id}`} />

            <div className="space-y-4">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        {flash.error}
                    </div>
                )}

                <div className="flex items-center justify-between gap-3">
                    <Link href={route('leave.requests.index')} className="text-sm font-semibold text-amber-800 hover:text-amber-900">
                        ← Back to requests
                    </Link>
                    <Badge tone={statusTone(request.status)}>{request.status}</Badge>
                </div>

                <Card className="p-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div className="min-w-0">
                            <h2 className="text-lg font-semibold text-slate-900 truncate">{employeeLabel}</h2>
                            <div className="mt-1 text-sm text-slate-600">
                                {leaveType?.name ?? 'Leave'} ({leaveType?.code ?? '—'})
                            </div>
                            <div className="mt-2 text-sm text-slate-700">
                                <div>
                                    <span className="font-semibold">Dates:</span> {request.start_date} → {request.end_date}
                                </div>
                                <div>
                                    <span className="font-semibold">Total:</span> {request.total_days} day(s)
                                    {request.is_half_day ? ` (Half-day ${request.half_day_part || ''})` : ''}
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-3 justify-end">
                            {actions?.cancel && request.status === 'PENDING' && (
                                <SecondaryButton type="button" onClick={onCancel}>
                                    Cancel
                                </SecondaryButton>
                            )}

                            {actions?.deny && request.status === 'PENDING' && (
                                <DangerButton type="button" onClick={() => setDenyOpen(true)}>
                                    Deny
                                </DangerButton>
                            )}
                            {actions?.approve && request.status === 'PENDING' && (
                                <PrimaryButton type="button" onClick={() => setApproveOpen(true)}>
                                    Approve
                                </PrimaryButton>
                            )}
                        </div>
                    </div>

                    {request.reason ? (
                        <div className="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Reason</div>
                            <div className="mt-2 whitespace-pre-wrap text-sm text-slate-800">{request.reason}</div>
                        </div>
                    ) : null}

                    {request.decision_notes ? (
                        <div className="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Decision notes</div>
                            <div className="mt-2 whitespace-pre-wrap text-sm text-slate-800">{request.decision_notes}</div>
                        </div>
                    ) : null}
                </Card>

                <Card className="p-6">
                    <h3 className="text-base font-semibold text-slate-900">Timeline</h3>
                    <div className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div className="rounded-xl border border-slate-200 p-4">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Requested</div>
                            <div className="mt-1 text-sm text-slate-800">{actors?.requested_by?.name ?? '—'}</div>
                            <div className="mt-1 text-xs text-slate-500">{request.created_at ?? '—'}</div>
                        </div>
                        <div className="rounded-xl border border-slate-200 p-4">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Approved</div>
                            <div className="mt-1 text-sm text-slate-800">{actors?.approved_by?.name ?? '—'}</div>
                            <div className="mt-1 text-xs text-slate-500">{request.approved_at ?? '—'}</div>
                        </div>
                        <div className="rounded-xl border border-slate-200 p-4">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Denied</div>
                            <div className="mt-1 text-sm text-slate-800">{actors?.denied_by?.name ?? '—'}</div>
                            <div className="mt-1 text-xs text-slate-500">{request.denied_at ?? '—'}</div>
                        </div>
                        <div className="rounded-xl border border-slate-200 p-4">
                            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">Last updated</div>
                            <div className="mt-1 text-sm text-slate-800">{request.updated_at ?? '—'}</div>
                        </div>
                    </div>
                </Card>
            </div>

            <Modal show={approveOpen} onClose={() => setApproveOpen(false)} maxWidth="md">
                <form onSubmit={onApprove} className="p-6 space-y-4">
                    <h2 className="text-lg font-semibold text-slate-900">Approve request</h2>
                    <div>
                        <InputLabel htmlFor="approve_notes" value="Notes (optional)" />
                        <textarea
                            id="approve_notes"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                            rows={4}
                            value={approveForm.data.decision_notes}
                            onChange={(e) => approveForm.setData('decision_notes', e.target.value)}
                        />
                        <InputError message={approveForm.errors.decision_notes} className="mt-2" />
                        <InputError message={approveForm.errors.start_date || approveForm.errors.status} className="mt-2" />
                    </div>
                    <div className="flex items-center justify-end gap-3">
                        <SecondaryButton type="button" onClick={() => setApproveOpen(false)} disabled={approveForm.processing}>
                            Close
                        </SecondaryButton>
                        <PrimaryButton disabled={approveForm.processing}>Approve</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={denyOpen} onClose={() => setDenyOpen(false)} maxWidth="md">
                <form onSubmit={onDeny} className="p-6 space-y-4">
                    <h2 className="text-lg font-semibold text-slate-900">Deny request</h2>
                    <div>
                        <InputLabel htmlFor="deny_notes" value="Notes (optional)" />
                        <textarea
                            id="deny_notes"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                            rows={4}
                            value={denyForm.data.decision_notes}
                            onChange={(e) => denyForm.setData('decision_notes', e.target.value)}
                        />
                        <InputError message={denyForm.errors.decision_notes} className="mt-2" />
                        <InputError message={denyForm.errors.status} className="mt-2" />
                    </div>
                    <div className="flex items-center justify-end gap-3">
                        <SecondaryButton type="button" onClick={() => setDenyOpen(false)} disabled={denyForm.processing}>
                            Close
                        </SecondaryButton>
                        <DangerButton disabled={denyForm.processing}>Deny</DangerButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
