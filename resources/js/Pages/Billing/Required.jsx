import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, router, usePage } from '@inertiajs/react';

export default function BillingRequired() {
    const { props } = usePage();
    const auth = props.auth ?? {};
    const company = auth.company ?? null;

    return (
        <AuthenticatedLayout user={auth.user} header="Billing Required" contentClassName="max-w-3xl mx-auto">
            <Head title="Billing Required" />

            <Card className="p-8">
                <div className="text-sm font-semibold text-slate-900">Subscription suspended</div>
                <div className="mt-2 text-sm text-slate-600">
                    {company?.name ? (
                        <span>
                            Access to <span className="font-semibold text-slate-900">{company.name}</span> is temporarily suspended.
                        </span>
                    ) : (
                        <span>Access is temporarily suspended.</span>
                    )}
                    <span className="block mt-2">Please contact support to restore access. Manual billing only (invoice-based).</span>
                </div>

                <div className="mt-6">
                    <PrimaryButton
                        type="button"
                        onClick={() => {
                            router.visit(
                                route('chat.support', {
                                    message: "Hi! Our Crewly account is showing 'Billing Required'. Please help us reactivate our subscription.",
                                })
                            );
                        }}
                    >
                        Contact support
                    </PrimaryButton>
                </div>
            </Card>
        </AuthenticatedLayout>
    );
}
