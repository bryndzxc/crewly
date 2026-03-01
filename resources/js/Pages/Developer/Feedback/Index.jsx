import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import { Head } from '@inertiajs/react';

function formatDate(iso) {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return String(iso);
    }
}

export default function DeveloperFeedbackIndex({ auth, feedback }) {
    const data = feedback?.data || [];

    return (
        <AuthenticatedLayout user={auth.user} header="Feedback Inbox">
            <Head title="Feedback Inbox" />

            <div className="max-w-6xl mx-auto">
                <Card>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Company</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">User</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Message</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Created</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 bg-white">
                                {data.length === 0 ? (
                                    <tr>
                                        <td className="px-4 py-6 text-sm text-slate-600" colSpan={4}>
                                            No feedback yet.
                                        </td>
                                    </tr>
                                ) : (
                                    data.map((f) => (
                                        <tr key={f.id}>
                                            <td className="px-4 py-3 text-sm text-slate-700">{f.company?.name || '-'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{f.user?.name || '-'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-900 whitespace-pre-wrap max-w-[52rem]">{String(f.message || '')}</td>
                                            <td className="px-4 py-3 text-sm text-slate-600">{formatDate(f.created_at)}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
