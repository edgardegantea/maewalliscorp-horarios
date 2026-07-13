import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Edit({ aula }) {
    const { data, setData, put, processing, errors } = useForm({
        nombre: aula.nombre,
        capacidad: aula.capacidad ?? '',
        tipo: aula.tipo ?? '',
        activo: aula.activo,
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.aulas.update', aula.id));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Editar aula</h2>}>
            <Head title="Editar aula" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Aulas', href: route('admin.aulas.index') },
                        { label: aula.nombre },
                    ]}
                    title="Editar aula"
                />
                <Card>
                    <Form
                        data={data}
                        setData={setData}
                        errors={errors}
                        processing={processing}
                        onSubmit={submit}
                        cancelHref={route('admin.aulas.index')}
                    />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
