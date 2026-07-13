import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        nombre: '',
        capacidad: '',
        tipo: '',
        activo: true,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.aulas.store'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Nueva aula</h2>}>
            <Head title="Nueva aula" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Aulas', href: route('admin.aulas.index') },
                        { label: 'Nueva aula' },
                    ]}
                    title="Nueva aula"
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
