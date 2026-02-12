import { Popover } from '@headlessui/react';
import { useEffect, useMemo, useState } from 'react';

function pad2(n) {
    return String(n).padStart(2, '0');
}

function toISODate(date) {
    const y = date.getFullYear();
    const m = pad2(date.getMonth() + 1);
    const d = pad2(date.getDate());
    return `${y}-${m}-${d}`;
}

function parseISODate(value) {
    if (!value || typeof value !== 'string') return null;
    // Force midnight to avoid timezone shifts.
    const dt = new Date(`${value}T00:00:00`);
    if (Number.isNaN(dt.getTime())) return null;
    return dt;
}

function startOfMonth(date) {
    return new Date(date.getFullYear(), date.getMonth(), 1);
}

function addMonths(date, delta) {
    return new Date(date.getFullYear(), date.getMonth() + delta, 1);
}

function isSameDay(a, b) {
    return (
        a?.getFullYear() === b?.getFullYear() && a?.getMonth() === b?.getMonth() && a?.getDate() === b?.getDate()
    );
}

export default function DatePicker({ id, name, value, onChange, placeholder = 'dd/mm/yyyy', disabled = false }) {
    const selectedDate = useMemo(() => parseISODate(value), [value]);

    const [viewMonth, setViewMonth] = useState(() => startOfMonth(selectedDate ?? new Date()));

    useEffect(() => {
        if (selectedDate) {
            setViewMonth(startOfMonth(selectedDate));
        }
    }, [value]);

    const monthLabel = useMemo(() => {
        const dtf = new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' });
        return dtf.format(viewMonth);
    }, [viewMonth]);

    const days = useMemo(() => {
        const first = startOfMonth(viewMonth);
        const startDow = first.getDay(); // 0=Sun
        const start = new Date(first);
        start.setDate(first.getDate() - startDow);

        const cells = [];
        for (let i = 0; i < 42; i++) {
            const d = new Date(start);
            d.setDate(start.getDate() + i);
            cells.push(d);
        }
        return cells;
    }, [viewMonth]);

    const displayValue = value ? value : '';

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
                        <Popover.Panel
                            static
                            className="absolute z-20 mt-2 w-80 rounded-lg border border-gray-200 bg-white shadow-lg"
                        >
                            <div className="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                                <button
                                    type="button"
                                    className="rounded-md px-2 py-1 text-sm text-gray-600 hover:text-gray-900"
                                    onClick={() => setViewMonth((m) => addMonths(m, -1))}
                                >
                                    ‹
                                </button>
                                <div className="text-sm font-semibold text-gray-900">{monthLabel}</div>
                                <button
                                    type="button"
                                    className="rounded-md px-2 py-1 text-sm text-gray-600 hover:text-gray-900"
                                    onClick={() => setViewMonth((m) => addMonths(m, 1))}
                                >
                                    ›
                                </button>
                            </div>

                            <div className="px-4 py-3">
                                <div className="grid grid-cols-7 gap-1 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <div>Sun</div>
                                    <div>Mon</div>
                                    <div>Tue</div>
                                    <div>Wed</div>
                                    <div>Thu</div>
                                    <div>Fri</div>
                                    <div>Sat</div>
                                </div>

                                <div className="mt-2 grid grid-cols-7 gap-1">
                                    {days.map((d) => {
                                        const inMonth = d.getMonth() === viewMonth.getMonth();
                                        const isSelected = selectedDate ? isSameDay(d, selectedDate) : false;

                                        return (
                                            <button
                                                key={toISODate(d)}
                                                type="button"
                                                className={`h-9 rounded-md text-sm transition ${
                                                    inMonth ? 'text-gray-900' : 'text-gray-400'
                                                } ${
                                                    isSelected
                                                        ? 'bg-amber-500 text-white'
                                                        : 'hover:bg-amber-50 hover:text-gray-900'
                                                }`}
                                                onClick={() => {
                                                    onChange?.(toISODate(d));
                                                    close();
                                                }}
                                            >
                                                {d.getDate()}
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

                                    <button
                                        type="button"
                                        className="text-sm text-gray-600 hover:text-gray-900"
                                        onClick={() => close()}
                                    >
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
