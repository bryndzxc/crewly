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
        case 'departments':
            return (
                <svg {...commonProps}>
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M3 21h18M6 21V5a2 2 0 012-2h8a2 2 0 012 2v16"
                    />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 7h.01M9 11h.01M9 15h.01M12 7h.01M12 11h.01M12 15h.01M15 7h.01M15 11h.01M15 15h.01" />
                </svg>
            );
        case 'leave':
            return (
                <svg {...commonProps}>
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M8 7V3m8 4V3M4 11h16"
                    />
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M6 5h12a2 2 0 012 2v13a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z"
                    />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 15l2 2 4-4" />
                </svg>
            );
        case 'leave_types':
            return (
                <svg {...commonProps}>
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M7 7h6l4 4v6a2 2 0 01-2 2H7a2 2 0 01-2-2V9a2 2 0 012-2z"
                    />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M13 7v4h4" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M8.5 13h7M8.5 16h5" />
                </svg>
            );
        case 'attendance':
            return (
                <svg {...commonProps}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v5l3 2" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            );
        case 'payroll':
            return (
                <svg {...commonProps}>
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M3 10h18M6 6h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2z"
                    />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M8 14h.01M12 14h.01M16 14h.01" />
                </svg>
            );
        case 'chat':
            return (
                <svg {...commonProps}>
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M7 8h10M7 12h6"
                    />
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M21 12a7 7 0 01-7 7H9l-4 3v-6a7 7 0 01-2-5 7 7 0 017-7h4a7 7 0 017 7z"
                    />
                </svg>
            );
        case 'settings':
            return (
                <svg {...commonProps}>
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M10.325 4.317a1 1 0 011.35-.436l.343.171a1 1 0 001.264-.289l.24-.3a1 1 0 011.556 0l.24.3a1 1 0 001.264.289l.343-.171a1 1 0 011.35.436l.12.367a1 1 0 00.95.69h.37a1 1 0 011 1v.4a1 1 0 00.293.707l.262.262a1 1 0 010 1.414l-.262.262A1 1 0 0021 11.43v.4a1 1 0 01-1 1h-.37a1 1 0 00-.95.69l-.12.367a1 1 0 01-1.35.436l-.343-.171a1 1 0 00-1.264.289l-.24.3a1 1 0 01-1.556 0l-.24-.3a1 1 0 00-1.264-.289l-.343.171a1 1 0 01-1.35-.436l-.12-.367a1 1 0 00-.95-.69H6a1 1 0 01-1-1v-.4a1 1 0 00-.293-.707l-.262-.262a1 1 0 010-1.414l.262-.262A1 1 0 005 8.77v-.4a1 1 0 011-1h.37a1 1 0 00.95-.69l.12-.367z"
                    />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 10a2 2 0 100 4 2 2 0 000-4z" />
                </svg>
            );
        case 'memo':
            return (
                <svg {...commonProps}>
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M7 3h7l3 3v15a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"
                    />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M14 3v4h4" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M8 11h8M8 15h8M8 19h5" />
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

export default function Sidebar({ chatUnreadCount: chatUnreadCountProp = null }) {
    const { url } = usePage();
    const role = String(usePage()?.props?.auth?.user?.role || '').toLowerCase();
    const can = useCan();

    const chat = usePage().props?.chat || {};
    const chatUnreadCount = Number((chatUnreadCountProp ?? chat.unread_count) || 0);

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
                                const isChat = item.routeName === 'chat.index';
                                const showChatBadge = isChat && chatUnreadCount > 0;

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
                                        {(showChatBadge || active) && (
                                            <span className="ml-auto flex items-center gap-2">
                                                {showChatBadge && (
                                                    <span className="inline-flex min-w-6 items-center justify-center rounded-full bg-slate-900 px-2 py-0.5 text-xs font-semibold text-white">
                                                        {chatUnreadCount > 99 ? '99+' : chatUnreadCount}
                                                    </span>
                                                )}
                                                {active && <span className="h-2 w-2 rounded-full bg-amber-500" aria-hidden="true" />}
                                            </span>
                                        )}
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
