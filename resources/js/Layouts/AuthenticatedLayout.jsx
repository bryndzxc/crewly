import { useMemo, useState } from 'react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import Sidebar from '@/Components/Sidebar';
import Dropdown from '@/Components/Dropdown';
import { Link, router, usePage } from '@inertiajs/react';

export default function Authenticated({ user, header, children, contentClassName = 'max-w-7xl mx-auto' }) {
    const [showingSidebar, setShowingSidebar] = useState(false);
    const mustChangePassword = Boolean(user?.must_change_password);
    const avatarUrl = useMemo(() => {
        const base = user?.profile_photo_url;
        if (!base) return null;
        const v = user?.updated_at ? encodeURIComponent(String(user.updated_at)) : '';
        return v ? `${base}?v=${v}` : base;
    }, [user?.profile_photo_url, user?.updated_at]);
    const notifications = usePage().props.notifications || {};
    const unreadCount = Number(notifications.unread_count || 0);
    const latest = useMemo(() => (Array.isArray(notifications.latest) ? notifications.latest : []), [notifications.latest]);

    const severityDotClass = (severity) => {
        const s = String(severity || '').toUpperCase();
        if (s === 'DANGER') return 'bg-red-500';
        if (s === 'WARNING') return 'bg-amber-500';
        if (s === 'SUCCESS') return 'bg-green-500';
        return 'bg-slate-500';
    };

    const openNotification = (n) => {
        const url = n?.url;
        router.patch(
            route('notifications.read', n.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (url) {
                        router.visit(url);
                    }
                },
            }
        );
    };

    return (
        <div className="min-h-screen bg-slate-50 flex">
            {!mustChangePassword && (
                <aside className="hidden md:flex md:w-72 md:flex-col bg-slate-50 border-r border-slate-200">
                    <div className="h-16 flex items-center px-5 border-b border-slate-200/80">
                        <Link href={route('dashboard')} className="flex items-center gap-2">
                            <span className="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white ring-1 ring-slate-200 shadow-sm">
                                <ApplicationLogo className="block h-5 w-auto fill-current text-slate-900" />
                            </span>
                            <span className="font-semibold tracking-tight text-slate-900">Crewly</span>
                            <span className="ml-1 hidden lg:inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800 ring-1 ring-amber-200">
                                Beta
                            </span>
                        </Link>
                    </div>
                    <Sidebar />
                </aside>
            )}

            <div className="flex-1 flex flex-col min-w-0">
                <header className="relative z-30 h-16 bg-white/70 backdrop-blur border-b border-slate-200 flex items-center justify-between px-4 sm:px-6">
                    <div className="flex items-center gap-3 min-w-0">
                        {!mustChangePassword && (
                            <button
                                type="button"
                                onClick={() => setShowingSidebar((v) => !v)}
                                className="md:hidden inline-flex items-center justify-center p-2 rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                                aria-label="Toggle sidebar"
                            >
                                <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        )}

                        <div className="truncate">
                            <div className="text-sm font-medium text-slate-500">Workspace</div>
                            <div className="truncate text-lg font-semibold tracking-tight text-slate-900">
                                {header ?? 'Dashboard'}
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Dropdown>
                            <Dropdown.Trigger>
                                <button
                                    type="button"
                                    className="relative inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2.5 text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                                    aria-label="Open notifications"
                                >
                                    <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0m6 0H9"
                                        />
                                    </svg>

                                    {unreadCount > 0 && (
                                        <span className="absolute -top-1 -right-1 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-amber-500 px-1.5 text-[10px] font-bold text-white ring-2 ring-white">
                                            {unreadCount > 99 ? '99+' : unreadCount}
                                        </span>
                                    )}
                                </button>
                            </Dropdown.Trigger>

                            <Dropdown.Content
                                align="right"
                                width="96"
                                contentClasses="py-2 bg-white border border-slate-200 rounded-xl shadow-xl shadow-slate-900/10"
                            >
                                <div className="px-4 pb-2 flex items-center justify-between gap-3">
                                    <div>
                                        <div className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Notifications</div>
                                        <div className="mt-0.5 text-sm font-semibold text-slate-900">{unreadCount} unread</div>
                                    </div>
                                    <Link
                                        href={route('notifications.index')}
                                        className="text-xs font-semibold text-amber-700 hover:text-amber-800"
                                    >
                                        View all
                                    </Link>
                                </div>
                                <div className="my-2 h-px bg-slate-200" />

                                {latest.length === 0 ? (
                                    <div className="px-4 py-3 text-sm text-slate-600">No notifications yet.</div>
                                ) : (
                                    <div className="max-h-80 overflow-auto">
                                        {latest.map((n) => {
                                            const isUnread = !n.read_at;
                                            return (
                                                <button
                                                    key={n.id}
                                                    type="button"
                                                    onClick={() => openNotification(n)}
                                                    className={
                                                        'w-full px-4 py-3 text-left hover:bg-amber-50 focus:bg-amber-50 focus:outline-none transition ' +
                                                        (isUnread ? 'bg-white' : 'bg-white/70')
                                                    }
                                                >
                                                    <div className="flex items-start gap-3">
                                                        <span className={'mt-1.5 inline-flex h-2.5 w-2.5 rounded-full ' + severityDotClass(n.severity)} />
                                                        <div className="min-w-0 flex-1">
                                                            <div className="flex items-center justify-between gap-2">
                                                                <div className="truncate text-sm font-semibold text-slate-900">{n.title}</div>
                                                                {isUnread && (
                                                                    <span className="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800 ring-1 ring-amber-200">
                                                                        Unread
                                                                    </span>
                                                                )}
                                                            </div>
                                                            {!!n.body && <div className="mt-0.5 text-sm text-slate-700">{n.body}</div>}
                                                            <div className="mt-1 text-xs text-slate-500">{n.created_at_human || n.created_at}</div>
                                                        </div>
                                                    </div>
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}
                            </Dropdown.Content>
                        </Dropdown>

                        <Dropdown>
                            <Dropdown.Trigger>
                                <button
                                    type="button"
                                    className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                                    aria-label="Open user menu"
                                >
                                    <span className="inline-flex h-7 w-7 items-center justify-center overflow-hidden rounded-lg bg-amber-100 text-amber-800 ring-1 ring-amber-200">
                                        {avatarUrl ? (
                                            <img src={avatarUrl} alt="" className="h-full w-full object-cover" />
                                        ) : (
                                            <span className="text-xs font-semibold">{(user?.name || 'U').slice(0, 1).toUpperCase()}</span>
                                        )}
                                    </span>
                                    <span className="hidden sm:inline-flex max-w-[14rem] truncate">{user.name}</span>
                                    <svg viewBox="0 0 20 20" className="h-4 w-4 text-slate-400" fill="currentColor" aria-hidden="true">
                                        <path
                                            fillRule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clipRule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </Dropdown.Trigger>

                            <Dropdown.Content
                                align="right"
                                width="48"
                                contentClasses="py-2 bg-white border border-slate-200 rounded-xl shadow-xl shadow-slate-900/10"
                            >
                                <div className="px-4 pb-2">
                                    <div className="text-xs font-medium text-slate-500">Signed in as</div>
                                    <div className="mt-0.5 truncate text-sm font-semibold text-slate-900">{user.email ?? user.name}</div>
                                </div>
                                <div className="my-2 h-px bg-slate-200" />
                                <Dropdown.Link
                                    href={String(user?.role || '').toLowerCase() === 'employee' ? route('my.profile') : route('profile.edit')}
                                    className="text-slate-700 hover:bg-amber-50 focus:bg-amber-50"
                                >
                                    Profile
                                </Dropdown.Link>
                                <Dropdown.Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                    className="text-slate-700 hover:bg-amber-50 focus:bg-amber-50"
                                >
                                    Log out
                                </Dropdown.Link>
                            </Dropdown.Content>
                        </Dropdown>
                    </div>
                </header>

                {!mustChangePassword && (
                    <div className={(showingSidebar ? 'block' : 'hidden') + ' md:hidden bg-slate-50 border-b border-slate-200'}>
                        <Sidebar />
                    </div>
                )}

                <main className="flex-1 bg-slate-50">
                    <div className="py-8 px-4 sm:px-6 lg:px-8">
                        <div className={contentClassName}>{children}</div>
                    </div>
                </main>
            </div>
        </div>
    );
}
