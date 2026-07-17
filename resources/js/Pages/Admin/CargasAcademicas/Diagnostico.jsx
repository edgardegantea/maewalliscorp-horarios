import SelectInput from '@/Components/SelectInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

const SECCIONES = [
    { clave: 'docentes', titulo: 'Empalmes de docente', descripcion: 'Un mismo docente con dos clases que se traslapan en el mismo día y módulo.' },
    { clave: 'aulas', titulo: 'Empalmes de aula', descripcion: 'Una misma aula ocupada por dos clases que se traslapan en el mismo día y módulo.' },
    { clave: 'grupos', titulo: 'Empalmes de grupo', descripcion: 'Un mismo grupo con dos clases que se traslapan en el mismo día y módulo.' },
];

export default function Diagnostico({ periodo, periodos, empalmes }) {
    const cambiarPeriodo = (e) => {
        router.get(route('admin.cargas.diagnostico'), { periodo: e.target.value }, { preserveState: true });
    };

    const totalEmpalmes = empalmes
        ? empalmes.docentes.length + empalmes.aulas.length + empalmes.grupos.length
        : 0;

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Diagnóstico de horarios</h2>}>
            <Head title="Diagnóstico de horarios" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Cargas académicas', href: route('admin.cargas.index') },
                        { label: 'Diagnóstico de horarios' },
                    ]}
                    title="Diagnóstico de horarios"
                    description="Revisa empalmes reales de docente, aula o grupo en todos los días del periodo, en todas las carreras."
                />

                <Card>
                    <div className="flex flex-wrap items-end gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Periodo escolar</label>
                            <SelectInput className="mt-1 block w-64" value={periodo?.id ?? ''} onChange={cambiarPeriodo}>
                                <option value="">Selecciona un periodo</option>
                                {periodos.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.nombre}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                    </div>
                </Card>

                {!periodo && (
                    <p className="rounded-lg bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20">
                        Selecciona un periodo escolar para revisar sus empalmes.
                    </p>
                )}

                {periodo && totalEmpalmes === 0 && (
                    <p className="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20">
                        No se encontró ningún empalme de docente, aula o grupo en {periodo.nombre}.
                    </p>
                )}

                {periodo &&
                    empalmes &&
                    SECCIONES.map(
                        (s) =>
                            empalmes[s.clave].length > 0 && (
                                <Card key={s.clave}>
                                    <h3 className="text-sm font-semibold text-slate-900 dark:text-white">
                                        {s.titulo} <span className="font-normal text-slate-400">({empalmes[s.clave].length})</span>
                                    </h3>
                                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{s.descripcion}</p>

                                    <div className="mt-4">
                                        <Table>
                                            <THead>
                                                <TR>
                                                    <TH>Entidad</TH>
                                                    <TH>Día</TH>
                                                    <TH>Clase A</TH>
                                                    <TH>Clase B</TH>
                                                </TR>
                                            </THead>
                                            <TBody>
                                                {empalmes[s.clave].map((e, idx) => (
                                                    <TR key={idx}>
                                                        <TD className="font-medium text-slate-900 dark:text-white">{e.entidad}</TD>
                                                        <TD>{e.dia}</TD>
                                                        <TD>
                                                            <DescripcionCarga carga={e.cargas[0]} periodoId={periodo.id} />
                                                        </TD>
                                                        <TD>
                                                            <DescripcionCarga carga={e.cargas[1]} periodoId={periodo.id} />
                                                        </TD>
                                                    </TR>
                                                ))}
                                                {empalmes[s.clave].length === 0 && <EmptyRow colSpan={4}>Sin empalmes.</EmptyRow>}
                                            </TBody>
                                        </Table>
                                    </div>
                                </Card>
                            ),
                    )}
            </div>
        </AuthenticatedLayout>
    );
}

function DescripcionCarga({ carga, periodoId }) {
    const puedeVerEnGrid = route().has('admin.cargas.builder');

    return (
        <div className="text-xs">
            <div className="font-medium text-slate-700 dark:text-slate-300">
                {carga.hora_inicio} - {carga.hora_fin}
            </div>
            <div className="text-slate-500 dark:text-slate-400">
                {carga.asignatura} · {carga.docente} · {carga.aula}
            </div>
            {puedeVerEnGrid && (
                <Link
                    href={route('admin.cargas.builder', {
                        periodo: periodoId,
                        carrera: carga.carrera_id,
                        docente: carga.docente_id,
                        editar: carga.id,
                        dia: carga.dia_semana,
                        hora_inicio: carga.hora_inicio,
                        hora_fin: carga.hora_fin,
                        asignatura_id: carga.asignatura_id,
                        aula_id: carga.aula_id,
                        grupo_ids: carga.grupo_ids.join(','),
                        modulo_sabatino: carga.modulo_sabatino || undefined,
                    })}
                    className="mt-0.5 inline-block text-indigo-600 underline decoration-dotted hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    Ver en el grid
                </Link>
            )}
        </div>
    );
}
