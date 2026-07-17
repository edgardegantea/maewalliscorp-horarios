import Icon from '@/Components/Icon';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import SelectInput from '@/Components/SelectInput';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function UtilizacionAulas({ periodos, periodoSeleccionado, aulas }) {
    const cambiarPeriodo = (periodoId) => {
        router.get(route('admin.reportes.utilizacion-aulas'), { periodo: periodoId || undefined }, { preserveState: true });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Utilización de aulas</h2>}>
            <Head title="Utilización de aulas" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Utilización de aulas' }]}
                    title="Utilización de aulas"
                    description="Porcentaje de ocupación respecto a las horas disponibles de la semana (7:00-21:00, lunes a sábado)."
                />

                <Card>
                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Periodo escolar</label>
                    <SelectInput
                        className="mt-1 block w-64"
                        value={periodoSeleccionado ?? ''}
                        onChange={(e) => cambiarPeriodo(e.target.value)}
                    >
                        <option value="">Selecciona un periodo</option>
                        {periodos.map((p) => (
                            <option key={p.id} value={p.id}>
                                {p.nombre}
                            </option>
                        ))}
                    </SelectInput>
                </Card>

                <Card padded={false}>
                    <Table>
                        <THead>
                            <TR>
                                <TH>Aula</TH>
                                <TH align="center">Horas ocupadas/semana</TH>
                                <TH>Ocupación</TH>
                                <TH align="right">
                                    <span className="sr-only">Acciones</span>
                                </TH>
                            </TR>
                        </THead>
                        <TBody>
                            {aulas.map((aula) => (
                                <TR key={aula.id}>
                                    <TD className="font-medium text-slate-900 dark:text-white">{aula.nombre}</TD>
                                    <TD align="center">{aula.horas_ocupadas}h</TD>
                                    <TD>
                                        <div className="flex items-center gap-3">
                                            <div className="h-2 w-40 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                                <div
                                                    className={`h-full rounded-full ${aula.porcentaje >= 80 ? 'bg-red-500' : aula.porcentaje >= 50 ? 'bg-amber-500' : 'bg-emerald-500'}`}
                                                    style={{ width: `${aula.porcentaje}%` }}
                                                />
                                            </div>
                                            <span className="text-sm text-slate-500 dark:text-slate-400">{aula.porcentaje}%</span>
                                        </div>
                                    </TD>
                                    <TD align="right">
                                        <Link
                                            href={route('admin.reportes.aula-horario', { aula: aula.id, periodo: periodoSeleccionado })}
                                            className="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            <Icon name="calendar" className="h-3.5 w-3.5" />
                                            Ver horario
                                        </Link>
                                    </TD>
                                </TR>
                            ))}
                            {aulas.length === 0 && <EmptyRow colSpan={4}>Selecciona un periodo para ver la utilización.</EmptyRow>}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
