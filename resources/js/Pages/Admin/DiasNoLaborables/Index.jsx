import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import useBusqueda from '@/Hooks/useBusqueda';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';

export default function Index({ dias, anios, filtros }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        fecha: '',
        descripcion: '',
    });
    const [q, setQ] = useBusqueda('admin.dias-no-laborables.index', filtros);

    const agregar = (e) => {
        e.preventDefault();
        post(route('admin.dias-no-laborables.store'), { onSuccess: () => reset() });
    };

    const eliminar = (dia) => {
        if (confirm(`¿Eliminar "${dia.descripcion}" (${dia.fecha}) de los días no laborables?`)) {
            router.delete(route('admin.dias-no-laborables.destroy', dia.id));
        }
    };

    const filtrar = (cambios) => {
        router.get(
            route('admin.dias-no-laborables.index'),
            { ...filtros, ...cambios },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hayFiltros = filtros.q || filtros.anio;

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

                <Card>
                    <div className="flex flex-wrap items-end gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Buscar</label>
                            <TextInput
                                className="mt-1 block w-56"
                                placeholder="Descripción…"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Año</label>
                            <SelectInput
                                className="mt-1 block w-32"
                                value={filtros.anio ?? ''}
                                onChange={(e) => filtrar({ anio: e.target.value || undefined })}
                            >
                                <option value="">Todos</option>
                                {anios.map((a) => (
                                    <option key={a} value={a}>
                                        {a}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>

                        {hayFiltros && (
                            <Link
                                href={route('admin.dias-no-laborables.index')}
                                className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                Limpiar filtros
                            </Link>
                        )}

                        <span className="ml-auto text-sm text-slate-400 dark:text-slate-500">
                            {dias.length} {dias.length === 1 ? 'día' : 'días'}
                        </span>
                    </div>
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
                            {dias.length === 0 && (
                                <EmptyRow colSpan={3}>
                                    {hayFiltros ? 'No hay días que coincidan con los filtros.' : 'No hay días no laborables registrados.'}
                                </EmptyRow>
                            )}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
