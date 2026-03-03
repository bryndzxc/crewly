import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useRef } from 'react';

export default function FeedbackCreate({ auth, pageUrl = '', prefillMessage = '' }) {
    const flash = usePage().props.flash || {};
    const fileInputRef = useRef(null);

    const { data, setData, post, processing, errors } = useForm({
        message: String(prefillMessage || ''),
        page_url: String(pageUrl || window.location?.href || ''),
        attachments: [],
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('feedback.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setData('message', '');
                setData('attachments', []);
                if (fileInputRef.current) {
                    fileInputRef.current.value = null;
                }
            },
        });
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Concerns">
            <Head title="Concerns" />

            <div className="max-w-4xl mx-auto">
                <div className="bg-white border border-gray-200 rounded-lg p-6">
                    {!!flash.success && (
                        <div className="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                            {flash.success}
                        </div>
                    )}
                    <div className="text-sm text-slate-600">Share concerns, bugs, feature requests, or anything that would improve Crewly.</div>

                    <form onSubmit={submit} className="mt-5 space-y-5">
                        <div>
                            <InputLabel htmlFor="message" value="Your concern" />
                            <textarea
                                id="message"
                                name="message"
                                value={data.message}
                                onChange={(e) => setData('message', e.target.value)}
                                rows={7}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="What should we address or improve?"
                            />
                            <InputError message={errors.message} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="attachments" value="Images (optional)" />
                            <input
                                ref={fileInputRef}
                                id="attachments"
                                name="attachments"
                                type="file"
                                accept="image/*"
                                multiple
                                className="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm"
                                onChange={(e) => setData('attachments', Array.from(e.target.files ?? []))}
                            />
                            <InputError message={errors.attachments} className="mt-2" />
                            <InputError message={errors['attachments.0']} className="mt-2" />
                            <div className="mt-1 text-xs text-slate-500">Up to 5 images, 5MB each.</div>
                        </div>

                        <input type="hidden" name="page_url" value={data.page_url} />

                        <div className="flex items-center justify-end">
                            <PrimaryButton disabled={processing}>Send</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
