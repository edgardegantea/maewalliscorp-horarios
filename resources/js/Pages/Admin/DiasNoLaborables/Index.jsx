import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';

export default function Index({ dias }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        fecha: '',
        descripcion: '',
    });

    const agregar = (e) => {
        e.preventDefault();
        post(route('admin.dias-no-laborables.store'), { onSuccess: () => reset() });
    };

    const eliminar = (dia) => {
        if (confirm(`¿Eliminar "${dia.descripcion}" (${dia.fecha}) de los días no laborables?`)) {
            router.delete(route('admin.dias-no-laborables.destroy', dia.id));
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Días no laborables</h2>}>
            <Head title="Días no laborables" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Días no laborables' }]}
                    title="Días no laborables"
                    description="Fechas informativas (festivos, suspensión de labores) que se muestran a docentes y administradores. El horario semanal recurrente no cambia automáticamente por estas fechas — sirven como aviso."
                />

                <Card>
                    <form onSubmit={agregar} className="flex flex-wrap items-end gap-3">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Fecha</label>
                            <TextInput
                                type="date"
                                className="mt-1 block"
                                value={data.fecha}
                                onChange={(e) => setData('fecha', e.target.value)}
                            />
                            <InputError message={errors.fecha} className="mt-1" />
                        </div>
                        <div className="flex-1">
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Descripción</label>
                            <TextInput
                                className="mt-1 block w-full"
                                placeholder="Ej. Día de la Independencia"
                                value={data.descripcion}
                                onChange={(e) => setData('descripcion', e.target.value)}
                            />
                            <InputError message={errors.descripcion} className="mt-1" />
                        </div>
                        <PrimaryButton disabled={processing}>Agregar</PrimaryButton>
                    </form>
                </Card>

                <Card padded={false}>
                    <Table>
                        <THead>
                            <TR>
                                <TH>Fecha</TH>
                                <TH>Descripción</TH>
                                <TH align="right">
                                    <span className="sr-only">Acciones</span>
                                </TH>
                            </TR>
                        </THead>
                        <TBody>
                            {dias.map((dia) => (
                                <TR key={dia.id}>
                                    <TD className="font-medium text-slate-900 dark:text-white">{dia.fecha}</TD>
                                    <TD>{dia.descripcion}</TD>
                                    <TD align="right">
                                        <button
                                            onClick={() => eliminar(dia)}
                                            className="font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            Eliminar
                                        </button>
                                    </TD>
                                </TR>
                            ))}
                            {dias.length === 0 && <EmptyRow colSpan={3}>No hay días no laborables registrados.</EmptyRow>}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
