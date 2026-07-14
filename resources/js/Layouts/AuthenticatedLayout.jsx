import Dropdown from '@/Components/Dropdown';
import FlashBanner from '@/Components/FlashBanner';
import Icon from '@/Components/Icon';
import InactivityLogout from '@/Components/InactivityLogout';
import ThemeToggle from '@/Components/ThemeToggle';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

const adminLinks = [
    { name: 'dashboard', label: 'Dashboard', icon: 'home' },
    { name: 'admin.periodos.index', label: 'Periodos', icon: 'calendar' },
    { name: 'admin.carreras.index', label: 'Carreras', icon: 'academicCap' },
    { name: 'admin.coordinadores.index', label: 'Coordinadores', icon: 'user' },
    { name: 'admin.docentes.index', label: 'Docentes', icon: 'users' },
    { name: 'admin.asignaturas.index', label: 'Asignaturas', icon: 'book' },
    { name: 'admin.grupos.index', label: 'Grupos', icon: 'user' },
    { name: 'admin.aulas.index', label: 'Aulas', icon: 'building' },
    { name: 'admin.cargas.index', label: 'Cargas académicas', icon: 'clipboard' },
    { name: 'admin.reportes.carga-docente', label: 'Carga de trabajo', icon: 'chartBar' },
    { name: 'admin.reportes.utilizacion-aulas', label: 'Utilización de aulas', icon: 'chartBar' },
    { name: 'admin.dias-no-laborables.index', label: 'Días no laborables', icon: 'calendar' },
    { name: 'admin.auditoria.index', label: 'Auditoría', icon: 'listBullet' },
];

const coordinadorLinks = [
    { name: 'dashboard', label: 'Dashboard', icon: 'home' },
    { name: 'admin.asignaturas.index', label: 'Asignaturas', icon: 'book' },
    { name: 'admin.grupos.index', label: 'Grupos', icon: 'user' },
    { name: 'admin.cargas.index', label: 'Cargas académicas', icon: 'clipboard' },
    { name: 'admin.reportes.carga-docente', label: 'Carga de trabajo', icon: 'chartBar' },
];

const docenteLinks = [
    { name: 'dashboard', label: 'Dashboard', icon: 'home' },
    { name: 'docente.disponibilidad.edit', label: 'Mi disponibilidad', icon: 'clock' },
    { name: 'docente.horario', label: 'Mi horario', icon: 'calendar' },
];

const LINKS_POR_ROL = {
    admin: adminLinks,
    coordinador: coordinadorLinks,
    docente: docenteLinks,
};

function estaActivo(name) {
    if (name === 'dashboard') {
        return route().current('dashboard');
    }

    return route().current(name.replace(/\.[^.]+$/, '') + '.*');
}

function NavItem({ link, onClick }) {
    const activo = estaActivo(link.name);

    return (
        <Link
            href={route(link.name)}
            onClick={onClick}
            className={`group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                activo
                    ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white'
            }`}
        >
            <Icon
                name={link.icon}
                className={`h-5 w-5 shrink-0 ${
                    activo
                        ? 'text-indigo-600 dark:text-indigo-400'
                        : 'text-slate-400 group-hover:text-slate-500 dark:text-slate-500 dark:group-hover:text-slate-400'
                }`}
            />
            {link.label}
        </Link>
    );
}

function SidebarContent({ links, onNavigate }) {
    return (
        <>
            <div className="flex h-16 shrink-0 items-center gap-2 px-6">
                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white">
                    CA
                </div>
                <span className="text-sm font-semibold text-slate-900 dark:text-white">Cargas Académicas</span>
            </div>
            <nav className="flex flex-1 flex-col gap-1 px-4 pb-4">
                {links.map((link) => (
                    <NavItem key={link.name} link={link} onClick={onNavigate} />
                ))}
            </nav>
        </>
    );
}

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;
    const [menuAbierto, setMenuAbierto] = useState(false);

    const links = (LINKS_POR_ROL[user.role] ?? docenteLinks).filter((link) => route().has(link.name));

    return (
        <div className="min-h-screen bg-slate-50 print:bg-white dark:bg-slate-950">
            {/* Sidebar de escritorio */}
            <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-slate-200 bg-white lg:flex print:hidden dark:border-slate-800 dark:bg-slate-900">
                <SidebarContent links={links} />
            </aside>

            {/* Sidebar móvil */}
            {menuAbierto && (
                <div className="fixed inset-0 z-40 lg:hidden">
                    <div
                        className="fixed inset-0 bg-slate-900/50"
                        onClick={() => setMenuAbierto(false)}
                    />
                    <aside className="fixed inset-y-0 left-0 flex w-64 flex-col bg-white shadow-xl dark:bg-slate-900">
                        <SidebarContent links={links} onNavigate={() => setMenuAbierto(false)} />
                    </aside>
                </div>
            )}

            <div className="flex flex-col lg:pl-64 print:pl-0">
                <header className="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-4 border-b border-slate-200 bg-white/80 px-4 backdrop-blur sm:px-6 lg:px-8 print:hidden dark:border-slate-800 dark:bg-slate-900/80">
                    <button
                        type="button"
                        onClick={() => setMenuAbierto(true)}
                        className="text-slate-500 hover:text-slate-700 lg:hidden dark:text-slate-400 dark:hover:text-slate-200"
                    >
                        <Icon name="menu" />
                    </button>

                    <div className="min-w-0 flex-1">{header}</div>

                    <ThemeToggle />

                    <Dropdown>
                        <Dropdown.Trigger>
                            <button
                                type="button"
                                className="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                <span className="flex h-7 w-7 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                    {user.name.charAt(0).toUpperCase()}
                                </span>
                                <span className="hidden sm:inline">{user.name}</span>
                                <Icon name="chevronDown" className="h-4 w-4 text-slate-400 dark:text-slate-500" />
                            </button>
                        </Dropdown.Trigger>

                        <Dropdown.Content contentClasses="py-1 bg-white dark:bg-slate-800">
                            <Dropdown.Link href={route('profile.edit')}>Perfil</Dropdown.Link>
                            <Dropdown.Link href={route('logout')} method="post" as="button">
                                Cerrar sesión
                            </Dropdown.Link>
                        </Dropdown.Content>
                    </Dropdown>
                </header>

                <div className="print:hidden">
                    <FlashBanner />
                    <InactivityLogout />
                </div>

                <main className="flex-1 px-4 py-8 sm:px-6 lg:px-8 print:p-0">{children}</main>
            </div>
        </div>
    );
}
