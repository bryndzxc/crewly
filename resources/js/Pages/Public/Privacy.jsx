import PublicLayout from '@/Layouts/PublicLayout';

function Section({ title, children }) {
    return (
        <section className="rounded-2xl border border-slate-200/70 bg-white/80 backdrop-blur p-6 shadow-lg shadow-slate-900/5">
            <h2 className="text-base font-semibold text-slate-900">{title}</h2>
            <div className="mt-2 text-sm text-slate-600 leading-relaxed">{children}</div>
        </section>
    );
}

export default function Privacy() {
    return (
        <PublicLayout
            title="Privacy Policy"
            description="Privacy policy for Crewly. Learn what data we collect via demo requests and how it’s used."
            image="/storage-images/crewly_logo.png"
        >
            <div className="mx-auto max-w-4xl px-4 py-16 sm:px-6">
                <h1 className="text-3xl font-semibold tracking-tight text-slate-900">Privacy Policy</h1>
                <p className="mt-2 text-sm text-slate-600">Placeholder policy — to be finalized. Last updated: Feb 23, 2026.</p>

                <div className="mt-8 space-y-6">
                    <Section title="Overview">
                        We collect basic contact details submitted via the demo request form (name, company, email, optional phone/message) so we can respond and schedule demos.
                    </Section>
                    <Section title="What we collect">
                        Contact information, company information, and any details you include in your message.
                    </Section>
                    <Section title="How we use information">
                        To contact you about the demo request, onboarding, and product updates relevant to your inquiry.
                    </Section>
                    <Section title="Retention">
                        Lead submissions may be retained for business follow-up and record-keeping. A retention policy will be documented in a future revision.
                    </Section>
                    <Section title="Contact">
                        For privacy questions, contact the Crewly administrator for your organization.
                    </Section>
                </div>
            </div>
        </PublicLayout>
    );
}
