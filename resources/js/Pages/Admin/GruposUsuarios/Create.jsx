import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Create({ usuarios }) {
    const { data, setData, post, processing, errors } = useForm({
        nombre: '',
        descripcion: '',
        usuarios: [],
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.grupos-usuarios.store'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Nuevo grupo de usuarios</h2>}>
            <Head title="Nuevo grupo de usuarios" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Grupos de usuarios', href: route('admin.grupos-usuarios.index') },
                        { label: 'Nuevo grupo' },
                    ]}
                    title="Nuevo grupo de usuarios"
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
