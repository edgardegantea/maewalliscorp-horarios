import { Link } from 'react-router-dom';

export default function GuestLayout({ children }) {
    return (
        <div className="relative flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 py-12 dark:bg-slate-950">
            <Link to="/" className="mb-6 flex items-center gap-2">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white">
                    CA
                </div>
                <span className="text-sm font-semibold text-slate-900 dark:text-white">Cargas Académicas</span>
            </Link>

            <div className="w-full overflow-hidden rounded-xl border border-slate-200 bg-white px-6 py-8 shadow-sm sm:max-w-md dark:border-slate-800 dark:bg-slate-900">
                {children}
            </div>
        </div>
    );
}
