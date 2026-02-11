import React from 'react';

export default function Card({ className = '', children }) {
    return (
        <div
            className={
                'rounded-2xl border border-slate-200/70 bg-white/80 backdrop-blur shadow-lg shadow-slate-900/5 ' +
                className
            }
        >
            {children}
        </div>
    );
}
