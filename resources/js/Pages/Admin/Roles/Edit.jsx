import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Edit({ role, permisos }) {
    const { data, setData, put, processing, errors } = useForm({
        nombre: role.nombre,
        slug: role.slug,
        descripcion: role.descripcion ?? '',
        permisos: role.permissions.map((p) => p.id),
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.roles.update', role.id));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Editar rol</h2>}>
            <Head title={`Editar ${role.nombre}`} />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Roles y permisos', href: route('admin.roles.index') },
                        { label: role.nombre },
                    ]}
                    title={`Editar ${role.nombre}`}
                />
                <Card>
                    <Form
                        data={data}
                        setData={setData}
                        errors={errors}
                        processing={processing}
                        onSubmit={submit}
                        cancelHref={route('admin.roles.index')}
                        permisos={permisos}
                        esSistema={role.es_sistema}
                    />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
