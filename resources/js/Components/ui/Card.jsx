export default function Card({ className = '', padded = true, children }) {
    return (
        <div
            className={`rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 ${padded ? 'p-6' : ''} ${className}`}
        >
            {children}
        </div>
    );
}
