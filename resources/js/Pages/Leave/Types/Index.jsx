import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Table from '@/Components/Table';
import Badge from '@/Components/UI/Badge';
import Modal from '@/Components/Modal';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import DangerButton from '@/Components/DangerButton';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function Index({ auth, types, filters = {} }) {
    const [perPage, setPerPage] = useState(filters.per_page ?? 10);
    const [isLoading, setIsLoading] = useState(false);
    const [createOpen, setCreateOpen] = useState(false);
    const flash = usePage().props.flash;

    const typeItems = types?.data ?? [];

    useEffect(() => {
        const parsePathname = (url) => {
            try {
                return new URL(url, window.location.origin).pathname;
            } catch {
                return String(url || '');
            }
        };

        const unsubscribeStart = router.on('start', (event) => {
            const visit = event?.detail?.visit;
            const pathname = parsePathname(visit?.url);
            if (pathname.startsWith('/leave/types')) setIsLoading(true);
        });

        const unsubscribeFinish = router.on('finish', (event) => {
            const visit = event?.detail?.visit;
            const pathname = parsePathname(visit?.url);
            if (pathname.startsWith('/leave/types')) setIsLoading(false);
        });

        return () => {
            if (typeof unsubscribeStart === 'function') unsubscribeStart();
            if (typeof unsubscribeFinish === 'function') unsubscribeFinish();
        };
    }, []);

    const onPerPageChange = (nextPerPage) => {
        setPerPage(nextPerPage);
        router.get(route('leave.types.index'), { per_page: nextPerPage, page: 1 }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const emptyState = useMemo(() => {
        if (typeItems.length === 0) return 'No leave types yet.';
        return null;
    }, [typeItems.length]);

    const createForm = useForm({
        name: '',
        code: '',
        requires_approval: true,
        paid: true,
        allow_half_day: true,
        default_annual_credits: '',
        is_active: true,
    });

    const submitCreate = (e) => {
        e.preventDefault();
        createForm.post(route('leave.types.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setCreateOpen(false);
                createForm.reset();
            },
        });
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Leave Types" contentClassName="max-w-none">
            <Head title="Leave Types" />

            <div className="w-full space-y-4">
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
                    <div className="text-sm text-slate-600">HR-managed leave categories.</div>
                    <PrimaryButton type="button" onClick={() => setCreateOpen(true)} className="shrink-0">
                        Create Leave Type
                    </PrimaryButton>
                </div>

                <Table
                    loading={isLoading}
                    loadingText="Loading leave types…"
                    columns={[
                        { key: 'name', label: 'Name' },
                        { key: 'code', label: 'Code' },
                        { key: 'paid', label: 'Paid' },
                        { key: 'half', label: 'Half-day' },
                        { key: 'credits', label: 'Annual Credits' },
                        { key: 'active', label: 'Active' },
                        { key: 'action', label: 'Action', align: 'right' },
                    ]}
                    items={typeItems}
                    rowKey={(t) => t.id}
                    emptyState={emptyState}
                    pagination={{
                        meta: types?.meta ?? types,
                        links: types?.links ?? types?.meta?.links ?? [],
                        perPage,
                        onPerPageChange,
                    }}
                    renderRow={(t) => (
                        <tr className="hover:bg-amber-50/30">
                            <td className="px-4 py-3 text-sm font-medium text-slate-900">{t.name}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">{t.code}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">
                                <Badge tone={t.paid ? 'success' : 'neutral'}>{t.paid ? 'PAID' : 'UNPAID'}</Badge>
                            </td>
                            <td className="px-4 py-3 text-sm text-slate-700">
                                <Badge tone={t.allow_half_day ? 'amber' : 'neutral'}>{t.allow_half_day ? 'ALLOWED' : 'NO'}</Badge>
                            </td>
                            <td className="px-4 py-3 text-sm text-slate-700">{t.default_annual_credits ?? '—'}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">
                                <Badge tone={t.is_active ? 'success' : 'neutral'}>{t.is_active ? 'ACTIVE' : 'INACTIVE'}</Badge>
                            </td>
                            <td className="px-4 py-3 text-right text-sm">
                                <Link href={route('leave.types.edit', t.id)} className="shrink-0">
                                    <SecondaryButton type="button">Edit</SecondaryButton>
                                </Link>
                            </td>
                        </tr>
                    )}
                />
            </div>

            <Modal show={createOpen} onClose={() => setCreateOpen(false)} maxWidth="2xl">
                <form onSubmit={submitCreate} className="p-6 space-y-5">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900">Create leave type</h2>
                            <p className="mt-1 text-sm text-slate-600">Set rules and defaults for a leave category.</p>
                        </div>
                        <DangerButton type="button" onClick={() => setCreateOpen(false)}>
                            Close
                        </DangerButton>
                    </div>

                    <div>
                        <InputLabel htmlFor="name" value="Name" />
                        <TextInput
                            id="name"
                            value={createForm.data.name}
                            className="mt-1 block w-full"
                            isFocused={true}
                            onChange={(e) => createForm.setData('name', e.target.value)}
                        />
                        <InputError message={createForm.errors.name} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="code" value="Code" />
                        <TextInput
                            id="code"
                            value={createForm.data.code}
                            className="mt-1 block w-full"
                            placeholder="e.g. VL"
                            onChange={(e) => createForm.setData('code', e.target.value)}
                        />
                        <div className="mt-2 text-xs text-gray-500">Uppercase letters, numbers, underscore, and dash only.</div>
                        <InputError message={createForm.errors.code} className="mt-2" />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label className="flex items-center gap-2 text-sm text-slate-700">
                            <Checkbox
                                checked={Boolean(createForm.data.requires_approval)}
                                onChange={(e) => createForm.setData('requires_approval', e.target.checked)}
                            />
                            Requires approval
                        </label>
                        <label className="flex items-center gap-2 text-sm text-slate-700">
                            <Checkbox checked={Boolean(createForm.data.paid)} onChange={(e) => createForm.setData('paid', e.target.checked)} />
                            Paid
                        </label>
                        <label className="flex items-center gap-2 text-sm text-slate-700">
                            <Checkbox
                                checked={Boolean(createForm.data.allow_half_day)}
                                onChange={(e) => createForm.setData('allow_half_day', e.target.checked)}
                            />
                            Allow half-day
                        </label>
                        <label className="flex items-center gap-2 text-sm text-slate-700">
                            <Checkbox
                                checked={Boolean(createForm.data.is_active)}
                                onChange={(e) => createForm.setData('is_active', e.target.checked)}
                            />
                            Active
                        </label>
                    </div>

                    <div>
                        <InputLabel htmlFor="default_annual_credits" value="Default annual credits (optional)" />
                        <TextInput
                            id="default_annual_credits"
                            value={createForm.data.default_annual_credits}
                            className="mt-1 block w-full"
                            placeholder="e.g. 15"
                            onChange={(e) => createForm.setData('default_annual_credits', e.target.value)}
                        />
                        <InputError message={createForm.errors.default_annual_credits} className="mt-2" />
                    </div>

                    <div className="flex items-center justify-end gap-3">
                        <SecondaryButton type="button" onClick={() => setCreateOpen(false)} disabled={createForm.processing}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton disabled={createForm.processing}>Create</PrimaryButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
