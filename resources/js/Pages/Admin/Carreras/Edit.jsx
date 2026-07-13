import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Coordinadores from './Partials/Coordinadores';
import Form from './Partials/Form';

export default function Edit({ carrera, coordinadoresDisponibles }) {
    const { data, setData, put, processing, errors } = useForm({
        nombre: carrera.nombre,
        clave: carrera.clave,
        activo: carrera.activo,
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.carreras.update', carrera.id));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Editar carrera</h2>}>
            <Head title="Editar carrera" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Carreras', href: route('admin.carreras.index') },
                        { label: carrera.nombre },
                    ]}
                    title="Editar carrera"
                />
                <Card>
                    <Form
                        data={data}
                        setData={setData}
                        errors={errors}
                        processing={processing}
                        onSubmit={submit}
                        cancelHref={route('admin.carreras.index')}
                    />

                    <Coordinadores carrera={carrera} coordinadoresDisponibles={coordinadoresDisponibles} />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
