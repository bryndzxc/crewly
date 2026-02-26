import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import RichTextEditor from '@/Components/RichTextEditor';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Create({ auth }) {
    const flash = usePage().props.flash;

    const form = useForm({
        name: '',
        slug: '',
        description: '',
        body_html: '',
        is_active: true,
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('settings.memo_templates.store'));
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Settings" contentClassName="max-w-4xl mx-auto">
            <Head title="Create Memo Template" />

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
                        <div className="text-lg font-semibold text-slate-900">Create memo template</div>
                        <div className="text-sm text-slate-600">Admin/HR templates for generated memos.</div>
                    </div>
                    <Link href={route('settings.memo_templates.index')}>
                        <SecondaryButton>Back</SecondaryButton>
                    </Link>
                </div>

                <form onSubmit={submit} className="bg-white border border-slate-200 rounded-lg p-5 space-y-4">
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

                    <div>
                        <label className="block text-xs font-semibold uppercase tracking-wider text-slate-600">Body HTML</label>
                        <div className="mt-1">
                            <RichTextEditor
                                value={form.data.body_html}
                                onChange={(html) => form.setData('body_html', html)}
                                placeholder="Write your memo template here…"
                            />
                        </div>
                        {form.errors.body_html && <div className="mt-1 text-sm text-red-600">{form.errors.body_html}</div>}
                        <div className="mt-2 rounded-md border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                            <div className="font-semibold text-slate-900">Placeholders</div>
                            <div className="mt-1">
                                {'{{company_name}}, {{employee_name}}, {{employee_id}}, {{employee_position}}, {{incident_date}}, {{incident_category}}, {{incident_description}}, {{memo_date}}, {{hr_signatory_name}}'}
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center justify-between">
                        <label className="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                className="rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                                checked={!!form.data.is_active}
                                onChange={(e) => form.setData('is_active', e.target.checked)}
                            />
                            Active
                        </label>

                        <PrimaryButton type="submit" disabled={form.processing}>
                            {form.processing ? 'Saving…' : 'Save'}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
