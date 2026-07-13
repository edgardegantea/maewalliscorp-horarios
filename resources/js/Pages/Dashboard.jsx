import Icon from '@/Components/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

const accesosAdmin = [
    { name: 'admin.cargas.index', titulo: 'Cargas académicas', desc: 'Arma y publica los horarios por periodo y carrera.', icon: 'clipboard' },
    { name: 'admin.docentes.index', titulo: 'Docentes', desc: 'Gestiona docentes, sus carreras y disponibilidad.', icon: 'users' },
    { name: 'admin.periodos.index', titulo: 'Periodos escolares', desc: 'Define los periodos escolares.', icon: 'calendar' },
    { name: 'admin.grupos.index', titulo: 'Grupos', desc: 'Administra grupos y su matrícula.', icon: 'user' },
];

const accesosCoordinador = [
    { name: 'admin.cargas.index', titulo: 'Cargas académicas', desc: 'Arma y publica los horarios de tus carreras.', icon: 'clipboard' },
    { name: 'admin.grupos.index', titulo: 'Grupos', desc: 'Administra los grupos de tus carreras.', icon: 'user' },
    { name: 'admin.asignaturas.index', titulo: 'Asignaturas', desc: 'Administra las asignaturas de tus carreras.', icon: 'book' },
];

const accesosDocente = [
    { name: 'docente.disponibilidad.edit', titulo: 'Mi disponibilidad', desc: 'Registra tus bloques de disponibilidad por periodo.', icon: 'clock' },
    { name: 'docente.horario', titulo: 'Mi horario', desc: 'Consulta tu horario de clases asignado.', icon: 'calendar' },
];

const ACCESOS_POR_ROL = {
    admin: accesosAdmin,
    coordinador: accesosCoordinador,
    docente: accesosDocente,
};

export default function Dashboard({ alertas }) {
    const user = usePage().props.auth.user;
    const esAdmin = user.role === 'admin';
    const accesos = (ACCESOS_POR_ROL[user.role] ?? accesosDocente).filter((a) => route().has(a.name));

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Inicio</h2>}>
            <Head title="Inicio" />

            <div className="space-y-6">
                <PageHeader
                    title={`Bienvenido, ${user.name}`}
                    description={
                        esAdmin
                            ? 'Panel de administración del sistema de cargas académicas.'
                            : user.role === 'coordinador'
                              ? 'Panel de coordinación de carrera.'
                              : 'Portal del docente.'
                    }
                />

                {alertas && (alertas.grupos_sin_clases.length > 0 || alertas.docentes_sin_disponibilidad.length > 0) && (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/20 dark:bg-amber-500/10">
                        <h3 className="text-sm font-semibold text-amber-800 dark:text-amber-400">
                            Pendientes en {alertas.periodo}
                        </h3>
                        <div className="mt-3 grid gap-4 sm:grid-cols-2">
                            {alertas.grupos_sin_clases.length > 0 && (
                                <div>
                                    <p className="text-sm font-medium text-amber-800 dark:text-amber-400">
                                        {alertas.grupos_sin_clases.length} grupo(s) sin ninguna clase asignada
                                    </p>
                                    <p className="mt-1 text-xs text-amber-700 dark:text-amber-500/80">
                                        {alertas.grupos_sin_clases.slice(0, 6).join(', ')}
                                        {alertas.grupos_sin_clases.length > 6 && '…'}
                                    </p>
                                </div>
                            )}
                            {alertas.docentes_sin_disponibilidad.length > 0 && (
                                <div>
                                    <p className="text-sm font-medium text-amber-800 dark:text-amber-400">
                                        {alertas.docentes_sin_disponibilidad.length} docente(s) sin disponibilidad registrada
                                    </p>
                                    <p className="mt-1 text-xs text-amber-700 dark:text-amber-500/80">
                                        {alertas.docentes_sin_disponibilidad.slice(0, 6).join(', ')}
                                        {alertas.docentes_sin_disponibilidad.length > 6 && '…'}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {accesos.map((a) => (
                        <Link
                            key={a.name}
                            href={route(a.name)}
                            className="group flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-6 transition hover:border-indigo-200 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-800"
                        >
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:group-hover:bg-indigo-500/20">
                                <Icon name={a.icon} className="h-5 w-5" />
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold text-slate-900 dark:text-white">{a.titulo}</h3>
                                <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{a.desc}</p>
                            </div>
                        </Link>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
