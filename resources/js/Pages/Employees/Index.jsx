import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Index({ auth }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header="Employees"
        >
            <Head title="Employees" />

            <div className="max-w-7xl mx-auto">
                <div className="bg-white border border-gray-200 rounded-lg p-6">
                    <div className="text-gray-900 font-medium">Employees</div>
                    <div className="mt-1 text-sm text-gray-500">Placeholder page (Phase 1).</div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
