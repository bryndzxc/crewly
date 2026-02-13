import { Popover } from '@headlessui/react';
import { useEffect, useMemo, useState } from 'react';

function pad2(n) {
    return String(n).padStart(2, '0');
}

function parseMonth(value) {
    if (!value || typeof value !== 'string') return null;
    const match = value.match(/^(\d{4})-(\d{2})$/);
    if (!match) return null;
    const year = Number(match[1]);
    const monthIndex = Number(match[2]) - 1;
    if (!Number.isFinite(year) || !Number.isFinite(monthIndex) || monthIndex < 0 || monthIndex > 11) return null;
    return { year, monthIndex };
}

function toMonthValue(year, monthIndex) {
    return `${year}-${pad2(monthIndex + 1)}`;
}

export default function MonthPicker({ id, name, value, onChange, placeholder = 'yyyy-mm', disabled = false }) {
    const parsed = useMemo(() => parseMonth(value), [value]);
    const [viewYear, setViewYear] = useState(() => parsed?.year ?? new Date().getFullYear());

    useEffect(() => {
        if (parsed?.year) {
            setViewYear(parsed.year);
        }
    }, [value]);

    const displayValue = typeof value === 'string' ? (value.match(/^(\d{4}-\d{2})/)?.[1] ?? value) : '';

    const months = useMemo(() => {
        const dtf = new Intl.DateTimeFormat(undefined, { month: 'short' });
        return Array.from({ length: 12 }).map((_, i) => dtf.format(new Date(2000, i, 1)));
    }, []);

    return (
        <Popover className="relative">
            {({ open, close }) => (
                <>
                    <Popover.Button
                        id={id}
                        name={name}
                        type="button"
                        disabled={disabled}
                        className={`mt-1 block w-full rounded-md border-slate-300 bg-white/90 px-3 py-2 text-left text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30 ${
                            disabled ? 'cursor-not-allowed opacity-70' : 'cursor-pointer'
                        }`}
                    >
                        <span className={displayValue ? '' : 'text-slate-400'}>{displayValue || placeholder}</span>
                    </Popover.Button>

                    {open && (
                        <Popover.Panel static className="absolute z-50 mt-2 w-72 rounded-lg border border-gray-200 bg-white shadow-lg">
                            <div className="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                                <button
                                    type="button"
                                    className="rounded-md px-2 py-1 text-sm text-gray-600 hover:text-gray-900"
                                    onClick={() => setViewYear((y) => y - 1)}
                                >
                                    ‹
                                </button>
                                <div className="text-sm font-semibold text-gray-900">{viewYear}</div>
                                <button
                                    type="button"
                                    className="rounded-md px-2 py-1 text-sm text-gray-600 hover:text-gray-900"
                                    onClick={() => setViewYear((y) => y + 1)}
                                >
                                    ›
                                </button>
                            </div>

                            <div className="px-4 py-3">
                                <div className="grid grid-cols-3 gap-2">
                                    {months.map((label, monthIndex) => {
                                        const isSelected = parsed?.year === viewYear && parsed?.monthIndex === monthIndex;

                                        return (
                                            <button
                                                key={`${viewYear}-${monthIndex}`}
                                                type="button"
                                                className={`h-10 rounded-md text-sm transition ${
                                                    isSelected
                                                        ? 'bg-amber-500 text-white'
                                                        : 'text-gray-900 hover:bg-amber-50 hover:text-gray-900'
                                                }`}
                                                onClick={() => {
                                                    onChange?.(toMonthValue(viewYear, monthIndex));
                                                    close();
                                                }}
                                            >
                                                {label}
                                            </button>
                                        );
                                    })}
                                </div>

                                <div className="mt-3 flex items-center justify-between">
                                    <button
                                        type="button"
                                        className="text-sm text-gray-600 hover:text-gray-900"
                                        onClick={() => {
                                            onChange?.('');
                                            close();
                                        }}
                                    >
                                        Clear
                                    </button>

                                    <button type="button" className="text-sm text-gray-600 hover:text-gray-900" onClick={() => close()}>
                                        Close
                                    </button>
                                </div>
                            </div>
                        </Popover.Panel>
                    )}
                </>
            )}
        </Popover>
    );
}
