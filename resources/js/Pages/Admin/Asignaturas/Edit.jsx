import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Edit({ asignatura, carreras }) {
    const { data, setData, put, processing, errors } = useForm({
        carrera_id: asignatura.carrera_id,
        nombre: asignatura.nombre,
        clave: asignatura.clave ?? '',
        semestre: asignatura.semestre ?? '',
        horas_semana: asignatura.horas_semana ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.asignaturas.update', asignatura.id));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Editar asignatura</h2>}>
            <Head title="Editar asignatura" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Asignaturas', href: route('admin.asignaturas.index') },
                        { label: asignatura.nombre },
                    ]}
                    title="Editar asignatura"
                />
                <Card>
                    <Form
                        data={data}
                        setData={setData}
                        errors={errors}
                        processing={processing}
                        onSubmit={submit}
                        cancelHref={route('admin.asignaturas.index')}
                        carreras={carreras}
                    />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
