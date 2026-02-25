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
                <p className="mt-2 text-sm text-slate-600">Last updated: Feb 25, 2026.</p>

                <div className="mt-8 space-y-6">
                    <Section title="1. Acceptance of Terms">
                        <p>
                            These Terms of Service (the “Terms”) govern your access to and use of Crewly (the “Service”), a cloud-based HR management system operated for use in the
                            Philippines. By accessing or using the Service (including any trial or Beta access), you confirm that you have read, understood, and agree to be bound by these
                            Terms.
                        </p>
                        <p className="mt-3">
                            If you are using the Service on behalf of a company or other organization (a “Client Organization”), you represent that you have the authority to bind that
                            Client Organization to these Terms.
                        </p>
                    </Section>

                    <Section title="2. Description of Service">
                        <p>
                            Crewly is a cloud-based HR management platform that enables Client Organizations to manage HR-related information and workflows.
                        </p>
                        <ul className="mt-3 list-disc pl-5 space-y-1">
                            <li>The Service is provided online and may be updated from time to time.</li>
                            <li>Features, functionality, and data fields may change, including during Beta.</li>
                            <li>Subscription-based plans and paid features may be introduced in the future.</li>
                        </ul>
                    </Section>

                    <Section title="3. Beta Disclaimer">
                        <p>
                            The Service is currently in Beta. You acknowledge and agree that Beta software may be incomplete, contain errors, and be subject to change.
                        </p>
                        <ul className="mt-3 list-disc pl-5 space-y-1">
                            <li>The Service may be unstable and may experience interruptions.</li>
                            <li>Features may change, be removed, or be discontinued without notice.</li>
                            <li>We do not guarantee uptime, availability, or that data will be preserved or recoverable during Beta.</li>
                        </ul>
                    </Section>

                    <Section title="4. Account Registration and Responsibilities">
                        <p>
                            To use the Service, you may be required to create an account or be invited by a Client Organization.
                        </p>
                        <ul className="mt-3 list-disc pl-5 space-y-2">
                            <li>
                                <span className="font-medium text-slate-700">Account security</span> — you are responsible for maintaining the confidentiality of your credentials and for all
                                activities conducted through your account.
                            </li>
                            <li>
                                <span className="font-medium text-slate-700">Accurate information</span> — you agree to provide accurate and current information and to promptly update it as
                                needed.
                            </li>
                            <li>
                                <span className="font-medium text-slate-700">Authorized use</span> — you may use the Service only for and on behalf of the Client Organization that granted
                                you access.
                            </li>
                        </ul>
                    </Section>

                    <Section title="5. Acceptable Use">
                        <p>You agree not to, and not to allow any third party to:</p>
                        <ul className="mt-3 list-disc pl-5 space-y-2">
                            <li>Use the Service for any unlawful, harmful, or fraudulent purpose;</li>
                            <li>Attempt to access, view, alter, or interfere with data belonging to another organization or user;</li>
                            <li>Probe, scan, or test the vulnerability of the Service or bypass security or access controls;</li>
                            <li>Reverse engineer, decompile, or otherwise attempt to derive source code or underlying ideas from the Service, except to the extent prohibited by law;</li>
                            <li>Upload or transmit malware, malicious code, or content designed to disrupt or compromise the Service;</li>
                            <li>Use the Service to infringe intellectual property rights or violate privacy or confidentiality obligations.</li>
                        </ul>
                        <p className="mt-3">
                            We may suspend or terminate access for suspected misuse, security risks, or violations of these Terms.
                        </p>
                    </Section>

                    <Section title="6. Data Ownership">
                        <p>
                            Client Organizations retain all right, title, and interest in and to the employee data, HR records, and other content submitted to the Service (“Client Data”).
                            Crewly does not claim ownership over Client Data.
                        </p>
                    </Section>

                    <Section title="7. Data Processing and Security">
                        <p>
                            <span className="font-medium text-slate-700">Data roles.</span> Client Organizations are the Data Controllers of Client Data. Crewly acts as a Data Processor when
                            processing Client Data on behalf of Client Organizations and generally processes Client Data only in accordance with documented instructions and applicable
                            agreements, subject to law.
                        </p>
                        <p className="mt-3">
                            <span className="font-medium text-slate-700">Security.</span> We implement reasonable safeguards designed to protect Client Data, including:
                        </p>
                        <ul className="mt-3 list-disc pl-5 space-y-2">
                            <li>Encryption in transit using HTTPS/SSL;</li>
                            <li>Encryption at rest for sensitive data;</li>
                            <li>Encrypted storage for uploaded files;</li>
                            <li>Passwords stored using one-way hashing;</li>
                            <li>Role-based access control within the application; and</li>
                            <li>Restricted internal access and restricted access to production databases.</li>
                        </ul>
                        <p className="mt-3">
                            No system is completely secure. You are responsible for maintaining appropriate security measures on your side, including safeguarding user accounts and
                            ensuring that only authorized personnel are granted access.
                        </p>
                    </Section>

                    <Section title="8. Service Availability">
                        <p>
                            We do not guarantee that the Service will be uninterrupted, timely, secure, or error-free, especially during Beta. Maintenance, updates, and changes may occur
                            from time to time and may affect availability.
                        </p>
                    </Section>

                    <Section title="9. Termination">
                        <p className="font-medium text-slate-700">9.1 Termination by Crewly</p>
                        <p className="mt-2">
                            We may suspend or terminate your access (or a Client Organization’s access) immediately if we reasonably believe there has been a violation of these Terms,
                            unlawful use, a security incident, or conduct that risks harm to the Service, other users, or third parties.
                        </p>
                        <p className="mt-4 font-medium text-slate-700">9.2 Termination by Client Organizations</p>
                        <p className="mt-2">Client Organizations may stop using the Service and request termination at any time, subject to any applicable agreement.</p>
                        <p className="mt-4 font-medium text-slate-700">9.3 Data handling upon termination</p>
                        <p className="mt-2">
                            Upon termination, we will handle Client Data in accordance with our contractual commitments and applicable law, including providing a reasonable opportunity
                            for export where applicable and then deleting or anonymizing data within a commercially reasonable period.
                        </p>
                    </Section>

                    <Section title="10. Limitation of Liability">
                        <p>
                            To the extent permitted under Philippine law, the Service is provided on an “as is” and “as available” basis, without warranties of any kind, whether express,
                            implied, or statutory.
                        </p>
                        <p className="mt-3">
                            To the maximum extent permitted by law, Crewly will not be liable for any indirect, incidental, special, consequential, or punitive damages, or for any loss of
                            profits, revenues, data, goodwill, or business interruption, arising out of or related to your use of (or inability to use) the Service.
                        </p>
                        <p className="mt-3">
                            Where liability cannot be excluded, it will be limited to the extent permitted by applicable law.
                        </p>
                    </Section>

                    <Section title="11. Intellectual Property">
                        <p>
                            Crewly and its licensors retain all rights, title, and interest in and to the Service, including all related intellectual property rights. Except for the limited
                            right to access and use the Service in accordance with these Terms, no rights are granted to you.
                        </p>
                        <p className="mt-3">
                            Client Organizations retain ownership of Client Data.
                        </p>
                    </Section>

                    <Section title="12. Governing Law">
                        <p>
                            These Terms are governed by and construed in accordance with the laws of the Republic of the Philippines, without regard to its conflict of law principles.
                        </p>
                    </Section>

                    <Section title="13. Changes to Terms">
                        <p>
                            We may update these Terms from time to time. When we do, we will update the “Last updated” date at the top of this page. Continued use of the Service after
                            updated Terms take effect constitutes acceptance of the updated Terms.
                        </p>
                    </Section>

                    <Section title="14. Contact Information">
                        <p>
                            For questions about these Terms, you may contact Crewly through our demo request page.
                        </p>
                        <p className="mt-3">
                            Demo request page: <a className="font-medium text-slate-700 underline hover:text-slate-900" href={route('public.demo')}>{route('public.demo')}</a>
                        </p>
                    </Section>
                </div>
            </div>
        </PublicLayout>
    );
}
