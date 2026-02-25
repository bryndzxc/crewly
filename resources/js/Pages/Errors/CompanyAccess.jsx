import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';

export default function CompanyAccess({ message }) {
    const { props } = usePage();
    const user = props?.auth?.user;

    return (
        <AuthenticatedLayout user={user} header="Access blocked">
            <Head title="Access blocked" />
            <div className="max-w-3xl mx-auto p-6">
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="text-sm font-semibold text-slate-500 uppercase tracking-wider">Company required</div>
                    <div className="mt-2 text-lg font-semibold text-slate-900">Your account needs an active company</div>
                    <div className="mt-2 text-slate-700">{message}</div>
                    <div className="mt-4 text-sm text-slate-600">You can still log out and sign in with a different account.</div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
