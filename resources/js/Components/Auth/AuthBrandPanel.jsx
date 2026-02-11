import React from 'react';

function Feature({ icon, children }) {
    return (
        <li className="flex items-start gap-3 text-white/95">
            <span className="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/15 ring-1 ring-white/20">
                {icon}
            </span>
            <span className="text-sm leading-6">{children}</span>
        </li>
    );
}

export default function AuthBrandPanel() {
    return (
        <div className="relative h-full w-full overflow-hidden bg-gradient-to-br from-amber-500 via-yellow-400 to-orange-500">
            <div className="absolute inset-0">
                <div className="absolute inset-0 opacity-15 [background-image:radial-gradient(circle_at_1px_1px,rgba(255,255,255,0.55)_1px,transparent_0)] [background-size:28px_28px]" />
                <div className="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-white/60 blur-3xl" />
                <div className="absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-white/50 blur-3xl" />
                <div className="absolute left-10 top-1/2 h-56 w-56 -translate-y-1/2 rounded-full bg-amber-200/40 blur-3xl" />
                <div className="absolute inset-0 bg-gradient-to-b from-slate-900/10 via-slate-900/5 to-slate-900/20" />
            </div>

            <div className="relative flex h-full flex-col justify-center px-8 py-12 sm:px-12">
                <div className="max-w-md">
                    <div className="rounded-3xl bg-slate-900/20 ring-1 ring-white/15 backdrop-blur-sm px-6 py-7 shadow-2xl shadow-slate-900/10">
                        <div className="text-white">
                            <div className="text-3xl font-semibold tracking-tight drop-shadow-sm">Crewly</div>
                            <div className="mt-2 text-base text-white/95 drop-shadow-sm">People operations, simplified.</div>
                        </div>

                        <ul className="mt-8 space-y-4">
                            <Feature
                                icon={
                                    <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 7.5l-6.75 9L7.5 13.5" />
                                    </svg>
                                }
                            >
                                Clean, production-ready authentication
                            </Feature>
                            <Feature
                                icon={
                                    <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                }
                            >
                                HR modules can be added incrementally
                            </Feature>
                            <Feature
                                icon={
                                    <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M12 3l7 4v5c0 5-3 9-7 9s-7-4-7-9V7l7-4z"
                                        />
                                    </svg>
                                }
                            >
                                Role-ready structure (Admin / HR / Manager)
                            </Feature>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    );
}
