import React from 'react';

const toneClasses = {
    neutral: 'bg-slate-100 text-slate-700 ring-slate-200',
    amber: 'bg-amber-100 text-amber-800 ring-amber-200',
    success: 'bg-emerald-100 text-emerald-800 ring-emerald-200',
    danger: 'bg-rose-100 text-rose-800 ring-rose-200',
};

export function toneFromStatus(status) {
    const value = String(status || '').toLowerCase();
    if (value.includes('active')) return 'success';
    if (value.includes('leave')) return 'amber';
    if (value.includes('terminated')) return 'danger';
    if (value.includes('inactive')) return 'neutral';
    return 'neutral';
}

export default function Badge({ children, tone = 'neutral', className = '' }) {
    const classes = toneClasses[tone] ?? toneClasses.neutral;

    return (
        <span
            className={
                'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ' +
                classes +
                ' ' +
                className
            }
        >
            {children}
        </span>
    );
}
