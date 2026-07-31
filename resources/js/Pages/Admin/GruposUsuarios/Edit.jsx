import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Edit({ grupo, usuarios }) {
    const { data, setData, put, processing, errors } = useForm({
        nombre: grupo.nombre,
        descripcion: grupo.descripcion ?? '',
        usuarios: grupo.usuarios.map((u) => u.id),
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.grupos-usuarios.update', grupo.id));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Editar grupo</h2>}>
            <Head title={`Editar ${grupo.nombre}`} />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Grupos de usuarios', href: route('admin.grupos-usuarios.index') },
                        { label: grupo.nombre },
                    ]}
                    title={`Editar ${grupo.nombre}`}
                />
                <Card>
                    <Form
                        data={data}
                        setData={setData}
                        errors={errors}
                        processing={processing}
                        onSubmit={submit}
                        cancelHref={route('admin.grupos-usuarios.index')}
                        usuarios={usuarios}
                    />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
