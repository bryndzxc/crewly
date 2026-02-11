import React from 'react';

export default function PasswordVisibilityToggle({ shown, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="absolute inset-y-0 right-0 inline-flex items-center px-3 text-slate-500 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 rounded-md"
            aria-label={shown ? 'Hide password' : 'Show password'}
        >
            {shown ? (
                <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M3 3l18 18M10.477 10.477a3 3 0 104.243 4.243"
                    />
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M7.5 7.5C5.2 9.2 3.8 12 3.8 12s2.9 6.5 8.2 6.5c1.5 0 2.8-.3 3.9-.8M14.1 9.2c-.7-.4-1.5-.7-2.3-.7C6.7 8.5 3.8 12 3.8 12s.9 2.1 2.7 3.8m12.1-1.3C19.8 13.7 21 12 21 12s-2.9-6.5-9.2-6.5c-.5 0-1 .1-1.5.2"
                    />
                </svg>
            ) : (
                <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            )}
        </button>
    );
}
