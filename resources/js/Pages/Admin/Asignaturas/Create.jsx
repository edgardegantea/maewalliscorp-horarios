import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Create({ carreras }) {
    const { data, setData, post, processing, errors } = useForm({
        carrera_id: '',
        nombre: '',
        clave: '',
        semestre: '',
        horas_semana: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.asignaturas.store'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Nueva asignatura</h2>}>
            <Head title="Nueva asignatura" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Asignaturas', href: route('admin.asignaturas.index') },
                        { label: 'Nueva asignatura' },
                    ]}
                    title="Nueva asignatura"
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
