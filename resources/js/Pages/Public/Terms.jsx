import PublicLayout from '@/Layouts/PublicLayout';

function Section({ title, children }) {
    return (
        <section className="rounded-2xl border border-slate-200/70 bg-white/80 backdrop-blur p-6 shadow-lg shadow-slate-900/5">
            <h2 className="text-base font-semibold text-slate-900">{title}</h2>
            <div className="mt-2 text-sm text-slate-600 leading-relaxed">{children}</div>
        </section>
    );
}

export default function Terms() {
    return (
        <PublicLayout
            title="Terms of Service"
            description="Terms of service for Crewly. Review demo usage, account access, and service disclaimers."
            image="/storage-images/crewly_logo.png"
        >
            <div className="mx-auto max-w-4xl px-4 py-16 sm:px-6">
                <h1 className="text-3xl font-semibold tracking-tight text-slate-900">Terms of Service</h1>
                <p className="mt-2 text-sm text-slate-600">Placeholder terms — to be finalized. Last updated: Feb 23, 2026.</p>

                <div className="mt-8 space-y-6">
                    <Section title="Use of service">
                        You agree to use Crewly for lawful HR and operations documentation within your organization.
                    </Section>
                    <Section title="Accounts and access">
                        Access may be restricted based on roles and permissions. You are responsible for keeping your credentials secure.
                    </Section>
                    <Section title="Demo environment">
                        Demo access may be shared and reset periodically. Data entered in the demo is not guaranteed to persist.
                    </Section>
                    <Section title="Disclaimer">
                        The service is provided on an “as is” basis. Additional warranty and liability terms will be specified in the finalized agreement.
                    </Section>
                    <Section title="Contact">
                        For questions about these terms, contact the Crewly administrator for your organization.
                    </Section>
                </div>
            </div>
        </PublicLayout>
    );
}
