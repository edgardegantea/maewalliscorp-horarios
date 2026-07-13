import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import AsignacionesCarrera from './Partials/AsignacionesCarrera';
import Form from './Partials/Form';

export default function Edit({ docente, carreras, periodos }) {
    const { data, setData, put, processing, errors } = useForm({
        name: docente.user.name,
        username: docente.user.username ?? '',
        email: docente.user.email,
        numero_empleado: docente.numero_empleado ?? '',
        telefono: docente.telefono ?? '',
    });

    const periodoDisponibilidad =
        docente.docente_carreras[0]?.periodo_escolar ?? periodos[0];

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.docentes.update', docente.id));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Editar docente</h2>}>
            <Head title="Editar docente" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Docentes', href: route('admin.docentes.index') },
                        { label: docente.user.name },
                    ]}
                    title="Editar docente"
                />
                <Card>
                    <Form
                        data={data}
                        setData={setData}
                        errors={errors}
                        processing={processing}
                        onSubmit={submit}
                        cancelHref={route('admin.docentes.index')}
                    />

                    <AsignacionesCarrera docente={docente} carreras={carreras} periodos={periodos} />

                    {periodoDisponibilidad && (
                        <div className="mt-8 border-t border-slate-200 pt-6 dark:border-slate-700">
                            <h3 className="text-lg font-medium text-slate-900 dark:text-white">Disponibilidad horaria</h3>
                            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Captura o edita los bloques de disponibilidad del docente por periodo.
                            </p>
                            <Link
                                href={route('admin.docentes.disponibilidad.edit', [
                                    docente.id,
                                    periodoDisponibilidad.id,
                                ])}
                                className="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                            >
                                Editar disponibilidad →
                            </Link>
                        </div>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
