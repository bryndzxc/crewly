import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Modal from '@/Components/Modal';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Index({ auth, templates = [], modal = null }) {
    const flash = usePage().props.flash;
    const items = Array.isArray(templates) ? templates : [];

    const mode = modal?.mode || null;
    const editingTemplate = modal?.template || null;
    const showModal = mode === 'create' || mode === 'edit';

    const form = useForm({
        name: '',
        slug: '',
        description: '',
        body_html: '',
        is_active: true,
    });

    useEffect(() => {
        if (!showModal) return;

        form.setData({
            name: editingTemplate?.name ?? '',
            slug: editingTemplate?.slug ?? '',
            description: editingTemplate?.description ?? '',
            body_html: editingTemplate?.body_html ?? '',
            is_active: mode === 'edit' ? !!editingTemplate?.is_active : true,
        });
        form.clearErrors();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [mode, editingTemplate?.id]);

    const toggle = (id) => {
        router.patch(route('settings.memo_templates.toggle', id), {}, { preserveScroll: true });
    };

    const closeModal = () => {
        router.visit(route('settings.memo_templates.index'), { preserveScroll: true });
    };

    const submit = (e) => {
        e.preventDefault();

        if (mode === 'create') {
            form.post(route('settings.memo_templates.store'), { preserveScroll: true });
            return;
        }

        if (mode === 'edit' && editingTemplate?.id) {
            form.put(route('settings.memo_templates.update', editingTemplate.id), { preserveScroll: true });
        }
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Settings" contentClassName="max-w-none">
            <Head title="Memo Templates" />

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

                <div className="flex items-center justify-between">
                    <div>
                        <div className="text-lg font-semibold text-slate-900">Memo Templates</div>
                        <div className="text-sm text-slate-600">Manage built-in templates used for generated PDFs.</div>
                    </div>

                    <Link href={route('settings.memo_templates.create')}>
                        <PrimaryButton>Create template</PrimaryButton>
                    </Link>
                </div>

                <div className="bg-white border border-slate-200 rounded-lg overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Slug</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 bg-white">
                                {items.length === 0 && (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-10 text-center text-sm text-slate-600">
                                            No templates yet.
                                        </td>
                                    </tr>
                                )}

                                {items.map((t) => (
                                    <tr key={t.id} className="hover:bg-amber-50/30">
                                        <td className="px-4 py-3 text-sm text-slate-900">
                                            <div className="flex items-center gap-2">
                                                <div className="font-medium">{t.name}</div>
                                                {t.is_system ? (
                                                    <span className="inline-flex items-center rounded-full bg-slate-900 px-2 py-0.5 text-[10px] font-semibold text-white">
                                                        System
                                                    </span>
                                                ) : null}
                                            </div>
                                            {!!t.description && <div className="mt-0.5 text-xs text-slate-600">{t.description}</div>}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-slate-700">{t.slug}</td>
                                        <td className="px-4 py-3">
                                            {t.is_active ? (
                                                <span className="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-800 ring-1 ring-green-200">
                                                    Active
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-800 ring-1 ring-slate-200">
                                                    Inactive
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right whitespace-nowrap">
                                            <Link
                                                href={route('settings.memo_templates.edit', t.id)}
                                                className="text-amber-700 hover:text-amber-900 text-sm font-medium"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                className="ml-4 text-sm font-medium text-slate-700 hover:text-slate-900"
                                                onClick={() => toggle(t.id)}
                                            >
                                                {t.is_active ? 'Deactivate' : 'Activate'}
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    <div className="font-semibold text-slate-900">Supported placeholders</div>
                    <div className="mt-1 text-slate-600">
                        {'{{company_name}}, {{employee_name}}, {{employee_id}}, {{employee_position}}, {{incident_date}}, {{incident_category}}, {{incident_description}}, {{memo_date}}, {{hr_signatory_name}}'}
                    </div>
                </div>
            </div>

            <Modal show={showModal} maxWidth="6xl" onClose={closeModal}>
                <div className="px-6 py-4 border-b border-slate-200 flex items-start justify-between gap-4">
                    <div>
                        <div className="text-lg font-semibold text-slate-900">
                            {mode === 'edit' ? 'Edit memo template' : 'Create memo template'}
                        </div>
                        <div className="text-sm text-slate-600">
                            {mode === 'edit' ? 'Update template HTML and settings.' : 'Admin/HR templates for generated memos.'}
                        </div>
                    </div>
                    <SecondaryButton type="button" onClick={closeModal}>
                        Close
                    </SecondaryButton>
                </div>

                <form onSubmit={submit} className="p-6 space-y-4">
                    <div>
                        <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Name</label>
                        <input
                            className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="e.g. Notice to Explain (NTE)"
                        />
                        {form.errors.name && <div className="mt-1 text-sm text-red-600">{form.errors.name}</div>}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Slug (optional)</label>
                            <input
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.slug}
                                onChange={(e) => form.setData('slug', e.target.value)}
                                placeholder="notice-to-explain"
                            />
                            {form.errors.slug && <div className="mt-1 text-sm text-red-600">{form.errors.slug}</div>}
                            <div className="mt-1 text-xs text-slate-500">Leave empty to auto-generate from name.</div>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Description (optional)</label>
                            <input
                                className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500"
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                placeholder="Short description for admins"
                            />
                            {form.errors.description && <div className="mt-1 text-sm text-red-600">{form.errors.description}</div>}
                        </div>
                    </div>

                    <div>
                        <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Body HTML</label>
                        <textarea
                            rows={18}
                            className="mt-1 w-full rounded-md border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono text-xs"
                            value={form.data.body_html}
                            onChange={(e) => form.setData('body_html', e.target.value)}
                            placeholder="<p><strong>{{company_name}}</strong></p>\n<p>Date: {{memo_date}}</p>\n..."
                        />
                        {form.errors.body_html && <div className="mt-1 text-sm text-red-600">{form.errors.body_html}</div>}
                        <div className="mt-2 rounded-md border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                            <div className="font-semibold text-slate-900">Placeholders</div>
                            <div className="mt-1">
                                {'{{company_name}}, {{employee_name}}, {{employee_id}}, {{employee_position}}, {{incident_date}}, {{incident_category}}, {{incident_description}}, {{memo_date}}, {{hr_signatory_name}}'}
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center justify-between pt-2">
                        <label className="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                className="rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                                checked={!!form.data.is_active}
                                onChange={(e) => form.setData('is_active', e.target.checked)}
                            />
                            Active
                        </label>

                        <div className="flex items-center gap-2">
                            <SecondaryButton type="button" onClick={closeModal}>
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton type="submit" disabled={form.processing}>
                                {form.processing ? 'Saving…' : mode === 'edit' ? 'Save changes' : 'Save'}
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
