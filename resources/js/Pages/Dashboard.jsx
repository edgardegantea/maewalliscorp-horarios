import Icon from '@/Components/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

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

                {alertas && (alertas.grupos_sin_clases.length > 0 || alertas.docentes_sin_disponibilidad.length > 0) && (
                    <PanelPendientes alertas={alertas} />
                )}
            </div>
        </AuthenticatedLayout>
    );
}

// Resumen de pendientes: arranca colapsado a una fila por categoría (solo el
// conteo) para no empujar el resto del contenido; cada categoría se expande
// por separado en una lista con scroll propio, así el bloque nunca crece sin
// límite ni corta texto a la mitad con "…".
function PanelPendientes({ alertas }) {
    const categorias = [
        {
            clave: 'grupos',
            icon: 'user',
            etiqueta: 'sin ninguna clase asignada',
            items: alertas.grupos_sin_clases,
            enlaceDe:
                route().has('admin.cargas.disponibilidad') &&
                ((item) => route('admin.cargas.disponibilidad', { periodo: alertas.periodo_id, grupo: item.id })),
        },
        {
            clave: 'docentes',
            icon: 'users',
            etiqueta: 'sin disponibilidad registrada',
            items: alertas.docentes_sin_disponibilidad,
        },
    ].filter((c) => c.items.length > 0);

    return (
        <div className="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-500/20 dark:bg-amber-500/10">
            <div className="flex items-center gap-2 border-b border-amber-200/70 px-5 py-3 dark:border-amber-500/20">
                <Icon name="clock" className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                <h3 className="text-sm font-semibold text-amber-800 dark:text-amber-400">
                    Pendientes en {alertas.periodo}
                </h3>
            </div>
            <div className="divide-y divide-amber-200/70 dark:divide-amber-500/20">
                {categorias.map((c) => (
                    <CategoriaPendiente key={c.clave} {...c} />
                ))}
            </div>
        </div>
    );
}

function CategoriaPendiente({ icon, etiqueta, items, enlaceDe }) {
    const [abierto, setAbierto] = useState(false);

    return (
        <div>
            <button
                type="button"
                onClick={() => setAbierto((v) => !v)}
                className="flex w-full items-center justify-between gap-3 px-5 py-3 text-left transition hover:bg-amber-100/60 dark:hover:bg-amber-500/10"
                aria-expanded={abierto}
            >
                <span className="flex items-center gap-2 text-sm font-medium text-amber-800 dark:text-amber-400">
                    <Icon name={icon} className="h-4 w-4 shrink-0" />
                    {items.length} {items.length === 1 ? 'elemento' : 'elementos'} {etiqueta}
                </span>
                <Icon
                    name="chevronDown"
                    className={`h-4 w-4 shrink-0 text-amber-600 transition-transform dark:text-amber-400 ${abierto ? 'rotate-180' : ''}`}
                />
            </button>
            {abierto && (
                <ul className="max-h-48 space-y-1 overflow-y-auto px-5 pb-4 text-xs text-amber-700 dark:text-amber-500/80">
                    {items.map((item) => {
                        const texto = typeof item === 'string' ? item : item.texto;
                        return (
                            <li key={texto} className="truncate">
                                {enlaceDe ? (
                                    <Link href={enlaceDe(item)} className="underline decoration-dotted hover:text-amber-900 dark:hover:text-amber-300">
                                        {texto}
                                    </Link>
                                ) : (
                                    texto
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
}
