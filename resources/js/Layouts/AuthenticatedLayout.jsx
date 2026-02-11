import { useState } from 'react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import Sidebar from '@/Components/Sidebar';
import Dropdown from '@/Components/Dropdown';
import { Link } from '@inertiajs/react';

export default function Authenticated({ user, header, children, contentClassName = 'max-w-7xl mx-auto' }) {
    const [showingSidebar, setShowingSidebar] = useState(false);

    return (
        <div className="min-h-screen bg-slate-50 flex">
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

            <div className="flex-1 flex flex-col min-w-0">
                <header className="relative z-30 h-16 bg-white/70 backdrop-blur border-b border-slate-200 flex items-center justify-between px-4 sm:px-6">
                    <div className="flex items-center gap-3 min-w-0">
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
                                    className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                                    aria-label="Open user menu"
                                >
                                    <span className="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 text-amber-800 ring-1 ring-amber-200">
                                        <span className="text-xs font-semibold">{(user?.name || 'U').slice(0, 1).toUpperCase()}</span>
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

                <div className={(showingSidebar ? 'block' : 'hidden') + ' md:hidden bg-slate-50 border-b border-slate-200'}>
                    <Sidebar />
                </div>

                <main className="flex-1 bg-slate-50">
                    <div className="py-8 px-4 sm:px-6 lg:px-8">
                        <div className={contentClassName}>{children}</div>
                    </div>
                </main>
            </div>
        </div>
    );
}
