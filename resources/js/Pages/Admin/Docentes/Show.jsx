import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import Tabs from '@/Components/ui/Tabs';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

const ROLES_PROYECTO = {
    responsable: 'Responsable',
    colaborador: 'Colaborador',
    asesor: 'Asesor',
    otro: 'Otro',
};

const PERIODOS = {
    'enero-junio': 'Enero - Junio',
    'agosto-diciembre': 'Agosto - Diciembre',
};

function Dato({ label, value }) {
    return (
        <div>
            <dt className="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{label}</dt>
            <dd className="mt-1 text-sm text-slate-900 dark:text-white">{value || '—'}</dd>
        </div>
    );
}

export default function Show({
    usuario,
    docente,
    carreras,
    asignaturas,
    experiencias,
    proyectos,
    productosAcademicos,
    gradosAcademicos,
    tiposProductoAcademico,
    historialAsignaturas,
}) {
    const gradoAcademicoLabel =
        gradosAcademicos.find((g) => g.value === docente.grado_academico)?.label ?? docente.grado_academico;

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Perfil del docente</h2>}>
            <Head title={`Perfil de ${usuario.name}`} />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Docentes', href: route('admin.docentes.index') },
                        { label: usuario.name },
                    ]}
                    title={usuario.name}
                    description="Información que el docente ha capturado en su propio perfil (solo lectura)."
                />

                <Tabs
                    tabs={[
                        'Cuenta',
                        'Datos personales',
                        'Datos profesionales',
                        'Historial académico',
                        'Experiencia adicional',
                        'Proyectos',
                        'Productos académicos',
                    ]}
                >
                    <Card>
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <Dato label="Nombre" value={usuario.name} />
                            <Dato label="Usuario" value={usuario.username} />
                            <Dato label="Correo electrónico" value={usuario.email} />
                            <Dato label="No. empleado" value={docente.numero_empleado} />
                        </dl>
                    </Card>

                    <Card>
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <Dato label="Teléfono" value={docente.telefono} />
                            <Dato label="Fecha de nacimiento" value={docente.fecha_nacimiento} />
                            <Dato label="Dirección" value={docente.direccion} />
                            <Dato label="CURP" value={docente.curp} />
                            <Dato label="RFC" value={docente.rfc} />
                        </dl>
                    </Card>

                    <Card>
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <Dato label="Grado académico" value={gradoAcademicoLabel} />
                            <Dato label="Cédula profesional" value={docente.cedula_profesional} />
                            <Dato label="Especialidad / área" value={docente.especialidad} />
                            <Dato label="Años de experiencia" value={docente.anios_experiencia} />
                        </dl>
                    </Card>

                    <div className="space-y-6">
                        <div className="grid gap-6 lg:grid-cols-2">
                            <Card padded={false}>
                                <div className="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                                    <h3 className="text-sm font-semibold text-slate-900 dark:text-white">Carreras asignadas</h3>
                                </div>
                                <Table>
                                    <THead>
                                        <TR>
                                            <TH>Carrera</TH>
                                            <TH>Periodo</TH>
                                        </TR>
                                    </THead>
                                    <TBody>
                                        {carreras.length === 0 && <EmptyRow colSpan={2}>Sin carreras asignadas.</EmptyRow>}
                                        {carreras.map((c, i) => (
                                            <TR key={i}>
                                                <TD>{c.carrera}</TD>
                                                <TD>{c.periodo}</TD>
                                            </TR>
                                        ))}
                                    </TBody>
                                </Table>
                            </Card>

                            <Card padded={false}>
                                <div className="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                                    <h3 className="text-sm font-semibold text-slate-900 dark:text-white">Asignaturas impartidas</h3>
                                </div>
                                <Table>
                                    <THead>
                                        <TR>
                                            <TH>Asignatura</TH>
                                            <TH>Carrera</TH>
                                            <TH>Periodo</TH>
                                        </TR>
                                    </THead>
                                    <TBody>
                                        {asignaturas.length === 0 && <EmptyRow colSpan={3}>Sin asignaturas registradas.</EmptyRow>}
                                        {asignaturas.map((a, i) => (
                                            <TR key={i}>
                                                <TD>{a.asignatura}</TD>
                                                <TD>{a.carrera}</TD>
                                                <TD>{a.periodo}</TD>
                                            </TR>
                                        ))}
                                    </TBody>
                                </Table>
                            </Card>
                        </div>

                        <Card padded={false}>
                            <div className="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                                <h3 className="text-sm font-semibold text-slate-900 dark:text-white">
                                    Asignaturas impartidas en semestres y años anteriores (registro manual del docente)
                                </h3>
                            </div>
                            <Table>
                                <THead>
                                    <TR>
                                        <TH>Asignatura</TH>
                                        <TH>Carrera</TH>
                                        <TH>Periodo</TH>
                                        <TH>Comentario</TH>
                                    </TR>
                                </THead>
                                <TBody>
                                    {historialAsignaturas.length === 0 && (
                                        <EmptyRow colSpan={4}>Sin registros manuales.</EmptyRow>
                                    )}
                                    {historialAsignaturas.map((item) => (
                                        <TR key={item.id}>
                                            <TD>{item.asignatura.nombre}</TD>
                                            <TD>{item.carrera.nombre}</TD>
                                            <TD>
                                                {PERIODOS[item.periodo] ?? item.periodo} {item.anio}
                                            </TD>
                                            <TD>{item.comentario ?? '—'}</TD>
                                        </TR>
                                    ))}
                                </TBody>
                            </Table>
                        </Card>
                    </div>

                    <Card padded={false}>
                        <div className="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                            <h3 className="text-sm font-semibold text-slate-900 dark:text-white">Experiencia adicional</h3>
                        </div>
                        <ul className="divide-y divide-slate-100 px-4 dark:divide-slate-800">
                            {experiencias.length === 0 && (
                                <li className="py-4 text-sm text-slate-400 dark:text-slate-500">Sin experiencia adicional registrada.</li>
                            )}
                            {experiencias.map((exp) => (
                                <li key={exp.id} className="py-4">
                                    <p className="text-sm font-medium text-slate-900 dark:text-white">
                                        {exp.puesto_o_materia} — {exp.institucion}
                                    </p>
                                    {exp.periodo_texto && (
                                        <p className="text-xs text-slate-500 dark:text-slate-400">{exp.periodo_texto}</p>
                                    )}
                                    {exp.descripcion && (
                                        <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{exp.descripcion}</p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </Card>

                    <Card padded={false}>
                        <div className="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                            <h3 className="text-sm font-semibold text-slate-900 dark:text-white">Proyectos realizados</h3>
                        </div>
                        <ul className="divide-y divide-slate-100 px-4 dark:divide-slate-800">
                            {proyectos.length === 0 && (
                                <li className="py-4 text-sm text-slate-400 dark:text-slate-500">Sin proyectos registrados.</li>
                            )}
                            {proyectos.map((p) => (
                                <li key={p.id} className="py-4">
                                    <p className="text-sm font-medium text-slate-900 dark:text-white">{p.nombre}</p>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                        {ROLES_PROYECTO[p.rol] ?? p.rol} · {p.anio_inicio} - {p.anio_fin ?? 'Actual'}
                                        {p.institucion ? ` · ${p.institucion}` : ''}
                                    </p>
                                    {p.descripcion && (
                                        <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{p.descripcion}</p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </Card>

                    <Card padded={false}>
                        <div className="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                            <h3 className="text-sm font-semibold text-slate-900 dark:text-white">Productos académicos</h3>
                        </div>
                        <ul className="divide-y divide-slate-100 px-4 dark:divide-slate-800">
                            {productosAcademicos.length === 0 && (
                                <li className="py-4 text-sm text-slate-400 dark:text-slate-500">Sin productos académicos registrados.</li>
                            )}
                            {productosAcademicos.map((p) => {
                                const tipoValue = p.tipo?.value ?? p.tipo;
                                const tipoLabel = tiposProductoAcademico.find((t) => t.value === tipoValue)?.label ?? tipoValue;

                                return (
                                    <li key={p.id} className="py-4">
                                        <p className="text-sm font-medium text-slate-900 dark:text-white">{p.titulo}</p>
                                        <p className="text-xs text-slate-500 dark:text-slate-400">
                                            {tipoLabel} · {p.anio}
                                            {p.editorial_o_medio ? ` · ${p.editorial_o_medio}` : ''}
                                        </p>
                                        {p.enlace && (
                                            <a
                                                href={p.enlace}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="text-xs text-indigo-600 underline dark:text-indigo-400"
                                            >
                                                Ver enlace
                                            </a>
                                        )}
                                        {p.descripcion && (
                                            <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{p.descripcion}</p>
                                        )}
                                    </li>
                                );
                            })}
                        </ul>
                    </Card>
                </Tabs>
            </div>
        </AuthenticatedLayout>
    );
}
