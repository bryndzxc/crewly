import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Table from '@/Components/Table';
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

    const meta =
        feedback?.meta ??
        (feedback
            ? {
                  from: feedback.from ?? 0,
                  to: feedback.to ?? 0,
                  total: feedback.total ?? 0,
                  current_page: feedback.current_page ?? 1,
                  last_page: feedback.last_page ?? 1,
              }
            : null);

    const links = Array.isArray(feedback?.links)
        ? feedback.links
        : Array.isArray(feedback?.meta?.links)
          ? feedback.meta.links
          : [];

    return (
        <AuthenticatedLayout user={auth.user} header="Feedback Inbox" contentClassName="max-w-none">
            <Head title="Feedback Inbox" />

            <div className="w-full space-y-4">
                <Table
                    columns={[
                        { key: 'company', label: 'Company' },
                        { key: 'user', label: 'User' },
                        { key: 'message', label: 'Message' },
                        { key: 'created', label: 'Created' },
                    ]}
                    items={data}
                    rowKey={(f) => f.id}
                    emptyState="No feedback yet."
                    pagination={meta ? { meta, links, perPage: feedback?.per_page ?? meta?.per_page } : null}
                    renderRow={(f) => (
                        <tr className="hover:bg-amber-50/40">
                            <td className="px-4 py-3 text-sm text-slate-700">{f.company?.name || '-'}</td>
                            <td className="px-4 py-3 text-sm text-slate-700">{f.user?.name || '-'}</td>
                            <td className="px-4 py-3 text-sm text-slate-900 whitespace-pre-wrap">{String(f.message || '')}</td>
                            <td className="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">
                                {formatDate(f.created_at)}
                            </td>
                        </tr>
                    )}
                />
            </div>
        </AuthenticatedLayout>
    );
}
