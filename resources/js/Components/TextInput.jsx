import { forwardRef, useEffect, useRef } from 'react';

export default forwardRef(function TextInput({ type = 'text', className = '', isFocused = false, ...props }, ref) {
    const input = ref ? ref : useRef();

    useEffect(() => {
        if (isFocused) {
            input.current.focus();
        }
    }, []);

    return (
        <input
            {...props}
            type={type}
            className={
                'rounded-md border-slate-300 bg-white/90 px-3 py-2 text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30 ' +
                className
            }
            ref={input}
        />
    );
});
