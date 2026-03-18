import PublicLayout from '@/Layouts/PublicLayout';
import { Link, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

function Icon({ path, className = 'h-5 w-5' }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className={className}>
            <path strokeLinecap="round" strokeLinejoin="round" d={path} />
        </svg>
    );
}

const ICONS = {
    document: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
    users: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
    calendar: 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
    chart: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
    shield: 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
    clipboard: 'M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75',
    briefcase: 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z',
};

const TRUST_ITEMS = [
    { icon: ICONS.users, label: 'Built for PH SMEs' },
    { icon: ICONS.shield, label: 'Audit-ready records' },
    { icon: ICONS.calendar, label: 'Attendance & payroll workflows' },
    { icon: ICONS.briefcase, label: 'Founder access available' },
];

const FEATURES = [
    {
        icon: ICONS.clipboard,
        title: 'Structured HR Case Workflow',
        body: 'Standardize incident handling from report to follow-up, resolution, and documentation — every step in one place.',
    },
    {
        icon: ICONS.document,
        title: 'Built-In Memo Templates',
        body: 'Generate consistent NTEs, warnings, and notices with pre-built templates and smart placeholders.',
    },
    {
        icon: ICONS.users,
        title: 'Centralized Employee Records',
        body: 'Keep employee profiles, 201 files, documents, and history in one searchable, organized workspace.',
    },
    {
        icon: ICONS.calendar,
        title: 'Attendance & Leave Tracking',
        body: 'Track daily attendance, absences, and leave requests in one clear view — payroll-ready from day one.',
    },
    {
        icon: ICONS.chart,
        title: 'Payroll Overview',
        body: 'Review payroll summaries and deductions with government contributions (SSS, PhilHealth, Pag-IBIG) pre-computed.',
    },
    {
        icon: ICONS.briefcase,
        title: 'Hiring Pipeline',
        body: 'Move candidates through recruitment stages and keep your hiring process organized and documented.',
    },
];

function FeatureCard({ icon, title, body }) {
    return (
        <div className="flex gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/4">
            <div className="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                <Icon path={icon} className="h-5 w-5" />
            </div>
            <div>
                <div className="text-sm font-semibold text-slate-900">{title}</div>
                <div className="mt-1.5 text-sm text-slate-600 leading-relaxed">{body}</div>
            </div>
        </div>
    );
}

function ShowcaseSection({ eyebrow, title, body, src, alt, imageLeft, onZoom }) {
    const imageBlock = (
        <div
            className="group relative cursor-zoom-in overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/8"
            onClick={() => onZoom?.({ src, alt })}
            role="button"
            tabIndex={0}
            onKeyDown={(e) => e.key === 'Enter' && onZoom?.({ src, alt })}
            aria-label={`Open ${alt}`}
        >
            <img
                src={src}
                alt={alt}
                className="h-full w-full object-cover object-top transition-transform duration-300 group-hover:scale-[1.02]"
                loading="lazy"
            />
        </div>
    );

    const textBlock = (
        <div className="flex flex-col justify-center">
            <div className="text-xs font-semibold uppercase tracking-widest text-amber-600">{eyebrow}</div>
            <h2 className="mt-3 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl lg:leading-snug">{title}</h2>
            <p className="mt-4 text-base text-slate-600 leading-relaxed">{body}</p>
        </div>
    );

    return (
        <div className="grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
            {imageLeft ? (
                <>
                    {imageBlock}
                    {textBlock}
                </>
            ) : (
                <>
                    {textBlock}
                    {imageBlock}
                </>
            )}
        </div>
    );
}

export default function Landing() {
    const props = usePage().props;
    const sharedDemo = props.shared_demo ?? {};
    const demoEnabled = Boolean(sharedDemo?.enabled);
    const demoEmail = useMemo(() => String(sharedDemo?.email || '').trim(), [sharedDemo?.email]);

    const demoLoginHref = useMemo(() => {
        if (!demoEnabled || !demoEmail) return route('login');
        return route('public.demo.login');
    }, [demoEnabled, demoEmail]);

    const imageUrl = (filename) => `/storage-images/${encodeURIComponent(filename)}`;
    const [zoom, setZoom] = useState(null);

    return (
        <PublicLayout
            title="Crewly"
            description="Structured HR case workflow, built-in memo templates, centralized employee records, attendance & payroll overview, and hiring pipeline management."
            image={imageUrl('product_preview.PNG')}
        >
            {/* ─── Hero ─────────────────────────────────────────────── */}
            <section className="relative overflow-hidden bg-white">
                <div className="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                    <div className="absolute -top-40 -right-40 h-[30rem] w-[30rem] rounded-full bg-amber-50 opacity-70 blur-3xl" />
                    <div className="absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-slate-100 opacity-70 blur-2xl" />
                </div>

                <div className="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
                    <div className="grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
                        {/* Left: text */}
                        <div>
                            <div className="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3.5 py-1.5 text-xs font-semibold text-amber-700">
                                <span className="h-1.5 w-1.5 flex-none rounded-full bg-amber-500" />
                                Built for Philippine SMEs
                            </div>

                            <h1 className="mt-5 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-[3.25rem] lg:leading-[1.15]">
                                HR Documentation,{' '}
                                <span className="text-amber-600">Done Right.</span>
                            </h1>

                            <p className="mt-5 max-w-lg text-lg text-slate-600 leading-relaxed">
                                Generate memos, track incidents, and keep employee files audit-ready — without messy folders, spreadsheets, or scattered files.
                            </p>

                            <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                                <Link
                                    href={route('pricing.index')}
                                    className="inline-flex items-center justify-center rounded-xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-md shadow-amber-600/20 transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                                >
                                    Request Founder Access
                                </Link>
                                <Link
                                    href={demoLoginHref}
                                    className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                                >
                                    Try Demo
                                </Link>
                            </div>
                        </div>

                        {/* Right: product mockup */}
                        <div className="relative">
                            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10">
                                <button
                                    type="button"
                                    onClick={() => setZoom({ src: imageUrl('product_preview.PNG'), alt: 'Product preview' })}
                                    className="block w-full"
                                    aria-label="Open product preview"
                                >
                                    <div className="aspect-[16/10]">
                                        <img
                                            src={imageUrl('product_preview.PNG')}
                                            alt="Crewly product preview"
                                            className="h-full w-full cursor-zoom-in object-cover object-top transition-transform duration-300 hover:scale-[1.01]"
                                            loading="eager"
                                        />
                                    </div>
                                </button>
                            </div>

                            {/* Floating mini-cards */}
                            <div className="absolute -bottom-5 -left-5 hidden rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-lg lg:block">
                                <div className="flex items-center gap-2.5">
                                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                        <Icon path={ICONS.shield} className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div className="text-xs font-semibold text-slate-900">Audit-ready</div>
                                        <div className="text-xs text-slate-500">Incident records secured</div>
                                    </div>
                                </div>
                            </div>

                            <div className="absolute -right-5 top-8 hidden rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-lg lg:block">
                                <div className="flex items-center gap-2.5">
                                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                        <Icon path={ICONS.users} className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div className="text-xs font-semibold text-slate-900">201 Files</div>
                                        <div className="text-xs text-slate-500">Centralized & searchable</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* ─── Trust Strip ──────────────────────────────────────── */}
            <section className="border-y border-slate-200 bg-slate-50">
                <div className="mx-auto max-w-7xl px-4 py-5 sm:px-6">
                    <div className="flex flex-wrap items-center justify-center gap-x-10 gap-y-3">
                        {TRUST_ITEMS.map(({ icon, label }) => (
                            <div key={label} className="flex items-center gap-2 text-sm font-medium text-slate-600">
                                <Icon path={icon} className="h-4 w-4 shrink-0 text-amber-600" />
                                <span>{label}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ─── Features ─────────────────────────────────────────── */}
            <section className="mx-auto max-w-7xl px-4 py-20 sm:px-6">
                <div className="mx-auto max-w-2xl text-center">
                    <div className="text-xs font-semibold uppercase tracking-widest text-amber-600">Core features</div>
                    <h2 className="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Everything your HR team needs</h2>
                    <p className="mt-3 text-base text-slate-600">Core workflows that operations-heavy teams use every week.</p>
                </div>

                <div className="mt-12 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                    {FEATURES.map((f) => (
                        <FeatureCard key={f.title} icon={f.icon} title={f.title} body={f.body} />
                    ))}
                </div>
            </section>

            {/* ─── Product Showcases ────────────────────────────────── */}
            <section className="border-t border-slate-200 bg-slate-50/60">
                <div className="mx-auto max-w-7xl space-y-24 px-4 py-20 sm:px-6">
                    <ShowcaseSection
                        eyebrow="Employee Records"
                        title="Centralized 201 Files, Always Accessible"
                        body="Store every employee's documents, history, and compensation details in one place. No more scattered folders or missing files when you need them most."
                        src={imageUrl('employee_profile.PNG')}
                        alt="Employee profile screenshot"
                        imageLeft={false}
                        onZoom={setZoom}
                    />

                    <ShowcaseSection
                        eyebrow="Incident Workflow"
                        title="From Report to Resolution, Fully Documented"
                        body="Handle HR cases — NTEs, investigations, sanctions — through a structured workflow that keeps every memo and decision on record and retrievable during audits."
                        src={imageUrl('product_preview.PNG')}
                        alt="Incident workflow screenshot"
                        imageLeft
                        onZoom={setZoom}
                    />

                    <ShowcaseSection
                        eyebrow="Attendance & Payroll"
                        title="Attendance Data That's Payroll-Ready"
                        body="Track employee attendance, leaves, and overtime. Payroll summaries are automatically connected to attendance records and government contribution computations."
                        src={imageUrl('attendance_summary.PNG')}
                        alt="Attendance summary screenshot"
                        imageLeft={false}
                        onZoom={setZoom}
                    />

                    <ShowcaseSection
                        eyebrow="Recruitment Pipeline"
                        title="Move Candidates Through Your Hiring Process"
                        body="Keep your hiring organized with a structured pipeline. Track applicants from application to onboarding, with documentation attached at every stage."
                        src={imageUrl('recruitment_pipeline.PNG')}
                        alt="Recruitment pipeline screenshot"
                        imageLeft
                        onZoom={setZoom}
                    />
                </div>
            </section>

            {/* ─── Final CTA ───────────────────────────────────────── */}
            <section className="border-t border-slate-200 bg-white">
                <div className="mx-auto max-w-4xl px-4 py-20 sm:px-6 text-center">
                    <div className="text-xs font-semibold uppercase tracking-widest text-amber-600">Get started</div>
                    <h2 className="mt-3 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                        Ready to structure your HR process?
                    </h2>
                    <p className="mx-auto mt-4 max-w-xl text-base text-slate-600 leading-relaxed">
                        Founder access is available for a limited number of early partners. Join now and keep your pricing as the product grows.
                    </p>
                    <div className="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                        <Link
                            href={route('pricing.index')}
                            className="inline-flex items-center justify-center rounded-xl bg-amber-600 px-7 py-3.5 text-sm font-semibold text-white shadow-md shadow-amber-600/20 transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                        >
                            Request Founder Access
                        </Link>
                        <Link
                            href={demoLoginHref}
                            className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-7 py-3.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                        >
                            Try Demo
                        </Link>
                    </div>
                </div>
            </section>

            {/* ─── Zoom Lightbox ───────────────────────────────────── */}
            {!!zoom && (
                <div
                    className="fixed inset-0 z-50 bg-slate-900/70 p-4 sm:p-8"
                    onClick={() => setZoom(null)}
                >
                    <div
                        className="mx-auto flex h-full max-w-6xl items-center justify-center"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="relative w-full">
                            <button
                                type="button"
                                onClick={() => setZoom(null)}
                                className="absolute right-3 top-3 z-10 inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                            >
                                Close
                            </button>
                            <img
                                src={zoom.src}
                                alt={zoom.alt || 'Zoomed image'}
                                className="mx-auto max-h-[85vh] w-full rounded-2xl border border-slate-200 bg-white object-contain shadow-2xl"
                                loading="eager"
                            />
                        </div>
                    </div>
                </div>
            )}
        </PublicLayout>
    );
}
