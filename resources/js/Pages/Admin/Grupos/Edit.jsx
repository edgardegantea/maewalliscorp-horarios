import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Edit({ grupo, carreras, periodos }) {
    const { data, setData, put, processing, errors } = useForm({
        carrera_id: grupo.carrera_id,
        periodo_escolar_id: grupo.periodo_escolar_id,
        nombre: grupo.nombre,
        semestre: grupo.semestre ?? '',
        matricula: grupo.matricula,
        modalidad: grupo.modalidad,
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.grupos.update', grupo.id));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Editar grupo</h2>}>
            <Head title="Editar grupo" />

            <div className="max-w-2xl space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Grupos', href: route('admin.grupos.index') },
                        { label: grupo.nombre },
                    ]}
                    title="Editar grupo"
                />
                <Card>
                    <Form
                        data={data}
                        setData={setData}
                        errors={errors}
                        processing={processing}
                        onSubmit={submit}
                        cancelHref={route('admin.grupos.index')}
                        carreras={carreras}
                        periodos={periodos}
                    />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
