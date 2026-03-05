import PublicLayout from '@/Layouts/PublicLayout';
import { Link, usePage } from '@inertiajs/react';
import { useMemo } from 'react';

export default function Demo() {
    const props = usePage().props;
    const sharedDemo = props.shared_demo ?? {};
    const demoEmail = useMemo(() => String(sharedDemo?.email || '').trim(), [sharedDemo?.email]);
    const demoPassword = useMemo(() => String(sharedDemo?.password || '').trim(), [sharedDemo?.password]);
    const demoEnabled = Boolean(sharedDemo?.enabled);

    const ogImage = '/storage-images/product_preview.PNG';

    const demoLoginHref = useMemo(() => {
        if (!demoEnabled || !demoEmail) return route('login');
        return route('public.demo.login');
    }, [demoEnabled, demoEmail]);

    return (
        <PublicLayout
            title="Demo"
            description="Explore Crewly using the shared live demo environment."
            image={ogImage}
        >
            <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6">
                <div className="mx-auto max-w-3xl">
                    <h1 className="text-3xl font-semibold tracking-tight text-slate-900">Demo</h1>
                    <p className="mt-3 text-sm text-slate-600 leading-relaxed">
                        Use the shared live demo environment to explore the product.
                    </p>

                    <div className="mt-10">
                        <div className="mb-4 text-sm text-slate-600">
                            Prefer instant access?{' '}
                            {demoEnabled && demoEmail && demoPassword ? (
                                <>
                                    <span className="font-semibold text-slate-900">Email:</span>{' '}
                                    <span className="font-mono text-slate-900">{demoEmail}</span>{' '}
                                    <span className="mx-2 text-slate-300">|</span>{' '}
                                    <span className="font-semibold text-slate-900">Password:</span>{' '}
                                    <span className="font-mono text-slate-900">{demoPassword}</span>
                                    <span className="mx-2 text-slate-300">|</span>{' '}
                                </>
                            ) : null}
                            <Link href={demoLoginHref} className="font-semibold text-amber-800 hover:text-amber-900">
                                Explore our live demo environment.
                            </Link>
                        </div>

                        {demoEnabled && demoEmail && demoPassword ? (
                            <div className="mb-4 rounded-2xl border border-slate-200/70 bg-white/80 backdrop-blur p-6 shadow-lg shadow-slate-900/5">
                                <div className="text-sm font-semibold text-slate-900">Live demo login</div>
                                <div className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div className="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</div>
                                        <div className="mt-1 font-mono text-sm text-slate-900 break-all">{demoEmail}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Password</div>
                                        <div className="mt-1 font-mono text-sm text-slate-900 break-all">{demoPassword}</div>
                                    </div>
                                </div>
                                <div className="mt-3 text-xs text-slate-500">Shared environment. Data may reset periodically.</div>
                            </div>
                        ) : null}
                        <div className="mt-4 text-xs text-slate-500">
                            Looking for pricing? See{' '}
                            <Link href={route('pricing.index')} className="font-semibold text-amber-800 hover:text-amber-900">
                                /pricing
                            </Link>
                            .
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
