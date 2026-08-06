import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Edit({ usuario, roles, grupos }) {
    const { data, setData, put, processing, errors } = useForm({
        name: usuario.name ?? '',
        username: usuario.username ?? '',
        email: usuario.email ?? '',
        password: '',
        roles: usuario.roles.map((r) => r.id),
        grupos: usuario.grupos_usuarios.map((g) => g.id),
    });

    const alternarRol = (id) => {
        setData('roles', data.roles.includes(id) ? data.roles.filter((r) => r !== id) : [...data.roles, id]);
    };

    const alternarGrupo = (id) => {
        setData('grupos', data.grupos.includes(id) ? data.grupos.filter((g) => g !== id) : [...data.grupos, id]);
    };

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.usuarios.update', usuario.id));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Editar usuario</h2>}>
            <Head title={`Editar ${usuario.name}`} />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Usuarios', href: route('admin.usuarios.index') },
                        { label: usuario.name },
                    ]}
                    title={usuario.name}
                    description={`${usuario.username ?? ''} · ${usuario.email ?? 'sin correo'} · Rol base: ${usuario.role}`}
                />

                <Card>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="name" value="Nombre" />
                                <TextInput
                                    id="name"
                                    className="mt-1 block w-full"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="username" value="Usuario" />
                                <TextInput
                                    id="username"
                                    className="mt-1 block w-full"
                                    value={data.username}
                                    onChange={(e) => setData('username', e.target.value)}
                                    required
                                />
                                <InputError message={errors.username} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="email" value="Correo electrónico" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                />
                                <InputError message={errors.email} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="password" value="Nueva contraseña" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    className="mt-1 block w-full"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Dejar en blanco para no cambiarla"
                                    autoComplete="new-password"
                                />
                                <InputError message={errors.password} className="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Roles personalizados" />
                            <div className="mt-2 space-y-1">
                                {roles.map((role) => (
                                    <label key={role.id} className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                        <input
                                            type="checkbox"
                                            className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700"
                                            checked={data.roles.includes(role.id)}
                                            onChange={() => alternarRol(role.id)}
                                        />
                                        {role.nombre}
                                        {role.es_sistema && (
                                            <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                                sistema
                                            </span>
                                        )}
                                    </label>
                                ))}
                            </div>
                            <InputError message={errors.roles} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel value="Grupos de usuarios" />
                            <div className="mt-2 space-y-1">
                                {grupos.map((grupo) => (
                                    <label key={grupo.id} className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                        <input
                                            type="checkbox"
                                            className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700"
                                            checked={data.grupos.includes(grupo.id)}
                                            onChange={() => alternarGrupo(grupo.id)}
                                        />
                                        {grupo.nombre}
                                    </label>
                                ))}
                                {grupos.length === 0 && (
                                    <p className="text-sm text-slate-400 dark:text-slate-500">No hay grupos creados.</p>
                                )}
                            </div>
                            <InputError message={errors.grupos} className="mt-2" />
                        </div>

                        <PrimaryButton disabled={processing}>Guardar</PrimaryButton>
                    </form>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
