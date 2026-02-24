import PublicLayout from '@/Layouts/PublicLayout';
import LeadForm from '@/Components/Public/LeadForm';
import { Link } from '@inertiajs/react';

function KeyValue({ label, value }) {
    return (
        <div className="flex items-start justify-between gap-4 border-b border-slate-200/70 py-3">
            <div className="text-sm text-slate-600">{label}</div>
            <div className="text-sm font-semibold text-slate-900 text-right break-all">{value}</div>
        </div>
    );
}

export default function Demo() {
    const ogImage = '/storage-images/product_preview.PNG';
    const demoEmail = 'demo@crewly.test';
    const demoLoginUrl = (() => {
        try {
            return `${route('login')}?email=${encodeURIComponent(demoEmail)}`;
        } catch {
            return '/login';
        }
    })();

    return (
        <PublicLayout
            title="Demo"
            description="Try the Crewly demo and explore core HR workflows. Shared demo access resets periodically."
            image={ogImage}
        >
            <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6">
                <div className="grid grid-cols-1 gap-10 lg:grid-cols-2 lg:items-start">
                    <div>
                        <h1 className="text-3xl font-semibold tracking-tight text-slate-900">Demo</h1>
                        <p className="mt-3 text-sm text-slate-600 leading-relaxed">
                            Use the demo access below for a quick look, or submit a request so we can walk you through your exact workflows.
                        </p>

                        <div className="mt-8 rounded-2xl border border-slate-200/70 bg-white/80 backdrop-blur p-6 shadow-lg shadow-slate-900/5">
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <div className="text-sm font-semibold text-slate-900">Demo access</div>
                                    <div className="mt-1 text-xs text-slate-500">Demo resets periodically.</div>
                                </div>
                                <Link
                                    href={demoLoginUrl}
                                    className="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                                >
                                    Demo login
                                </Link>
                            </div>

                            <div className="mt-4">
                                <KeyValue label="Email" value={demoEmail} />
                                <KeyValue label="Password" value="demo-password" />
                            </div>

                            <div className="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                                If read-only controls are not enforced yet, please assume changes may be reset.
                            </div>
                        </div>
                    </div>

                    <div>
                        <LeadForm sourcePage="/demo" />
                        <div className="mt-4 text-xs text-slate-500">
                            Looking for pricing? See <Link href={route('public.pricing')} className="font-semibold text-amber-800 hover:text-amber-900">/pricing</Link>.
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
