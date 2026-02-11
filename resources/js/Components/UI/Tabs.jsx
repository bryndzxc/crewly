import React from 'react';

export default function Tabs({ tabs = [], value, onChange }) {
    return (
        <div className="border-b border-slate-200">
            <nav className="-mb-px flex gap-6 overflow-x-auto" aria-label="Tabs">
                {tabs.map((tab) => {
                    const active = tab.key === value;

                    return (
                        <button
                            key={tab.key}
                            type="button"
                            onClick={() => onChange?.(tab.key)}
                            className={
                                'whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-semibold transition ' +
                                (active
                                    ? 'border-amber-500 text-slate-900'
                                    : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300')
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
