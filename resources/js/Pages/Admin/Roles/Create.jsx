import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Create({ permisos }) {
    const { data, setData, post, processing, errors } = useForm({
        nombre: '',
        slug: '',
        descripcion: '',
        permisos: [],
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.roles.store'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Nuevo rol</h2>}>
            <Head title="Nuevo rol" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Roles y permisos', href: route('admin.roles.index') },
                        { label: 'Nuevo rol' },
                    ]}
                    title="Nuevo rol"
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
                    />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
