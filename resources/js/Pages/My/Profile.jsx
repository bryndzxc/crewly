import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PageHeader from '@/Components/UI/PageHeader';
import { Head } from '@inertiajs/react';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm';

function Field({ label, value }) {
    return (
        <div>
            <div className="text-xs font-semibold text-slate-600 uppercase tracking-wider">{label}</div>
            <div className="mt-1 text-sm font-medium text-slate-900">{value || '—'}</div>
        </div>
    );
}

export default function Profile({ auth, employee }) {
    return (
        <AuthenticatedLayout user={auth.user} header="My Profile">
            <Head title="My Profile" />

            <PageHeader title="My Profile" subtitle="Your details are read-only (except password)." />

            <div className="w-full space-y-4">
                <Card className="p-6">
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <Field label="Employee Code" value={employee?.employee_code} />
                        <Field label="Name" value={employee?.name} />
                        <Field label="Email" value={employee?.email} />
                        <Field label="Department" value={employee?.department?.name} />
                        <Field label="Position Title" value={employee?.position_title} />
                        <Field label="Employment Type" value={employee?.employment_type} />
                        <Field label="Status" value={employee?.status} />
                        <Field label="Date Hired" value={employee?.date_hired} />
                        <Field label="Regularization Date" value={employee?.regularization_date} />
                    </div>

                    <div className="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        Need to update something? Contact HR to request changes.
                    </div>
                </Card>

                <Card className="p-6">
                    <UpdatePasswordForm />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
