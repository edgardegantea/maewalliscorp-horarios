import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Edit({ periodo }) {
    const { data, setData, put, processing, errors } = useForm({
        nombre: periodo.nombre,
        fecha_inicio: periodo.fecha_inicio,
        fecha_fin: periodo.fecha_fin,
        activo: periodo.activo,
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.periodos.update', periodo.id));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Editar periodo escolar</h2>}>
            <Head title="Editar periodo escolar" />

            <div className="max-w-2xl space-y-6">
                <PageHeader title="Editar periodo escolar" />
                <Card>
                    <Form
                        data={data}
                        setData={setData}
                        errors={errors}
                        processing={processing}
                        onSubmit={submit}
                        cancelHref={route('admin.periodos.index')}
                    />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
