export default function PrimaryButton({ className = '', disabled, children, ...props }) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center justify-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest text-slate-900 border border-amber-600/30 bg-gradient-to-r from-amber-500 to-amber-600 shadow-sm shadow-amber-600/10 hover:from-amber-500 hover:to-amber-500 hover:shadow-amber-600/20 active:from-amber-600 active:to-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed ` +
                className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
