import { useAuth } from '@/context/AuthContext';
import { useNavigate } from 'react-router-dom';

export default function AuthenticatedLayout({ children }) {
    const { user, logout } = useAuth();
    const navigate = useNavigate();

    const handleLogout = async () => {
        await logout();
        navigate('/login');
    };

    return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
            <nav className="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-xs font-bold text-white">
                            CA
                        </div>
                        <span className="text-sm font-semibold text-slate-900 dark:text-white">Cargas Académicas</span>
                    </div>

                    <div className="flex items-center gap-4">
                        <span className="text-sm text-slate-600 dark:text-slate-400">{user?.name}</span>
                        <button
                            onClick={handleLogout}
                            className="text-sm text-slate-500 underline hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                        >
                            Cerrar sesión
                        </button>
                    </div>
                </div>
            </nav>

            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">{children}</main>
        </div>
    );
}
