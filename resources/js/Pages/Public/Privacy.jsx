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
            image="/favicon.png"
        >
            <div className="mx-auto max-w-4xl px-4 py-16 sm:px-6">
                <h1 className="text-3xl font-semibold tracking-tight text-slate-900">Privacy Policy</h1>
                <p className="mt-2 text-sm text-slate-600">Last updated: Feb 25, 2026.</p>

                <div className="mt-8 space-y-6">
                    <Section title="1. Introduction">
                        <p>
                            This Privacy Policy (the “Policy”) explains how Crewly (“Crewly”, “we”, “us”, or “our”) collects, uses, discloses, and protects Personal Data in
                            connection with our HR management software-as-a-service platform (the “Service”) and our public website pages (the “Site”).
                        </p>
                        <p className="mt-3">
                            We are currently operating in the Philippines. This Policy is intended to align with the Philippine Data Privacy Act of 2012 (Republic Act No. 10173), its
                            Implementing Rules and Regulations, and relevant issuances of the National Privacy Commission (“NPC”).
                        </p>
                    </Section>

                    <Section title="2. Scope">
                        <p>This Policy applies to:</p>
                        <ul className="mt-3 list-disc pl-5 space-y-1">
                            <li>Individuals who submit demo requests or otherwise contact us through the Site;</li>
                            <li>Authorized users of the Service (such as client administrators and HR staff); and</li>
                            <li>Employee data and related HR records uploaded/entered into the Service by our client companies.</li>
                        </ul>
                        <p className="mt-3">
                            This Policy does not cover third-party websites or services that may be linked from the Site or the Service. Their privacy practices are governed by their own
                            policies.
                        </p>
                    </Section>

                    <Section title="3. Information We Collect">
                        <p className="font-medium text-slate-700">3.1 Demo request information</p>
                        <p className="mt-2">When you request a demo, we may collect:</p>
                        <ul className="mt-3 list-disc pl-5 space-y-1">
                            <li>Name</li>
                            <li>Company name</li>
                            <li>Email address</li>
                            <li>Phone number (if provided)</li>
                        </ul>

                        <p className="mt-4 font-medium text-slate-700">3.2 Account and organization information</p>
                        <p className="mt-2">When a client account is created and used, we may process:</p>
                        <ul className="mt-3 list-disc pl-5 space-y-1">
                            <li>Organization details (e.g., company name and configuration details)</li>
                            <li>User account details (e.g., name, email, role/permissions)</li>
                            <li>Usage and audit information necessary to operate and secure the Service (e.g., access logs and activity history within the Service)</li>
                        </ul>

                        <p className="mt-4 font-medium text-slate-700">3.3 Employee information entered by client companies</p>
                        <p className="mt-2">
                            Our client companies may input and manage employee-related information in the Service. This may include Personal Data such as employee identifiers and
                            employment records, depending on what the client chooses to store in Crewly and how they configure their account.
                        </p>
                        <p className="mt-3">
                            Important: When we process employee information on behalf of a client company, the client company determines what data is collected and for what purposes.
                            Please refer to your employer’s or organization’s privacy notices for details about their processing.
                        </p>
                    </Section>

                    <Section title="4. Legal Basis for Processing">
                        <p>We process Personal Data based on one or more of the following legal bases, as applicable:</p>
                        <ul className="mt-3 list-disc pl-5 space-y-2">
                            <li>
                                <span className="font-medium text-slate-700">Consent</span> — for example, when you voluntarily submit your details through a demo request form.
                            </li>
                            <li>
                                <span className="font-medium text-slate-700">Contract</span> — to enter into and perform our contract with client companies, including providing and
                                supporting the Service.
                            </li>
                            <li>
                                <span className="font-medium text-slate-700">Legitimate Interests</span> — to operate, maintain, and secure the Service, prevent fraud/abuse, and
                                improve product functionality, provided that such interests are not overridden by your rights under applicable law.
                            </li>
                            <li>
                                <span className="font-medium text-slate-700">Legal Obligation</span> — where processing is necessary to comply with applicable laws, regulations, lawful
                                orders, or requests from government authorities.
                            </li>
                        </ul>
                    </Section>

                    <Section title="5. Data Controller and Data Processor Clarification">
                        <p>
                            Crewly is an HR management SaaS platform. In most cases involving employee and HR data inside the Service, Crewly acts as a <span className="font-medium text-slate-700">Data Processor</span>,
                            while our client companies act as the <span className="font-medium text-slate-700">Data Controllers</span>.
                        </p>
                        <p className="mt-3">
                            <span className="font-medium text-slate-700">Client companies (Data Controllers)</span> decide what Personal Data is collected about employees and applicants, why it is processed,
                            and how long it is retained. <span className="font-medium text-slate-700">Crewly (Data Processor)</span> processes that data on the client company’s documented instructions and in accordance
                            with our agreements, subject to applicable law.
                        </p>
                        <p className="mt-3">
                            For limited contexts such as demo request inquiries submitted through the Site, Crewly may process information directly to respond to your request.
                        </p>
                    </Section>

                    <Section title="6. How We Use Information">
                        <p>We may use Personal Data for the following purposes:</p>
                        <ul className="mt-3 list-disc pl-5 space-y-1">
                            <li>To respond to demo requests and communicate with prospective customers;</li>
                            <li>To create, administer, and authenticate user accounts and organization workspaces;</li>
                            <li>To provide, maintain, support, and improve the Service (including troubleshooting and customer support);</li>
                            <li>To secure the Service, enforce access controls, and monitor for suspicious activity;</li>
                            <li>To send service-related notices (e.g., security or operational updates); and</li>
                            <li>To comply with legal obligations and protect our rights and interests.</li>
                        </ul>
                        <p className="mt-3">
                            We do not sell Personal Data.
                        </p>
                    </Section>

                    <Section title="7. Data Sharing and Third-Party Providers">
                        <p>
                            We may share Personal Data only as necessary to provide the Service and operate our business, including with:
                        </p>
                        <ul className="mt-3 list-disc pl-5 space-y-2">
                            <li>
                                <span className="font-medium text-slate-700">Service providers</span> — such as hosting, infrastructure, and email delivery providers that help us operate
                                the Service and communicate with users.
                            </li>
                            <li>
                                <span className="font-medium text-slate-700">Professional advisers</span> — such as lawyers, accountants, auditors, and consultants, subject to
                                confidentiality obligations.
                            </li>
                            <li>
                                <span className="font-medium text-slate-700">Legal and regulatory disclosures</span> — when required by law, court order, subpoena, or lawful request.
                            </li>
                        </ul>
                        <p className="mt-3">
                            Where we engage third-party providers to process Personal Data on our behalf, we take steps to require appropriate safeguards and confidentiality consistent
                            with applicable law and our contractual obligations.
                        </p>
                        <p className="mt-3">
                            Depending on the locations of our service providers, Personal Data may be stored or processed in the Philippines and/or other jurisdictions. Where applicable,
                            we implement contractual and organizational safeguards intended to provide a comparable level of protection.
                        </p>
                    </Section>

                    <Section title="8. Data Retention Policy">
                        <p className="font-medium text-slate-700">8.1 Demo leads retention</p>
                        <p className="mt-2">
                            Demo request information is generally retained for up to <span className="font-medium text-slate-700">24 months</span> from the last meaningful interaction, unless:
                        </p>
                        <ul className="mt-3 list-disc pl-5 space-y-1">
                            <li>you request earlier deletion (where applicable);</li>
                            <li>we are required or permitted to retain it for a longer period to comply with legal obligations or to establish, exercise, or defend legal claims; or</li>
                            <li>it becomes part of a client account record (e.g., if you proceed to subscribe to the Service).</li>
                        </ul>

                        <p className="mt-4 font-medium text-slate-700">8.2 Client account and employee data retention</p>
                        <p className="mt-2">
                            Client account data (including employee data and HR records processed within the Service) is retained for the duration of the client’s subscription/contract.
                            Upon account termination or expiry, we will make the data available for export for a limited period and then delete or anonymize it in accordance with our
                            contractual commitments and applicable law.
                        </p>
                        <p className="mt-3">
                            Unless otherwise agreed with the client company in writing, we generally delete or anonymize client account data within <span className="font-medium text-slate-700">90 days</span> after termination.
                            Backups, if any, may persist for a limited additional period (typically up to <span className="font-medium text-slate-700">30 days</span>) before being overwritten in the ordinary course.
                        </p>
                    </Section>

                    <Section title="9. Security Measures">
                        <p>We implement reasonable organizational, physical, and technical measures designed to protect Personal Data, including:</p>
                        <ul className="mt-3 list-disc pl-5 space-y-2">
                            <li>
                                <span className="font-medium text-slate-700">Secure transmission (HTTPS/SSL)</span> — the Site and Service use HTTPS to help protect data in transit.
                            </li>
                            <li>
                                <span className="font-medium text-slate-700">Encryption at rest</span> — sensitive employee data stored in the Service is encrypted at rest.
                            </li>
                            <li>
                                <span className="font-medium text-slate-700">Encrypted file storage</span> — uploaded files are encrypted.
                            </li>
                            <li>
                                <span className="font-medium text-slate-700">Password protection</span> — passwords are stored using one-way hashing.
                            </li>
                            <li>
                                <span className="font-medium text-slate-700">Role-based access control</span> — access to data and features is restricted based on user roles and permissions.
                            </li>
                            <li>
                                <span className="font-medium text-slate-700">Restricted database access</span> — access to production databases is restricted.
                            </li>
                        </ul>
                        <p className="mt-3">
                            No method of transmission over the internet or electronic storage is completely secure. We cannot guarantee absolute security, but we work to continuously
                            improve our safeguards.
                        </p>
                    </Section>

                    <Section title="10. Limited Access to Client Data">
                        <p>
                            We limit access to client data to authorized personnel and only when necessary for legitimate purposes such as technical support, service maintenance,
                            security, troubleshooting, or legal and regulatory compliance.
                        </p>
                        <p className="mt-3">
                            Where feasible and appropriate, access is controlled through role-based permissions and internal procedures designed to minimize access and reduce the risk of
                            unauthorized disclosure.
                        </p>
                    </Section>

                    <Section title="11. Data Subject Rights under Philippine Law">
                        <p>Subject to the Data Privacy Act and applicable NPC rules, Data Subjects may have rights including:</p>
                        <ul className="mt-3 list-disc pl-5 space-y-1">
                            <li>The right to be informed;</li>
                            <li>The right to object to processing;</li>
                            <li>The right to access;</li>
                            <li>The right to rectify/correct;</li>
                            <li>The right to erasure or blocking (in appropriate cases);</li>
                            <li>The right to data portability (where applicable); and</li>
                            <li>The right to lodge a complaint with the National Privacy Commission.</li>
                        </ul>
                        <p className="mt-3">
                            If your Personal Data is processed in the Service by your employer or organization (a client company), please direct rights requests to your employer or
                            organization as the Data Controller. Where appropriate, we will assist our client companies in responding to verified requests consistent with our role as Data
                            Processor and our contractual obligations.
                        </p>
                    </Section>

                    <Section title="12. Beta Disclaimer">
                        <p>
                            Crewly is currently in Beta. Features, workflows, and data fields may change, and we may add, modify, or remove functionality. We will continue to take
                            reasonable steps to protect Personal Data during Beta and will update this Policy as needed.
                        </p>
                        <p className="mt-3">
                            Payroll processing is not currently part of the Service. If payroll-related functionality is introduced in the future, we will update our documentation and, where
                            required, our agreements and notices.
                        </p>
                    </Section>

                    <Section title="13. Limitation of Liability">
                        <p>
                            Client companies control what employee data is entered into Crewly. Each client company represents and warrants that it has a valid legal basis to collect,
                            use, and disclose Personal Data to Crewly for processing, and that the data it provides is accurate and obtained in compliance with applicable laws and
                            policies.
                        </p>
                        <p className="mt-3">
                            To the extent permitted by law, Crewly is not responsible for the legality, accuracy, or completeness of Personal Data and content submitted by client
                            companies or their users. This section does not limit any liability that cannot be excluded under applicable law.
                        </p>
                    </Section>

                    <Section title="14. Changes to This Policy">
                        <p>
                            We may update this Policy from time to time. When we do, we will revise the “Last updated” date at the top of this page. If changes are material, we may
                            provide additional notice through the Site or Service.
                        </p>
                    </Section>

                    <Section title="15. Contact Information">
                        <p>
                            For questions about this Policy, or to submit a privacy-related inquiry regarding demo request information, you may contact Crewly through our demo request
                            page.
                        </p>
                        <p className="mt-3">
                            Demo request page: <a className="font-medium text-slate-700 underline hover:text-slate-900" href={route('public.demo')}>{route('public.demo')}</a>
                        </p>
                        <p className="mt-3">
                            If your concern relates to employee data processed within a client account, please contact your employer or organization (the client company) as the Data
                            Controller.
                        </p>
                    </Section>
                </div>
            </div>
        </PublicLayout>
    );
}
