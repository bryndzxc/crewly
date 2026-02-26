import PublicLayout from '@/Layouts/PublicLayout';
import LeadForm from '@/Components/Public/LeadForm';
import { Link } from '@inertiajs/react';

export default function Demo() {
    const ogImage = '/storage-images/product_preview.PNG';

    return (
        <PublicLayout
            title="Demo"
            description="Request a Crewly demo and explore core HR workflows. We’ll reach out to walk you through your exact workflows."
            image={ogImage}
        >
            <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6">
                <div className="mx-auto max-w-3xl">
                    <h1 className="text-3xl font-semibold tracking-tight text-slate-900">Demo</h1>
                    <p className="mt-3 text-sm text-slate-600 leading-relaxed">
                        Submit a request and we’ll reach out to walk you through your exact workflows.
                    </p>

                    <div className="mt-10">
                        <div className="mb-4 text-sm text-slate-600">
                            Prefer instant access?{' '}
                            <Link href={route('login')} className="font-semibold text-amber-800 hover:text-amber-900">
                                Explore our live demo environment.
                            </Link>
                        </div>

                        <div className="mb-4 rounded-2xl border border-slate-200/70 bg-white/80 backdrop-blur p-6 shadow-lg shadow-slate-900/5">
                            <div className="text-sm font-semibold text-slate-900">What happens next?</div>
                            <ul className="mt-3 space-y-2 text-sm text-slate-700">
                                <li className="flex gap-2"><span className="text-amber-700">•</span>We schedule a short 15-minute walkthrough</li>
                                <li className="flex gap-2"><span className="text-amber-700">•</span>We show how Crewly fits your HR workflow</li>
                                <li className="flex gap-2"><span className="text-amber-700">•</span>You receive a free 30-day trial workspace</li>
                                <li className="flex gap-2"><span className="text-amber-700">•</span>No obligation to subscribe</li>
                            </ul>
                        </div>

                        <LeadForm sourcePage="/demo" />
                        <div className="mt-4 text-xs text-slate-500">
                            Looking for pricing? See{' '}
                            <Link href={route('public.pricing')} className="font-semibold text-amber-800 hover:text-amber-900">
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
