import PublicLayout from '@/Layouts/PublicLayout';
import LeadForm from '@/Components/Public/LeadForm';
import { Link } from '@inertiajs/react';

function FeatureCard({ title, body }) {
    return (
        <div className="rounded-2xl border border-slate-200/70 bg-white/80 backdrop-blur p-6 shadow-lg shadow-slate-900/5">
            <div className="text-base font-semibold text-slate-900">{title}</div>
            <div className="mt-1 text-sm text-slate-600">{body}</div>
        </div>
    );
}

function ScreenshotCard({ title, src, alt }) {
    return (
        <div className="rounded-2xl border border-slate-200/70 bg-white/70 p-6 shadow-lg shadow-slate-900/5">
            <div className="text-sm font-semibold text-slate-900">{title}</div>
            <div className="group mt-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                <div className="aspect-[16/10] w-full">
                    <img
                        src={src}
                        alt={alt || title}
                        className="h-full w-full object-contain object-top transition-transform duration-200 ease-out group-hover:scale-[1.02]"
                        loading="lazy"
                    />
                </div>
            </div>
        </div>
    );
}

function FaqItem({ q, a }) {
    return (
        <div className="rounded-2xl border border-slate-200/70 bg-white/80 backdrop-blur p-6 shadow-lg shadow-slate-900/5">
            <div className="text-sm font-semibold text-slate-900">{q}</div>
            <div className="mt-1 text-sm text-slate-600">{a}</div>
        </div>
    );
}

export default function Landing() {
    const imageUrl = (filename) => `/storage-images/${encodeURIComponent(filename)}`;

    return (
        <PublicLayout
            title="Crewly"
            description="Structured HR case workflow, built-in memo templates, centralized employee records, attendance & payroll overview, and hiring pipeline management."
            image={imageUrl('product_preview.PNG')}
        >
            <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6">
                <div className="grid grid-cols-1 gap-10 lg:grid-cols-2 lg:items-center">
                    <div>
                        <div className="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                            Built for PH SMEs
                        </div>
                        <h1 className="mt-4 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">
                            Stop Losing HR Documents and Incident Records.
                        </h1>
                        <p className="mt-4 text-base text-slate-600 leading-relaxed">
                            Generate memos, track incidents, and keep employee files audit-ready — without messy folders, spreadsheets, or scattered files.
                        </p>

                        <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <a
                                href="#request-demo"
                                className="inline-flex items-center justify-center rounded-xl bg-amber-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                            >
                                Book a Demo
                            </a>
                            <Link
                                href={route('login')}
                                className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                            >
                                Try the Demo Environment
                            </Link>
                            <Link href={route('public.demo')} className="text-sm font-semibold text-amber-800 hover:text-amber-900">
                                Or learn about the demo →
                            </Link>
                        </div>
                    </div>

                    <div className="rounded-3xl border border-slate-200/70 bg-white/70 p-6 shadow-xl shadow-slate-900/5">
                        <div className="text-sm font-semibold text-slate-900">What you get</div>
                        <ul className="mt-3 space-y-2 text-sm text-slate-700">
                            <li className="flex gap-2"><span className="text-amber-700">•</span>Structured HR Case Workflow</li>
                            <li className="flex gap-2"><span className="text-amber-700">•</span>Built-In Memo Templates</li>
                            <li className="flex gap-2"><span className="text-amber-700">•</span>Centralized Employee Records</li>
                            <li className="flex gap-2"><span className="text-amber-700">•</span>Attendance &amp; Payroll Overview</li>
                            <li className="flex gap-2"><span className="text-amber-700">•</span>Hiring Pipeline Management</li>
                        </ul>
                        <div className="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                            <div className="aspect-[16/10] w-full sm:aspect-[16/9]">
                                <img
                                    src={imageUrl('product_preview.PNG')}
                                    alt="Product preview"
                                    className="h-full w-full object-contain object-top transition-transform duration-200 ease-out hover:scale-[1.02]"
                                    loading="lazy"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
                <div className="mb-6">
                    <h2 className="text-2xl font-semibold tracking-tight text-slate-900">Features</h2>
                    <p className="mt-1 text-sm text-slate-600">Core workflows teams use every week.</p>
                </div>
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <FeatureCard
                        title="Structured HR Case Workflow"
                        body="Standardize incident handling from report to follow-up and documentation."
                    />
                    <FeatureCard
                        title="Built-In Memo Templates"
                        body="Generate consistent memos (NTEs, warnings, notices) with placeholders."
                    />
                    <FeatureCard
                        title="Centralized Employee Records"
                        body="Keep employee profiles, documents, and history in one place."
                    />
                    <FeatureCard
                        title="Attendance & Payroll Overview"
                        body="Track attendance and review payroll summaries quickly."
                    />
                    <FeatureCard
                        title="Hiring Pipeline Management"
                        body="Move candidates through stages and keep hiring organized."
                    />
                </div>
            </section>

            <section className="border-y border-slate-200/60 bg-slate-50/60">
                <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:items-start">
                        <div>
                            <h2 className="text-2xl font-semibold tracking-tight text-slate-900">Is your HR process still manual?</h2>
                            <ul className="mt-4 space-y-2 text-sm text-slate-700">
                                <li className="flex gap-2"><span className="text-amber-700">•</span>Creating NTEs and memos manually in Word</li>
                                <li className="flex gap-2"><span className="text-amber-700">•</span>Storing employee documents in scattered folders</li>
                                <li className="flex gap-2"><span className="text-amber-700">•</span>Tracking incidents in Excel sheets</li>
                                <li className="flex gap-2"><span className="text-amber-700">•</span>Struggling to retrieve documents during audits</li>
                                <li className="flex gap-2"><span className="text-amber-700">•</span>Losing follow-ups on HR cases</li>
                            </ul>
                            <p className="mt-5 text-sm font-semibold text-slate-900">
                                Crewly centralizes HR case documentation into one structured, searchable system.
                            </p>
                        </div>

                        <div className="rounded-2xl border border-slate-200/70 bg-white/80 backdrop-blur p-6 shadow-lg shadow-slate-900/5">
                            <p className="text-sm text-slate-600 leading-relaxed">
                                Crewly replaces scattered files with a consistent workflow for memos, incidents, and employee records — so your team can document faster, stay organized, and pull what you need when audits or investigations come up.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
                <div className="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:items-start">
                    <div>
                        <h2 className="text-2xl font-semibold tracking-tight text-slate-900">Who it’s for</h2>
                        <p className="mt-2 text-sm text-slate-600 leading-relaxed">
                            Crewly is designed for operations-heavy teams where HR documentation, incident handling, and employee records must stay organized and audit-ready.
                        </p>
                        <div className="mt-4 flex flex-wrap gap-2">
                            {['Logistics', 'Warehouses', 'Construction', 'SMEs'].map((label) => (
                                <span key={label} className="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white">
                                    {label}
                                </span>
                            ))}
                        </div>
                        <div className="mt-3 text-sm text-slate-600">
                            Best suited for teams with 20–150 employees still managing HR workflows manually.
                        </div>
                    </div>
                    <div className="rounded-2xl border border-slate-200/70 bg-white/80 backdrop-blur p-6 shadow-lg shadow-slate-900/5">
                        <div className="text-sm font-semibold text-slate-900">Typical outcomes</div>
                        <ul className="mt-3 space-y-2 text-sm text-slate-700">
                            <li className="flex gap-2"><span className="text-amber-700">•</span>Fewer missing documents and scattered files</li>
                            <li className="flex gap-2"><span className="text-amber-700">•</span>Clearer incident timelines and follow-ups</li>
                            <li className="flex gap-2"><span className="text-amber-700">•</span>Better readiness for audits and investigations</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
                <div className="mb-6">
                    <h2 className="text-2xl font-semibold tracking-tight text-slate-900">Screenshots</h2>
                    <p className="mt-1 text-sm text-slate-600"></p>
                </div>
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <ScreenshotCard title="Employee profile" src={imageUrl('employee_profile.PNG')} alt="Employee profile screenshot" />
                    <ScreenshotCard title="Attendance summary" src={imageUrl('attendance_summary.PNG')} alt="Attendance summary screenshot" />
                    <ScreenshotCard title="Recruitment pipeline" src={imageUrl('recruitment_pipeline.PNG')} alt="Recruitment pipeline screenshot" />
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
                <div className="mb-6">
                    <h2 className="text-2xl font-semibold tracking-tight text-slate-900">FAQ</h2>
                    <p className="mt-1 text-sm text-slate-600">Common questions from HR and ops teams.</p>
                </div>
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <FaqItem q="Do you support multi-branch companies?" a="Multi-branch support is on our roadmap. Currently, each company operates within a single structured workspace." />
                    <FaqItem q="Is there a demo environment?" a="Yes. Demo access is shared and resets periodically." />
                    <FaqItem q="Can we migrate existing employee docs?" a="Yes — we can guide you on importing documents during onboarding." />
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
                <div className="mb-6">
                    <h2 className="text-2xl font-semibold tracking-tight text-slate-900">Why Teams Choose Crewly</h2>
                </div>
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <FeatureCard
                        title="Structured Workflow"
                        body="Standardize HR cases from report to resolution."
                    />
                    <FeatureCard
                        title="Audit-Ready Documentation"
                        body="Keep memos, attachments, and incident timelines organized and searchable."
                    />
                    <FeatureCard
                        title="Simple & Focused"
                        body="No bloated features — just what SMEs actually use."
                    />
                </div>
            </section>

            <section id="request-demo" className="mx-auto max-w-7xl px-4 py-14 sm:px-6">
                <div className="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:items-start">
                    <div>
                        <h2 className="text-2xl font-semibold tracking-tight text-slate-900">Request a demo</h2>
                        <p className="mt-2 text-sm text-slate-600 leading-relaxed">
                            We’ll walk you through your workflows — HR cases, memo templates, employee records, attendance &amp; payroll, and hiring pipeline — and answer questions about fit for your team.
                        </p>
                        <div className="mt-4 text-sm text-slate-600">
                            Prefer details first? Visit <Link href={route('public.demo')} className="font-semibold text-amber-800 hover:text-amber-900">/demo</Link>.
                        </div>
                    </div>
                    <LeadForm sourcePage="/" />
                </div>
            </section>
        </PublicLayout>
    );
}
