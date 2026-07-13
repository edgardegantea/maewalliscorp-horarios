import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        username: '',
        email: '',
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.coordinadores.store'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Nuevo coordinador</h2>}>
            <Head title="Nuevo coordinador" />

            <div className="max-w-2xl space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Coordinadores', href: route('admin.coordinadores.index') },
                        { label: 'Nuevo coordinador' },
                    ]}
                    title="Nuevo coordinador"
                    description="Después de crearlo, asígnale una o más carreras desde la ficha de cada carrera."
                />
                <Card>
                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <InputLabel htmlFor="name" value="Nombre completo" />
                            <TextInput
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                isFocused
                            />
                            <InputError message={errors.name} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="username" value="Nombre de usuario" />
                            <TextInput
                                id="username"
                                className="mt-1 block w-full"
                                value={data.username}
                                onChange={(e) => setData('username', e.target.value)}
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
                            <InputLabel htmlFor="password" value="Contraseña inicial" />
                            <TextInput
                                id="password"
                                type="password"
                                className="mt-1 block w-full"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                            />
                            <InputError message={errors.password} className="mt-2" />
                        </div>

                        <div className="flex items-center gap-4">
                            <PrimaryButton disabled={processing}>Guardar</PrimaryButton>
                            <Link
                                href={route('admin.coordinadores.index')}
                                className="text-sm text-slate-600 underline dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                Cancelar
                            </Link>
                        </div>
                    </form>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
