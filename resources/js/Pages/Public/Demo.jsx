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
