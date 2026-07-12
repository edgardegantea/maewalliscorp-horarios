import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        nombre: '',
        clave: '',
        activo: true,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.carreras.store'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Nueva carrera</h2>}>
            <Head title="Nueva carrera" />

            <div className="max-w-2xl space-y-6">
                <PageHeader title="Nueva carrera" />
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
