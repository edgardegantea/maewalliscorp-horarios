export function Table({ children }) {
    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">{children}</table>
        </div>
    );
}

export function THead({ children }) {
    return <thead>{children}</thead>;
}

const ALINEACION = { left: 'text-left', right: 'text-right', center: 'text-center' };

export function TH({ children, className = '', align = 'left' }) {
    return (
        <th
            className={`whitespace-nowrap px-4 py-3 ${ALINEACION[align]} text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400 ${className}`}
        >
            {children}
        </th>
    );
}

export function TBody({ children }) {
    return <tbody className="divide-y divide-slate-100 dark:divide-slate-800">{children}</tbody>;
}

export function TR({ children, className = '' }) {
    return (
        <tr className={`transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60 ${className}`}>
            {children}
        </tr>
    );
}

export function TD({ children, className = '', align = 'left' }) {
    return (
        <td className={`px-4 py-3 ${ALINEACION[align]} text-slate-700 dark:text-slate-300 ${className}`}>
            {children}
        </td>
    );
}

export function EmptyRow({ colSpan, children }) {
    return (
        <tr>
            <td colSpan={colSpan} className="px-4 py-12 text-center text-sm text-slate-400 dark:text-slate-500">
                {children}
            </td>
        </tr>
    );
}
