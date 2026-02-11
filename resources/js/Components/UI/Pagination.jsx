import React from 'react';

export default function Pagination({ page, pages = 5, onPageChange }) {
    const safePages = Math.max(1, Number(pages || 1));
    const current = Math.min(Math.max(1, Number(page || 1)), safePages);

    const go = (next) => {
        const clamped = Math.min(Math.max(1, next), safePages);
        onPageChange?.(clamped);
    };

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="text-sm text-slate-600">Page {current} of {safePages}</div>

            <nav className="flex items-center gap-1" aria-label="Pagination">
                <button
                    type="button"
                    onClick={() => go(current - 1)}
                    disabled={current <= 1}
                    className={
                        'rounded-xl border px-3 py-2 text-sm font-medium shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:ring-offset-2 ' +
                        (current <= 1
                            ? 'border-slate-200 bg-slate-50 text-slate-400'
                            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50')
                    }
                >
                    Prev
                </button>

                {Array.from({ length: safePages }).map((_, idx) => {
                    const n = idx + 1;
                    const active = n === current;

                    return (
                        <button
                            key={n}
                            type="button"
                            onClick={() => go(n)}
                            className={
                                'rounded-xl border px-3 py-2 text-sm font-semibold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:ring-offset-2 ' +
                                (active
                                    ? 'border-amber-200 bg-amber-50 text-amber-800'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50')
                            }
                            aria-current={active ? 'page' : undefined}
                        >
                            {n}
                        </button>
                    );
                })}

                <button
                    type="button"
                    onClick={() => go(current + 1)}
                    disabled={current >= safePages}
                    className={
                        'rounded-xl border px-3 py-2 text-sm font-medium shadow-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:ring-offset-2 ' +
                        (current >= safePages
                            ? 'border-slate-200 bg-slate-50 text-slate-400'
                            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50')
                    }
                >
                    Next
                </button>
            </nav>
        </div>
    );
}
