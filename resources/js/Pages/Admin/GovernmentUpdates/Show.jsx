import React, { useMemo, useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Badge from '@/Components/UI/Badge';
import SecondaryButton from '@/Components/SecondaryButton';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';

function statusTone(status) {
    const s = String(status || '').toLowerCase();
    if (s === 'draft') return 'amber';
    if (s === 'approved') return 'success';
    if (s === 'rejected') return 'danger';
    return 'neutral';
}

function KeyValueTable({ rows }) {
    const keys = Object.keys(rows || {});

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full text-sm">
                <thead>
                    <tr className="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th className="px-3 py-2">Field</th>
                        <th className="px-3 py-2">Value</th>
                    </tr>
                </thead>
                <tbody>
                    {keys.map((k) => (
                        <tr key={k} className="border-t border-slate-200">
                            <td className="px-3 py-2 font-semibold text-slate-700">{k}</td>
                            <td className="px-3 py-2 text-slate-700">{rows[k] ?? '—'}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function SssTable({ rows }) {
    const safeRows = Array.isArray(rows) ? rows : [];

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full text-xs">
                <thead>
                    <tr className="text-left uppercase tracking-wide text-slate-500">
                        <th className="px-3 py-2">Range from</th>
                        <th className="px-3 py-2">Range to</th>
                        <th className="px-3 py-2">MSC</th>
                        <th className="px-3 py-2">Employee</th>
                        <th className="px-3 py-2">Employer</th>
                        <th className="px-3 py-2">EC</th>
                        <th className="px-3 py-2">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    {safeRows.length === 0 ? (
                        <tr>
                            <td className="px-3 py-3 text-slate-600" colSpan={7}>
                                No rows.
                            </td>
                        </tr>
                    ) : null}
                    {safeRows.map((r, idx) => (
                        <tr key={idx} className="border-t border-slate-200">
                            <td className="px-3 py-2 text-slate-700">{r.range_from ?? '—'}</td>
                            <td className="px-3 py-2 text-slate-700">{r.range_to ?? '—'}</td>
                            <td className="px-3 py-2 text-slate-700">{r.monthly_salary_credit ?? '—'}</td>
                            <td className="px-3 py-2 text-slate-700">{r.employee_share ?? '—'}</td>
                            <td className="px-3 py-2 text-slate-700">{r.employer_share ?? '—'}</td>
                            <td className="px-3 py-2 text-slate-700">{r.ec_share ?? '—'}</td>
                            <td className="px-3 py-2 text-slate-700">{r.notes ?? '—'}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export default function Show() {
    const { props } = usePage();
    const auth = props.auth ?? {};
    const flash = props.flash ?? {};

    const draft = props.draft;
    const active = props.active ?? {};

    const parseError = String(draft.parse_error || '').trim();

    const title = useMemo(() => `Government Update Draft #${draft.id}`, [draft.id]);

    const { data, setData, post, processing } = useForm({
        notes: '',
    });

    const [confirmReject, setConfirmReject] = useState(false);

    const canReview = String(draft.status) === 'draft';
    const canApprove = canReview && !parseError;

    const approve = () => {
        post(route('admin.government_updates.drafts.approve', draft.id), {
            preserveScroll: true,
        });
    };

    const reject = () => {
        post(route('admin.government_updates.drafts.reject', draft.id), {
            preserveScroll: true,
        });
    };

    const isSss = String(draft.source_type || '').toLowerCase() === 'sss';
    const isPhilhealth = String(draft.source_type || '').toLowerCase() === 'philhealth';
    const isPagibig = String(draft.source_type || '').toLowerCase() === 'pagibig';

    const draftPayload = draft.parsed_payload;

    const draftRow = !isSss
        ? Array.isArray(draftPayload)
            ? draftPayload[0]
            : draftPayload
        : null;

    return (
        <AuthenticatedLayout user={auth.user} header="Government Update Monitor" contentClassName="max-w-none">
            <Head title={title} />

            <div className="space-y-3">
                {!!flash?.success && (
                    <div className="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {!!flash?.error && (
                    <div className="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}
            </div>

            <div className="mt-6 flex items-start justify-between gap-4 flex-col md:flex-row">
                <div className="min-w-0">
                    <div className="flex items-center gap-3 flex-wrap">
                        <h1 className="text-xl font-semibold text-slate-900 truncate">Draft #{draft.id}</h1>
                        <Badge tone={statusTone(draft.status)}>{String(draft.status || '').toUpperCase()}</Badge>
                        <div className="text-sm text-slate-600 uppercase">{draft.source_type}</div>
                    </div>
                    <div className="mt-1 text-sm text-slate-600">Detected: {draft.detected_at || '—'}</div>
                    <div className="mt-1 text-xs text-slate-500 break-all">{draft.source_url}</div>
                </div>

                <div className="flex items-center gap-2 flex-wrap">
                    <SecondaryButton type="button" onClick={() => router.get(route('admin.government_updates.index'))}>
                        Back
                    </SecondaryButton>
                </div>
            </div>

            <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card className="p-6">
                    <div className="text-sm font-semibold text-slate-900">Current active settings</div>
                    <div className="mt-1 text-xs text-slate-600">Effective on: {active.effective_on || '—'}</div>

                    <div className="mt-4">
                        {isSss ? <SssTable rows={active.rows || []} /> : null}
                        {isPhilhealth || isPagibig ? (
                            active.row ? (
                                <KeyValueTable rows={active.row} />
                            ) : (
                                <div className="text-sm text-slate-600">No active settings found.</div>
                            )
                        ) : null}
                    </div>
                </Card>

                <Card className="p-6">
                    <div className="text-sm font-semibold text-slate-900">Parsed draft payload</div>
                    <div className="mt-1 text-xs text-slate-600">This is not active until approved.</div>

                    {parseError ? (
                        <div className="mt-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                            <div className="font-semibold">Parsing failed</div>
                            <div className="mt-1 text-xs whitespace-pre-wrap break-words">{parseError}</div>
                        </div>
                    ) : null}

                    <div className="mt-4">
                        {isSss ? <SssTable rows={Array.isArray(draftPayload) ? draftPayload : []} /> : null}
                        {isPhilhealth || isPagibig ? (
                            draftRow ? (
                                <KeyValueTable rows={draftRow} />
                            ) : (
                                <div className="text-sm text-slate-600">No payload.</div>
                            )
                        ) : null}
                    </div>
                </Card>
            </div>

            <div className="mt-6">
                <Card className="p-6">
                    <div className="text-sm font-semibold text-slate-900">Review</div>
                    <div className="mt-1 text-xs text-slate-600">Add notes (optional) then approve or reject.</div>

                    {parseError ? (
                        <div className="mt-4 text-sm text-red-700">
                            This draft can’t be approved until the source can be parsed.
                        </div>
                    ) : null}

                    <div className="mt-4">
                        <textarea
                            className="w-full rounded-md border-slate-300 bg-white/90 px-3 py-2 text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                            rows={4}
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            placeholder="Notes for audit trail (optional)"
                        />
                    </div>

                    <div className="mt-4 flex items-center gap-2 flex-wrap">
                        <PrimaryButton type="button" disabled={processing || !canApprove} onClick={approve}>
                            Approve draft
                        </PrimaryButton>
                        <DangerButton
                            type="button"
                            disabled={processing || !canReview}
                            onClick={() => setConfirmReject(true)}
                        >
                            Reject draft
                        </DangerButton>
                        {confirmReject ? (
                            <SecondaryButton type="button" onClick={() => setConfirmReject(false)}>
                                Cancel reject
                            </SecondaryButton>
                        ) : null}
                        {confirmReject ? (
                            <DangerButton type="button" disabled={processing} onClick={reject}>
                                Confirm reject
                            </DangerButton>
                        ) : null}
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
