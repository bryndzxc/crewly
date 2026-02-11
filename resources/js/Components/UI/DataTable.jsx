import React from 'react';
import Card from '@/Components/UI/Card';

function alignClass(align) {
    if (align === 'right') return 'text-right';
    if (align === 'center') return 'text-center';
    return 'text-left';
}

export default function DataTable({ columns = [], rows = [], rowKey, emptyState = 'No data.', className = '' }) {
    return (
        <Card className={'overflow-hidden ' + className}>
            <div className="w-full overflow-x-auto">
                <table className="w-full min-w-[720px] divide-y divide-slate-200">
                    <thead className="bg-slate-50">
                        <tr>
                            {columns.map((col) => (
                                <th
                                    key={col.key}
                                    className={
                                        (col.className ?? 'px-4 py-3') +
                                        ' ' +
                                        alignClass(col.align) +
                                        ' whitespace-nowrap text-xs font-semibold uppercase tracking-wider text-slate-600'
                                    }
                                >
                                    {col.header}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-200 bg-white">
                        {rows.map((row) => (
                            <tr key={typeof rowKey === 'function' ? rowKey(row) : row.id} className="hover:bg-slate-50/60">
                                {columns.map((col) => (
                                    <td
                                        key={col.key}
                                        className={
                                            (col.cellClassName ?? 'px-4 py-3') +
                                            ' ' +
                                            alignClass(col.align) +
                                            ' text-sm text-slate-700'
                                        }
                                    >
                                        {typeof col.cell === 'function' ? col.cell(row) : row[col.key]}
                                    </td>
                                ))}
                            </tr>
                        ))}

                        {rows.length === 0 && (
                            <tr>
                                <td className="px-4 py-8 text-sm text-slate-600" colSpan={columns.length}>
                                    {emptyState}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </Card>
    );
}
