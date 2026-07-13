import ImportCsvButton from '@/Components/ImportCsvButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import useBusqueda from '@/Hooks/useBusqueda';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ docentes, carreras, filtros }) {
    const [q, setQ] = useBusqueda('admin.docentes.index', filtros);

    const eliminar = (docente) => {
        if (confirm(`¿Eliminar al docente "${docente.user.name}"? Esto también eliminará su acceso al sistema.`)) {
            router.delete(route('admin.docentes.destroy', docente.id));
        }
    };

    const filtrar = (cambios) => {
        router.get(
            route('admin.docentes.index'),
            { ...filtros, ...cambios },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hayFiltros = filtros.q || filtros.carrera;

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Docentes</h2>}>
            <Head title="Docentes" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Docentes' }]}
                    title="Docentes"
                    description="Cuentas de acceso y perfil de los docentes de la institución."
                    actions={
                        <>
                            <ImportCsvButton
                                action={route('admin.docentes.import')}
                                columnas={['name', 'username', 'email', 'numero_empleado (opcional)', 'telefono (opcional)']}
                                nota="Se genera una contraseña temporal; el docente debe usar “¿Olvidaste tu contraseña?” en el login."
                            />
                            <Link href={route('admin.docentes.create')}>
                                <PrimaryButton>Nuevo docente</PrimaryButton>
                            </Link>
                        </>
                    }
                />

                <Card>
                    <div className="flex flex-wrap items-end gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Buscar</label>
                            <TextInput
                                className="mt-1 block w-64"
                                placeholder="Nombre, usuario, correo o no. empleado…"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Carrera</label>
                            <SelectInput
                                className="mt-1 block w-56"
                                value={filtros.carrera ?? ''}
                                onChange={(e) => filtrar({ carrera: e.target.value || undefined })}
                            >
                                <option value="">Todas</option>
                                {carreras.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.nombre}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>

                        {hayFiltros && (
                            <Link
                                href={route('admin.docentes.index')}
                                className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                Limpiar filtros
                            </Link>
                        )}

                        <span className="ml-auto text-sm text-slate-400 dark:text-slate-500">
                            {docentes.length} {docentes.length === 1 ? 'docente' : 'docentes'}
                        </span>
                    </div>
                </Card>

                <Card padded={false}>
                    <Table>
                        <THead>
                            <TR>
                                <TH>Nombre</TH>
                                <TH>Usuario</TH>
                                <TH>Correo</TH>
                                <TH>No. empleado</TH>
                                <TH>Carreras asignadas</TH>
                                <TH align="right">
                                    <span className="sr-only">Acciones</span>
                                </TH>
                            </TR>
                        </THead>
                        <TBody>
                            {docentes.map((docente) => (
                                <TR key={docente.id}>
                                    <TD className="font-medium text-slate-900">{docente.user.name}</TD>
                                    <TD>{docente.user.username ?? '—'}</TD>
                                    <TD>{docente.user.email}</TD>
                                    <TD>{docente.numero_empleado ?? '—'}</TD>
                                    <TD>
                                        {docente.docente_carreras.length === 0
                                            ? '—'
                                            : docente.docente_carreras
                                                  .map((dc) => `${dc.carrera.nombre} (${dc.periodo_escolar.nombre})`)
                                                  .join(', ')}
                                    </TD>
                                    <TD align="right">
                                        <div className="flex justify-end gap-4">
                                            <Link
                                                href={route('admin.docentes.edit', docente.id)}
                                                className="font-medium text-indigo-600 hover:text-indigo-800"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() => eliminar(docente)}
                                                className="font-medium text-red-600 hover:text-red-800"
                                            >
                                                Eliminar
                                            </button>
                                        </div>
                                    </TD>
                                </TR>
                            ))}
                            {docentes.length === 0 && (
                                <EmptyRow colSpan={6}>No hay docentes que coincidan con los filtros.</EmptyRow>
                            )}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
