import React from 'react';

export default function Tabs({ tabs = [], value, onChange }) {
    return (
        <div className="border-b border-slate-200">
            <nav className="flex gap-6 overflow-x-auto" aria-label="Tabs">
                {tabs.map((tab) => {
                    const active = tab.key === value;

                    return (
                        <button
                            key={tab.key}
                            type="button"
                            onClick={() => onChange?.(tab.key)}
                            className={
                                'whitespace-nowrap px-1 py-3 text-sm font-semibold transition ' +
                                (active
                                    ? 'text-slate-900 shadow-[inset_0_-2px_0_0_#f59e0b]'
                                    : 'text-slate-600 hover:text-slate-900 shadow-[inset_0_-2px_0_0_transparent] hover:shadow-[inset_0_-2px_0_0_#cbd5e1]')
                            }
                            aria-current={active ? 'page' : undefined}
                        >
                            {tab.label}
                        </button>
                    );
                })}
            </nav>
        </div>
    );
}
