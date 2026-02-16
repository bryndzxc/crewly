import { Link, usePage } from '@inertiajs/react';
import { navigation } from '@/config/navigation';
import useCan from '@/hooks/useCan';
import { useMemo, useState } from 'react';

function isActive(url, patterns) {
    return (patterns || []).some((pattern) => url === pattern || url.startsWith(pattern));
}

function Icon({ name, className }) {
    const commonProps = {
        viewBox: '0 0 24 24',
        className,
        fill: 'none',
        stroke: 'currentColor',
        strokeWidth: 2,
    };

    switch (name) {
        case 'dashboard':
            return (
                <svg {...commonProps}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M3 12l9-9 9 9" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 21V9h6v12" />
                </svg>
            );
        case 'employees':
            return (
                <svg {...commonProps}>
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                </svg>
            );
        case 'recruitment':
            return (
                <svg {...commonProps}>
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M8 7V6a4 4 0 118 0v1M5 7h14l-1 14H6L5 7z"
                    />
                </svg>
            );
        case 'account':
            return (
                <svg {...commonProps}>
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M12 12a5 5 0 100-10 5 5 0 000 10z"
                    />
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M3 22a9 9 0 0118 0"
                    />
                </svg>
            );
        case 'users':
            return (
                <svg {...commonProps}>
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"
                    />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 11a4 4 0 100-8 4 4 0 000 8z" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M23 21v-2a4 4 0 00-3-3.87" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M16 3.13a4 4 0 010 7.75" />
                </svg>
            );
        case 'roles':
            return (
                <svg {...commonProps}>
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M12 15l-2.5 1.5.7-2.8L8 11.8l2.9-.2L12 9l1.1 2.6 2.9.2-2.2 1.9.7 2.8L12 15z"
                    />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M20 12a8 8 0 11-16 0 8 8 0 0116 0z" />
                </svg>
            );
        case 'lock':
            return (
                <svg {...commonProps}>
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M17 11H7V8a5 5 0 0110 0v3z"
                    />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M7 11h10v10H7V11z" />
                </svg>
            );
        default:
            return (
                <svg {...commonProps}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            );
    }
}

function Chevron({ open }) {
    return (
        <svg
            viewBox="0 0 20 20"
            className={(open ? 'rotate-180 ' : '') + 'h-4 w-4 text-slate-400 transition-transform'}
            fill="currentColor"
            aria-hidden="true"
        >
            <path
                fillRule="evenodd"
                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                clipRule="evenodd"
            />
        </svg>
    );
}

export default function Sidebar() {
    const { url } = usePage();
    const role = String(usePage()?.props?.auth?.user?.role || '').toLowerCase();
    const can = useCan();

    const roleAllows = (item) => {
        const roles = item?.roles;
        if (!Array.isArray(roles) || roles.length === 0) {
            // For Employee users, default to hiding non-My-Portal items unless explicitly allowed.
            return role !== 'employee';
        }
        return roles.map((r) => String(r).toLowerCase()).includes(role);
    };

    const visibleNavigation = useMemo(() => {
        return navigation
            .map((item) => {
                if (item.type === 'group') {
                    const children = (item.children || []).filter((c) => roleAllows(c) && can(c.ability));
                    return children.length ? { ...item, children } : null;
                }

                return roleAllows(item) && can(item.ability) ? item : null;
            })
            .filter(Boolean);
    }, [can, role]);

    const groupInitiallyOpen = (group) => (group.children || []).some((c) => isActive(url, c.activePatterns));
    const [openGroups, setOpenGroups] = useState(() => {
        const state = {};
        for (const item of navigation) {
            if (item.type === 'group') state[item.label] = groupInitiallyOpen(item);
        }
        return state;
    });

    const topLinks = visibleNavigation.filter((i) => i.type === 'link');
    const groups = visibleNavigation.filter((i) => i.type === 'group');

    return (
        <div className="px-3 py-5">
            <nav className="space-y-4">
                {topLinks.length > 0 && (
                    <div>
                        <div className="px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Core</div>
                        <div className="mt-2 space-y-1">
                            {topLinks.map((item) => {
                                const active = isActive(url, item.activePatterns);

                                return (
                                    <Link
                                        key={item.routeName}
                                        href={route(item.routeName)}
                                        className={
                                            'group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition ' +
                                            (active
                                                ? 'bg-amber-50 text-slate-900 ring-1 ring-amber-200'
                                                : 'text-slate-700 hover:bg-amber-50/60 hover:text-slate-900')
                                        }
                                    >
                                        <span
                                            className={
                                                'shrink-0 ' +
                                                (active
                                                    ? 'text-amber-600'
                                                    : 'text-slate-400 group-hover:text-amber-600')
                                            }
                                            aria-hidden="true"
                                        >
                                            <Icon name={item.iconKey} className="h-5 w-5" />
                                        </span>
                                        <span className="truncate">{item.label}</span>
                                        {active && <span className="ml-auto h-2 w-2 rounded-full bg-amber-500" aria-hidden="true" />}
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                )}

                {groups.length > 0 && (
                    <div>
                        <div className="px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Settings</div>
                        <div className="mt-2 space-y-2">
                            {groups.map((item) => {
                                const open = Boolean(openGroups[item.label]);
                                const groupActive = (item.children || []).some((c) => isActive(url, c.activePatterns));

                                return (
                                    <div key={item.label} className="rounded-xl">
                                        <button
                                            type="button"
                                            onClick={() => setOpenGroups((prev) => ({ ...prev, [item.label]: !open }))}
                                            className={
                                                'w-full flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition ' +
                                                (groupActive
                                                    ? 'bg-amber-50 text-slate-900 ring-1 ring-amber-200'
                                                    : 'text-slate-700 hover:bg-amber-50/60 hover:text-slate-900')
                                            }
                                        >
                                            <span className="flex items-center gap-3 min-w-0">
                                                <span
                                                    className={
                                                        'shrink-0 ' +
                                                        (groupActive
                                                            ? 'text-amber-600'
                                                            : 'text-slate-400 group-hover:text-amber-600')
                                                    }
                                                    aria-hidden="true"
                                                >
                                                    <Icon name={item.iconKey} className="h-5 w-5" />
                                                </span>
                                                <span className="truncate">{item.label}</span>
                                            </span>
                                            <Chevron open={open} />
                                        </button>

                                        {open && (
                                            <div className="mt-2 space-y-1 pl-3">
                                                {(item.children || []).map((child) => {
                                                    const active = isActive(url, child.activePatterns);
                                                    return (
                                                        <Link
                                                            key={child.routeName}
                                                            href={route(child.routeName)}
                                                            className={
                                                                'group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition ' +
                                                                (active
                                                                    ? 'bg-amber-50 text-slate-900 ring-1 ring-amber-200'
                                                                    : 'text-slate-700 hover:bg-amber-50/60 hover:text-slate-900')
                                                            }
                                                        >
                                                            <span
                                                                className={
                                                                    'shrink-0 ' +
                                                                    (active
                                                                        ? 'text-amber-600'
                                                                        : 'text-slate-400 group-hover:text-amber-600')
                                                                }
                                                                aria-hidden="true"
                                                            >
                                                                <Icon name={child.iconKey} className="h-5 w-5" />
                                                            </span>
                                                            <span className="truncate">{child.label}</span>
                                                            {active && (
                                                                <span
                                                                    className="ml-auto h-2 w-2 rounded-full bg-amber-500"
                                                                    aria-hidden="true"
                                                                />
                                                            )}
                                                        </Link>
                                                    );
                                                })}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {visibleNavigation.length === 0 && (
                    <div className="px-3 py-6 text-sm text-slate-600">No navigation items available.</div>
                )}
            </nav>
        </div>
    );
}
