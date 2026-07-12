import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Edit({ carrera }) {
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

            <div className="max-w-2xl space-y-6">
                <PageHeader title="Editar carrera" />
                <Card>
                    <Form
                        data={data}
                        setData={setData}
                        errors={errors}
                        processing={processing}
                        onSubmit={submit}
                        cancelHref={route('admin.carreras.index')}
                    />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
