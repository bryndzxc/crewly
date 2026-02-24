import PublicLayout from '@/Layouts/PublicLayout';
import { Link } from '@inertiajs/react';

export default function Pricing() {
    return (
        <PublicLayout
            title="Pricing"
            description="Crewly pricing is coming soon. Request a demo to see workflows for your team."
            image="/storage-images/product_preview.PNG"
        >
            <div className="mx-auto max-w-5xl px-4 py-16 sm:px-6">
                <div className="rounded-3xl border border-slate-200/70 bg-white/80 backdrop-blur p-8 shadow-xl shadow-slate-900/5">
                    <h1 className="text-3xl font-semibold tracking-tight text-slate-900">Pricing</h1>
                    <p className="mt-2 text-sm text-slate-600">Coming soon.</p>

                    <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Link
                            href={route('public.demo')}
                            className="inline-flex items-center justify-center rounded-xl bg-amber-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                        >
                            Request a demo
                        </Link>
                        <Link
                            href={route('home')}
                            className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                        >
                            Back to home
                        </Link>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
