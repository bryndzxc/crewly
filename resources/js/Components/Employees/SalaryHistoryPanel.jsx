function formatCurrency(value) {
    const amount = Number(value ?? 0);

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(Number.isFinite(amount) ? amount : 0);
}

export default function SalaryHistoryPanel({ items = [] }) {
    return (
        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div className="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                <div className="text-sm font-semibold text-gray-900">Salary History</div>
                <div className="text-sm text-gray-600">{items.length} total</div>
            </div>

            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Previous Salary</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">New Salary</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Effective Date</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Reason</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Approved By</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {items.length === 0 && (
                            <tr>
                                <td className="px-4 py-6 text-sm text-gray-600" colSpan={5}>
                                    No salary changes recorded yet.
                                </td>
                            </tr>
                        )}

                        {items.map((item) => (
                            <tr key={item.id} className="hover:bg-amber-50/40">
                                <td className="px-4 py-3 text-sm text-gray-700">{formatCurrency(item.previous_salary)}</td>
                                <td className="px-4 py-3 text-sm font-medium text-gray-900">{formatCurrency(item.new_salary)}</td>
                                <td className="px-4 py-3 text-sm text-gray-700">{item.effective_date ?? '-'}</td>
                                <td className="px-4 py-3 text-sm text-gray-700">{item.reason ?? '-'}</td>
                                <td className="px-4 py-3 text-sm text-gray-700">{item.approved_by?.name ?? '-'}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}