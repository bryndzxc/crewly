import React from 'react';
import Card from '@/Components/UI/Card';

export default function StatCard({ title, value, caption, icon }) {
    return (
        <Card className="p-5">
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                    <div className="text-sm font-medium text-slate-600">{title}</div>
                    <div className="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{value}</div>
                    {caption ? <div className="mt-1 text-xs text-slate-500">{caption}</div> : null}
                </div>
                {icon ? (
                    <div className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                        {icon}
                    </div>
                ) : null}
            </div>
        </Card>
    );
}
