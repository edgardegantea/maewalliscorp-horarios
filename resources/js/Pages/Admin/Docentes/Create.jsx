import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import Form from './Partials/Form';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        username: '',
        email: '',
        password: '',
        numero_empleado: '',
        telefono: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.docentes.store'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Nuevo docente</h2>}>
            <Head title="Nuevo docente" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Docentes', href: route('admin.docentes.index') },
                        { label: 'Nuevo docente' },
                    ]}
                    title="Nuevo docente"
                />
                <Card>
                    <Form
                        data={data}
                        setData={setData}
                        errors={errors}
                        processing={processing}
                        onSubmit={submit}
                        cancelHref={route('admin.docentes.index')}
                        showPassword
                    />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
