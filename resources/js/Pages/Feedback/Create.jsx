import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function FeedbackCreate({ auth, pageUrl = '' }) {
    const flash = usePage().props.flash || {};
    const { data, setData, post, processing, errors } = useForm({
        message: '',
        page_url: String(pageUrl || window.location?.href || ''),
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('feedback.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setData('message', '');
            },
        });
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Feedback">
            <Head title="Feedback" />

            <div className="max-w-4xl mx-auto">
                <div className="bg-white border border-gray-200 rounded-lg p-6">
                    {!!flash.success && (
                        <div className="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                            {flash.success}
                        </div>
                    )}
                    <div className="text-sm text-slate-600">Share bugs, feature requests, or anything that would improve Crewly.</div>

                    <form onSubmit={submit} className="mt-5 space-y-5">
                        <div>
                            <InputLabel htmlFor="message" value="Your feedback" />
                            <textarea
                                id="message"
                                name="message"
                                value={data.message}
                                onChange={(e) => setData('message', e.target.value)}
                                rows={7}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="What should we improve?"
                            />
                            <InputError message={errors.message} className="mt-2" />
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
