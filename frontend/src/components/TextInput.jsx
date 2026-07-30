import { forwardRef } from 'react';

const TextInput = forwardRef(function TextInput({ className = '', isFocused = false, ...props }, ref) {
    return (
        <input
            {...props}
            ref={ref}
            autoFocus={isFocused}
            className={
                'rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white ' +
                className
            }
        />
    );
});

export default TextInput;
