import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth }) {
    return (
        <>
            <Head title="Sistema de Cargas Académicas" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-slate-100 p-6 dark:bg-slate-950">
                <div className="w-full max-w-xl rounded-lg bg-white p-10 text-center shadow dark:bg-slate-900">
                    <h1 className="text-2xl font-semibold text-slate-900 dark:text-white">
                        Sistema de Asignación de Cargas Académicas
                    </h1>
                    <p className="mt-3 text-sm text-slate-600 dark:text-slate-400">
                        Gestión de horarios por periodo escolar y carrera, con control de empalmes
                        de docentes, aulas y grupos.
                    </p>

                    <div className="mt-8">
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="inline-flex items-center rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                Ir al panel
                            </Link>
                        ) : (
                            <Link
                                href={route('login')}
                                className="inline-flex items-center rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                Iniciar sesión
                            </Link>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
