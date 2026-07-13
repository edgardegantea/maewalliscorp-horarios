import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        nombre: '',
        fecha_inicio: '',
        fecha_fin: '',
        activo: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.periodos.store'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Nuevo periodo escolar</h2>}>
            <Head title="Nuevo periodo escolar" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Periodos escolares', href: route('admin.periodos.index') },
                        { label: 'Nuevo periodo' },
                    ]}
                    title="Nuevo periodo escolar"
                />
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
