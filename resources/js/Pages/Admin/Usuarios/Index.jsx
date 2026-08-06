import TextInput from '@/Components/TextInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import useBusqueda from '@/Hooks/useBusqueda';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ usuarios, filtros }) {
    const [q, setQ] = useBusqueda('admin.usuarios.index', filtros);

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Usuarios</h2>}>
            <Head title="Usuarios" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Usuarios' }]}
                    title="Usuarios"
                    description="Asigna roles personalizados y grupos a cualquier usuario del sistema."
                />

                <Card>
                    <div>
                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Buscar</label>
                        <TextInput
                            className="mt-1 block w-64"
                            placeholder="Nombre, usuario o correo…"
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                        />
                    </div>
                </Card>

                <Card padded={false}>
                    <Table>
                        <THead>
                            <TR>
                                <TH>Nombre</TH>
                                <TH>Usuario</TH>
                                <TH>Rol base</TH>
                                <TH>Roles personalizados</TH>
                                <TH>Grupos</TH>
                                <TH align="right">
                                    <span className="sr-only">Acciones</span>
                                </TH>
                            </TR>
                        </THead>
                        <TBody>
                            {usuarios.map((usuario) => (
                                <TR key={usuario.id}>
                                    <TD className="font-medium text-slate-900 dark:text-white">{usuario.name}</TD>
                                    <TD>{usuario.username ?? '—'}</TD>
                                    <TD className="capitalize">{usuario.role}</TD>
                                    <TD>
                                        {usuario.roles.length === 0
                                            ? '—'
                                            : usuario.roles.map((r) => r.nombre).join(', ')}
                                    </TD>
                                    <TD>
                                        {usuario.grupos_usuarios.length === 0
                                            ? '—'
                                            : usuario.grupos_usuarios.map((g) => g.nombre).join(', ')}
                                    </TD>
                                    <TD align="right">
                                        {usuario.puede_editar ? (
                                            <Link
                                                href={route('admin.usuarios.edit', usuario.id)}
                                                className="font-medium text-indigo-600 hover:text-indigo-800"
                                            >
                                                Editar
                                            </Link>
                                        ) : (
                                            <span className="text-slate-400 dark:text-slate-600">—</span>
                                        )}
                                    </TD>
                                </TR>
                            ))}
                            {usuarios.length === 0 && <EmptyRow colSpan={6}>No hay usuarios que coincidan.</EmptyRow>}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
