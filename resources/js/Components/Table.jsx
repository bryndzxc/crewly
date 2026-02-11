import Pagination from '@/Components/Pagination';
import { Fragment } from 'react';

export default function Table({
    columns,
    items,
    rowKey,
    renderRow,
    emptyState = null,
    pagination = null,
    loading = false,
    loadingText = 'Loading…',
}) {
    const alignClass = (align) => {
        if (align === 'right') return 'text-right';
        if (align === 'center') return 'text-center';
        return 'text-left';
    };

    return (
        <div className="relative bg-white/80 backdrop-blur border border-slate-200/70 rounded-2xl overflow-hidden shadow-lg shadow-slate-900/5">
            {loading && (
                <div className="absolute inset-0 z-10 flex items-center justify-center bg-white/55 backdrop-blur-[1px]">
                    <div className="flex items-center gap-3 rounded-2xl bg-white px-4 py-3 shadow-lg shadow-slate-900/10 ring-1 ring-slate-200">
                        <span
                            className="h-4 w-4 rounded-full border-2 border-amber-500/30 border-t-amber-600 animate-spin"
                            aria-hidden="true"
                        />
                        <span className="text-sm font-medium text-slate-700">{loadingText}</span>
                    </div>
                </div>
            )}
            <div className="w-full overflow-x-auto">
                <table className="w-full min-w-[720px] divide-y divide-slate-200">
                    <thead className="bg-slate-50">
                        <tr>
                            {columns.map((col) => (
                                <th
                                    key={col.key ?? col.label}
                                    className={(col.className ?? 'px-4 py-3') +
                                        ' ' +
                                        alignClass(col.align) +
                                        ' whitespace-nowrap text-xs font-semibold text-slate-600 uppercase tracking-wider'}
                                >
                                    {col.label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-200">
                        {items.map((item) => (
                            <Fragment key={rowKey(item)}>{renderRow(item)}</Fragment>
                        ))}

                        {items.length === 0 && emptyState && (
                            <tr>
                                <td className="px-4 py-6 text-sm text-slate-600" colSpan={columns.length}>
                                    {emptyState}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {pagination && (
                <div className="border-t border-slate-200 px-4 py-3">
                    <Pagination
                        meta={pagination.meta}
                        links={pagination.links}
                        perPage={pagination.perPage}
                        perPageOptions={pagination.perPageOptions}
                        onPerPageChange={pagination.onPerPageChange}
                    />
                </div>
            )}
        </div>
    );
}
