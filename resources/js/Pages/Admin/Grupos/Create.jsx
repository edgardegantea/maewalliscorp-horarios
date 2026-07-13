import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Create({ carreras, periodos }) {
    const { data, setData, post, processing, errors } = useForm({
        carrera_id: '',
        periodo_escolar_id: '',
        nombre: '',
        semestre: '',
        matricula: '',
        modalidad: 'Escolarizado',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.grupos.store'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Nuevo grupo</h2>}>
            <Head title="Nuevo grupo" />

            <div className="max-w-2xl space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Grupos', href: route('admin.grupos.index') },
                        { label: 'Nuevo grupo' },
                    ]}
                    title="Nuevo grupo"
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
