import ApplicationLogo from '@/Components/ApplicationLogo';
import { Head, Link, usePage } from '@inertiajs/react';
import { useMemo } from 'react';

function NavLink({ href, children }) {
    const { url } = usePage();
    const isActive = useMemo(() => {
        const currentPath = String(url || '');
        const targetPath = (() => {
            try {
                return new URL(String(href || ''), window.location.origin).pathname;
            } catch {
                return String(href || '');
            }
        })();

        return currentPath === targetPath || (targetPath !== '/' && currentPath.startsWith(targetPath));
    }, [url, href]);

    return (
        <Link
            href={href}
            className={
                'rounded-lg px-3 py-2 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-amber-500/40 focus:ring-offset-2 ' +
                (isActive ? 'text-slate-900 bg-amber-50 ring-1 ring-amber-200' : 'text-slate-700 hover:bg-amber-50/60 hover:text-slate-900')
            }
        >
            {children}
        </Link>
    );
}

export default function PublicLayout({ title, description, image, children }) {
    const { url } = usePage();

    const appName = import.meta.env.VITE_APP_NAME || 'Crewly';
    const metaTitle = useMemo(() => {
        const t = String(title || '').trim();
        if (!t) return appName;
        if (t.toLowerCase() === appName.toLowerCase()) return appName;
        if (t.toLowerCase().endsWith(`- ${appName}`.toLowerCase())) return t;
        return `${t} - ${appName}`;
    }, [title, appName]);

    const metaDescription = String(description || 'HR documentation & incident tracking for PH SMEs.').trim();
    const metaImage = String(image || '/storage-images/crewly_logo.png').trim();

    const canonical = useMemo(() => {
        const path = String(url || '/');
        try {
            return new URL(path, window.location.origin).toString();
        } catch {
            return null;
        }
    }, [url]);

    const absoluteImage = useMemo(() => {
        try {
            return new URL(metaImage, window.location.origin).toString();
        } catch {
            return metaImage;
        }
    }, [metaImage]);

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col">
            <Head title={title || appName}>
                <meta name="description" content={metaDescription} />
                {canonical ? <link rel="canonical" href={canonical} /> : null}

                <meta property="og:site_name" content={appName} />
                <meta property="og:type" content="website" />
                <meta property="og:title" content={metaTitle} />
                <meta property="og:description" content={metaDescription} />
                {canonical ? <meta property="og:url" content={canonical} /> : null}
                <meta property="og:image" content={absoluteImage} />

                <meta name="twitter:card" content="summary_large_image" />
                <meta name="twitter:title" content={metaTitle} />
                <meta name="twitter:description" content={metaDescription} />
                <meta name="twitter:image" content={absoluteImage} />
            </Head>

            <header className="sticky top-0 z-30 bg-white/70 backdrop-blur border-b border-slate-200">
                <div className="mx-auto max-w-7xl px-4 sm:px-6">
                    <div className="h-16 flex items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <Link href={route('home')} className="flex items-center gap-2">
                                <span className="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white ring-1 ring-slate-200 shadow-sm">
                                    <ApplicationLogo className="block h-6 w-6" />
                                </span>
                                <span className="font-semibold tracking-tight text-slate-900">Crewly</span>
                                <span className="ml-1 hidden sm:inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800 ring-1 ring-amber-200">
                                    Beta
                                </span>
                            </Link>
                        </div>

                        <nav className="hidden md:flex items-center gap-1">
                            <NavLink href={route('public.pricing')}>Pricing</NavLink>
                            <NavLink href={route('public.demo')}>Demo</NavLink>
                            <NavLink href={route('public.privacy')}>Privacy</NavLink>
                            <NavLink href={route('public.terms')}>Terms</NavLink>
                        </nav>

                        <div className="flex items-center gap-2">
                            <Link
                                href={route('login')}
                                className="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                            >
                                Login
                            </Link>
                            <Link
                                href={route('public.demo')}
                                className="hidden sm:inline-flex items-center rounded-xl bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                            >
                                Request demo
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <main className="flex-1">
                {children}
            </main>

            <footer className="border-t border-slate-200 bg-white/60 backdrop-blur">
                <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
                    <div className="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="text-sm font-semibold text-slate-900">Crewly</div>
                            <div className="mt-1 text-sm text-slate-600">HR documentation & incident tracking for PH SMEs.</div>
                        </div>
                        <div className="flex flex-wrap items-center gap-4 text-sm">
                            <Link href={route('public.pricing')} className="font-medium text-slate-700 hover:text-slate-900">Pricing</Link>
                            <Link href={route('public.demo')} className="font-medium text-slate-700 hover:text-slate-900">Demo</Link>
                            <Link href={route('public.privacy')} className="font-medium text-slate-700 hover:text-slate-900">Privacy</Link>
                            <Link href={route('public.terms')} className="font-medium text-slate-700 hover:text-slate-900">Terms</Link>
                        </div>
                    </div>
                    <div className="mt-8 text-xs text-slate-500">© {new Date().getFullYear()} Crewly. All rights reserved.</div>
                </div>
            </footer>
        </div>
    );
}
