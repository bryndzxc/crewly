import React, { useMemo, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import Card from '@/Components/UI/Card';
import Badge from '@/Components/UI/Badge';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';

function statusTone(status) {
    const s = String(status || '').toLowerCase();
    if (s === 'active') return 'success';
    if (s === 'trial') return 'neutral';
    if (s === 'past_due') return 'amber';
    if (s === 'suspended') return 'danger';
    return 'neutral';
}

export default function Show() {
    const { props } = usePage();
    const auth = props.auth ?? {};
    const flash = props.flash ?? {};
    const company = props.company;
    const plans = Array.isArray(props.plans) ? props.plans : [];
    const statuses = Array.isArray(props.statuses) ? props.statuses : [];
    const users = Array.isArray(props.users) ? props.users : [];

    const [editOpen, setEditOpen] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [confirmAction, setConfirmAction] = useState('');
    const [confirmMessage, setConfirmMessage] = useState('');

    const { data, setData, patch, processing, errors } = useForm({
        plan_name: company.plan_name || 'starter',
        max_employees: company.max_employees ?? 20,
        subscription_status: company.subscription_status || 'trial',
        trial_ends_at: company.trial_ends_at || '',
        next_billing_at: company.next_billing_at || '',
        last_payment_at: company.last_payment_at || '',
        grace_days: company.grace_days ?? 7,
        billing_notes: company.billing_notes || '',
    });

    const title = useMemo(() => `Billing / ${company.name}`, [company.name]);

    const submitUpdate = (e) => {
        e.preventDefault();
        patch(route('admin.billing.companies.update', company.id), {
            preserveScroll: true,
            onSuccess: () => setEditOpen(false),
        });
    };

    const openConfirm = (actionKey, message) => {
        setConfirmAction(String(actionKey || ''));
        setConfirmMessage(String(message || ''));
        setConfirmOpen(true);
    };

    const closeConfirm = () => {
        setConfirmOpen(false);
        setConfirmAction('');
        setConfirmMessage('');
    };

    const runConfirmAction = () => {
        const key = String(confirmAction || '');

        const onFinish = () => closeConfirm();

        if (key === 'activate') {
            router.post(route('admin.billing.companies.activate', company.id), {}, { preserveScroll: true, onFinish });
            return;
        }
        if (key === 'grant_trial_30') {
            router.post(route('admin.billing.companies.grant_trial_30', company.id), {}, { preserveScroll: true, onFinish });
            return;
        }
        if (key === 'mark_paid') {
            router.post(route('admin.billing.companies.mark_paid', company.id), {}, { preserveScroll: true, onFinish });
            return;
        }
        if (key === 'set_past_due') {
            router.post(route('admin.billing.companies.set_past_due', company.id), {}, { preserveScroll: true, onFinish });
            return;
        }
        if (key === 'suspend') {
            router.post(route('admin.billing.companies.suspend', company.id), {}, { preserveScroll: true, onFinish });
            return;
        }
        if (key === 'send_invoice_email') {
            router.post(route('admin.billing.companies.send_invoice_email', company.id), {}, { preserveScroll: true, onFinish });
            return;
        }

        closeConfirm();
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Admin Billing" contentClassName="max-w-none">
            <Head title={title} />

            <div className="space-y-3">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}
            </div>

            <div className="flex items-start justify-between gap-4 flex-col md:flex-row">
                <div className="min-w-0">
                    <div className="flex items-center gap-3 flex-wrap">
                        <h1 className="text-xl font-semibold text-slate-900 truncate">{company.name}</h1>
                        <Badge tone={statusTone(company.subscription_status)}>
                            {String(company.subscription_status || '').replace('_', ' ') || '—'}
                        </Badge>
                    </div>
                    <div className="mt-1 text-sm text-slate-600">Slug: {company.slug || '—'}</div>
                </div>
                <div className="flex items-center gap-2 flex-wrap">
                    <SecondaryButton type="button" onClick={() => router.get(route('admin.billing.companies.index'))}>
                        Back
                    </SecondaryButton>
                </div>
            </div>

            <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <Card className="p-6 lg:col-span-2">
                    <div className="text-sm font-semibold text-slate-900">Billing actions</div>
                    <div className="mt-4 flex flex-wrap gap-2">
                        <PrimaryButton
                            type="button"
                            onClick={() =>
                                openConfirm(
                                    'activate',
                                    'Activate subscription now? This sets status active, last_payment_at=now, next_billing_at=now+30 days.'
                                )
                            }
                        >
                            Activate
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={() =>
                                openConfirm(
                                    'grant_trial_30',
                                    'Grant a free 30-day trial? This sets status=trial and trial_ends_at=now+30 days.'
                                )
                            }
                        >
                            Grant 30-day trial
                        </SecondaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={() =>
                                openConfirm(
                                    'mark_paid',
                                    'Mark as paid now? This sets last_payment_at=now and next_billing_at=paid+30 days.'
                                )
                            }
                        >
                            Mark paid
                        </SecondaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={() => openConfirm('set_past_due', 'Set to past due? Users can still access but will see warnings.')}
                        >
                            Set past due
                        </SecondaryButton>
                        <DangerButton
                            type="button"
                            onClick={() =>
                                openConfirm(
                                    'suspend',
                                    'Suspend subscription? Non-super-admin users will be redirected to Billing Required.'
                                )
                            }
                        >
                            Suspend
                        </DangerButton>
                        <SecondaryButton
                            type="button"
                            onClick={() => openConfirm('send_invoice_email', 'Send an invoice summary email to company admin/HR users?')}
                        >
                            Send invoice email
                        </SecondaryButton>
                        <SecondaryButton type="button" onClick={() => setEditOpen(true)}>
                            Edit billing fields
                        </SecondaryButton>
                    </div>

                    <div className="mt-6 rounded-xl border border-slate-200 bg-white p-4">
                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Current</div>
                        <div className="mt-2 grid gap-3 sm:grid-cols-2">
                            <div className="text-sm text-slate-700">
                                <span className="font-semibold text-slate-900">Plan:</span> {(company.plan_name || 'starter').toUpperCase()}
                            </div>
                            <div className="text-sm text-slate-700">
                                <span className="font-semibold text-slate-900">Max employees:</span> {company.max_employees ?? 0}
                            </div>
                            <div className="text-sm text-slate-700">
                                <span className="font-semibold text-slate-900">Next billing:</span> {company.next_billing_at || '—'}
                            </div>
                            <div className="text-sm text-slate-700">
                                <span className="font-semibold text-slate-900">Last payment:</span> {company.last_payment_at || '—'}
                            </div>
                            <div className="text-sm text-slate-700">
                                <span className="font-semibold text-slate-900">Grace days:</span> {company.grace_days ?? 7}
                            </div>
                        </div>
                    </div>
                </Card>

                <Card className="p-6">
                    <div className="text-sm font-semibold text-slate-900">Tenant users</div>
                    <div className="mt-3 space-y-3">
                        {users.length === 0 ? <div className="text-sm text-slate-600">No users.</div> : null}
                        {users.map((u) => (
                            <div key={u.id} className="rounded-xl border border-slate-200 bg-white px-3 py-2">
                                <div className="text-sm font-semibold text-slate-900">{u.name}</div>
                                <div className="text-xs text-slate-600">{u.email}</div>
                                <div className="mt-1 text-xs text-slate-500">Role: {u.role}</div>
                            </div>
                        ))}
                    </div>
                </Card>
            </div>

            <Modal show={editOpen} onClose={() => setEditOpen(false)} maxWidth="4xl">
                <div className="p-6">
                    <div className="flex items-center justify-between gap-3">
                        <div className="text-sm font-semibold text-slate-900">Edit billing fields</div>
                        <SecondaryButton type="button" onClick={() => setEditOpen(false)}>
                            Close
                        </SecondaryButton>
                    </div>

                    <form onSubmit={submitUpdate} className="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Plan" />
                            <select
                                className="mt-1 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-amber-400 focus:ring-amber-400"
                                value={data.plan_name}
                                onChange={(e) => setData('plan_name', e.target.value)}
                            >
                                {plans.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.label}
                                    </option>
                                ))}
                            </select>
                            <InputError className="mt-1" message={errors.plan_name} />
                        </div>

                        <div>
                            <InputLabel value="Max employees" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={data.max_employees}
                                onChange={(e) => setData('max_employees', e.target.value)}
                            />
                            <InputError className="mt-1" message={errors.max_employees} />
                        </div>

                        <div>
                            <InputLabel value="Subscription status" />
                            <select
                                className="mt-1 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-amber-400 focus:ring-amber-400"
                                value={data.subscription_status}
                                onChange={(e) => setData('subscription_status', e.target.value)}
                            >
                                {statuses.map((s) => (
                                    <option key={s} value={s}>
                                        {String(s).replace('_', ' ')}
                                    </option>
                                ))}
                            </select>
                            <InputError className="mt-1" message={errors.subscription_status} />
                        </div>

                        <div>
                            <InputLabel value="Grace days" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={data.grace_days}
                                onChange={(e) => setData('grace_days', e.target.value)}
                            />
                            <InputError className="mt-1" message={errors.grace_days} />
                        </div>

                        <div>
                            <InputLabel value="Trial ends at (optional)" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={data.trial_ends_at}
                                onChange={(e) => setData('trial_ends_at', e.target.value)}
                                placeholder="YYYY-MM-DD HH:MM:SS"
                            />
                            <InputError className="mt-1" message={errors.trial_ends_at} />
                        </div>

                        <div>
                            <InputLabel value="Next billing at (optional)" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={data.next_billing_at}
                                onChange={(e) => setData('next_billing_at', e.target.value)}
                                placeholder="YYYY-MM-DD HH:MM:SS"
                            />
                            <InputError className="mt-1" message={errors.next_billing_at} />
                        </div>

                        <div>
                            <InputLabel value="Last payment at (optional)" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={data.last_payment_at}
                                onChange={(e) => setData('last_payment_at', e.target.value)}
                                placeholder="YYYY-MM-DD HH:MM:SS"
                            />
                            <InputError className="mt-1" message={errors.last_payment_at} />
                        </div>

                        <div className="sm:col-span-2">
                            <InputLabel value="Billing notes" />
                            <textarea
                                className="mt-1 block w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-amber-400 focus:ring-amber-400"
                                rows={4}
                                value={data.billing_notes}
                                onChange={(e) => setData('billing_notes', e.target.value)}
                            />
                            <InputError className="mt-1" message={errors.billing_notes} />
                        </div>

                        <div className="sm:col-span-2 flex items-center justify-end gap-2">
                            <SecondaryButton type="button" onClick={() => setEditOpen(false)} disabled={processing}>
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton disabled={processing} type="submit">
                                Save changes
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <Modal show={confirmOpen} onClose={closeConfirm} maxWidth="lg">
                <div className="p-6">
                    <div className="text-sm font-semibold text-slate-900">Confirm action</div>
                    <div className="mt-2 text-sm text-slate-600 whitespace-pre-wrap">{confirmMessage || 'Are you sure?'}</div>

                    <div className="mt-5 flex items-center justify-end gap-2">
                        <SecondaryButton type="button" onClick={closeConfirm}>
                            Cancel
                        </SecondaryButton>
                        {String(confirmAction) === 'suspend' ? (
                            <DangerButton type="button" onClick={runConfirmAction}>
                                Confirm
                            </DangerButton>
                        ) : (
                            <PrimaryButton type="button" onClick={runConfirmAction}>
                                Confirm
                            </PrimaryButton>
                        )}
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
