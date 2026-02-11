import { Link } from '@inertiajs/react';

const isNumericLabel = (label) => /^\d+$/.test(String(label).trim());

export default function Pagination({
    meta = null,
    links = [],
    perPage = null,
    perPageOptions = [10, 25, 50, 100],
    onPerPageChange,
}) {
    if (!meta) return null;
    if (!meta.last_page) return null;

    const firstUrl = links.find((l) => isNumericLabel(l.label) && String(l.label).trim() === '1')?.url ?? null;
    const lastUrl = [...links].reverse().find((l) => isNumericLabel(l.label))?.url ?? null;
    const prevUrl = links[0]?.url ?? null;
    const nextUrl = links[links.length - 1]?.url ?? null;

    const canChangePerPage = typeof onPerPageChange === 'function' && perPage !== null;

    const numericLinks = links.filter((l) => isNumericLabel(l.label));
    const pageLinks =
        numericLinks.length > 0
            ? links.filter((l) => isNumericLabel(l.label) || String(l.label).includes('...'))
            : [
                  {
                      url: null,
                      label: '1',
                      active: true,
                  },
              ];

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="text-sm text-gray-600">
                Showing {meta.from ?? 0} to {meta.to ?? 0} of {meta.total ?? 0} entries
            </div>

            <div className="flex items-center justify-end gap-3">
                {canChangePerPage && (
                    <select
                        value={perPage}
                        onChange={(e) => onPerPageChange(Number(e.target.value))}
                        className="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        aria-label="Rows per page"
                    >
                        {perPageOptions.map((opt) => (
                            <option key={opt} value={opt}>
                                {opt}
                            </option>
                        ))}
                    </select>
                )}

                <nav className="flex items-center gap-1" aria-label="Pagination">
                    <Link
                        href={firstUrl ?? '#'}
                        preserveScroll
                        className={
                            'px-2 py-2 text-sm rounded-md border ' +
                            (firstUrl
                                ? 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                : 'border-gray-100 bg-gray-50 text-gray-400 pointer-events-none')
                        }
                        aria-label="First page"
                    >
                        «
                    </Link>

                    <Link
                        href={prevUrl ?? '#'}
                        preserveScroll
                        className={
                            'px-2 py-2 text-sm rounded-md border ' +
                            (prevUrl
                                ? 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                : 'border-gray-100 bg-gray-50 text-gray-400 pointer-events-none')
                        }
                        aria-label="Previous page"
                    >
                        ‹
                    </Link>

                    {pageLinks.map((link, idx) => {
                            if (link.url === null) {
                                return (
                                    <span key={idx} className="px-2 py-2 text-sm text-gray-400">
                                        {String(link.label).includes('...') ? '…' : String(link.label)}
                                    </span>
                                );
                            }

                            return (
                                <Link
                                    key={idx}
                                    href={link.url}
                                    preserveScroll
                                    className={
                                        'px-3 py-2 text-sm rounded-md border ' +
                                        (link.active
                                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                            : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50')
                                    }
                                >
                                    {String(link.label).replace(/<[^>]+>/g, '')}
                                </Link>
                            );
                        })}

                    <Link
                        href={nextUrl ?? '#'}
                        preserveScroll
                        className={
                            'px-2 py-2 text-sm rounded-md border ' +
                            (nextUrl
                                ? 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                : 'border-gray-100 bg-gray-50 text-gray-400 pointer-events-none')
                        }
                        aria-label="Next page"
                    >
                        ›
                    </Link>

                    <Link
                        href={lastUrl ?? '#'}
                        preserveScroll
                        className={
                            'px-2 py-2 text-sm rounded-md border ' +
                            (lastUrl
                                ? 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                : 'border-gray-100 bg-gray-50 text-gray-400 pointer-events-none')
                        }
                        aria-label="Last page"
                    >
                        »
                    </Link>
                </nav>
            </div>
        </div>
    );
}
