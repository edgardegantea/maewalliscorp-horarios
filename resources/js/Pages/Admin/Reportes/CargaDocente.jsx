import Badge from '@/Components/ui/Badge';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import SelectInput from '@/Components/SelectInput';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';

const DIAS = { 1: 'Lun', 2: 'Mar', 3: 'Mié', 4: 'Jue', 5: 'Vie', 6: 'Sáb' };

export default function CargaDocente({ periodos, periodoSeleccionado, docentes }) {
    const cambiarPeriodo = (periodoId) => {
        router.get(route('admin.reportes.carga-docente'), { periodo: periodoId || undefined }, { preserveState: true });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Carga de trabajo por docente</h2>}>
            <Head title="Carga de trabajo por docente" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Carga de trabajo' }]}
                    title="Carga de trabajo por docente"
                    description="Horas asignadas por día y total semanal, comparadas contra el límite de 8h/día."
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
                                <TH>Docente</TH>
                                {Object.values(DIAS).map((d) => (
                                    <TH key={d} align="center">
                                        {d}
                                    </TH>
                                ))}
                                <TH align="center">Total semana</TH>
                                <TH></TH>
                            </TR>
                        </THead>
                        <TBody>
                            {docentes.map((docente) => (
                                <TR key={docente.id}>
                                    <TD className="font-medium text-slate-900 dark:text-white">{docente.nombre}</TD>
                                    {Object.keys(DIAS).map((dia) => {
                                        const horas = docente.horas_por_dia[dia] ?? 0;
                                        return (
                                            <TD key={dia} align="center" className={horas > 8 ? 'font-semibold text-red-600 dark:text-red-400' : ''}>
                                                {horas > 0 ? horas : '—'}
                                            </TD>
                                        );
                                    })}
                                    <TD align="center" className="font-medium">
                                        {docente.horas_totales}h
                                    </TD>
                                    <TD align="right">
                                        {docente.excede_algun_dia && <Badge color="red">Excede 8h en algún día</Badge>}
                                    </TD>
                                </TR>
                            ))}
                            {docentes.length === 0 && (
                                <EmptyRow colSpan={9}>No hay docentes con carga asignada en este periodo.</EmptyRow>
                            )}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
