import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';

export default function Perfil({ usuario, docente, carreras, asignaturas, experiencias }) {
    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Mi perfil</h2>}>
            <Head title="Mi perfil" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Mi perfil' }]}
                    title="Mi perfil"
                    description="Edita tus datos personales y profesionales, y consulta tu historial académico."
                />

                <DatosForm usuario={usuario} docente={docente} />
                <HistorialAcademico carreras={carreras} asignaturas={asignaturas} />
                <ExperienciaAdicional experiencias={experiencias} />
            </div>
        </AuthenticatedLayout>
    );
}

function DatosForm({ usuario, docente }) {
    const { data, setData, put, processing, errors } = useForm({
        telefono: docente.telefono ?? '',
        direccion: docente.direccion ?? '',
        fecha_nacimiento: docente.fecha_nacimiento ?? '',
        curp: docente.curp ?? '',
        rfc: docente.rfc ?? '',
        grado_academico: docente.grado_academico ?? '',
        cedula_profesional: docente.cedula_profesional ?? '',
        especialidad: docente.especialidad ?? '',
        anios_experiencia: docente.anios_experiencia ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('docente.perfil.update'));
    };

    return (
        <Card>
            <h3 className="text-sm font-semibold text-slate-900 dark:text-white">Datos personales y profesionales</h3>
            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {usuario.name} · {usuario.username} · {usuario.email} · Nº empleado: {docente.numero_empleado ?? '—'}
            </p>

            <form onSubmit={submit} className="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor="telefono" value="Teléfono" />
                    <TextInput
                        id="telefono"
                        className="mt-1 block w-full"
                        value={data.telefono}
                        onChange={(e) => setData('telefono', e.target.value)}
                    />
                    <InputError message={errors.telefono} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="fecha_nacimiento" value="Fecha de nacimiento" />
                    <TextInput
                        id="fecha_nacimiento"
                        type="date"
                        className="mt-1 block w-full"
                        value={data.fecha_nacimiento}
                        onChange={(e) => setData('fecha_nacimiento', e.target.value)}
                    />
                    <InputError message={errors.fecha_nacimiento} className="mt-1" />
                </div>

                <div className="sm:col-span-2">
                    <InputLabel htmlFor="direccion" value="Dirección" />
                    <TextInput
                        id="direccion"
                        className="mt-1 block w-full"
                        value={data.direccion}
                        onChange={(e) => setData('direccion', e.target.value)}
                    />
                    <InputError message={errors.direccion} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="curp" value="CURP" />
                    <TextInput
                        id="curp"
                        className="mt-1 block w-full"
                        value={data.curp}
                        onChange={(e) => setData('curp', e.target.value.toUpperCase())}
                    />
                    <InputError message={errors.curp} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="rfc" value="RFC" />
                    <TextInput
                        id="rfc"
                        className="mt-1 block w-full"
                        value={data.rfc}
                        onChange={(e) => setData('rfc', e.target.value.toUpperCase())}
                    />
                    <InputError message={errors.rfc} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="grado_academico" value="Grado académico" />
                    <TextInput
                        id="grado_academico"
                        className="mt-1 block w-full"
                        placeholder="Licenciatura, Maestría, Doctorado..."
                        value={data.grado_academico}
                        onChange={(e) => setData('grado_academico', e.target.value)}
                    />
                    <InputError message={errors.grado_academico} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="cedula_profesional" value="Cédula profesional" />
                    <TextInput
                        id="cedula_profesional"
                        className="mt-1 block w-full"
                        value={data.cedula_profesional}
                        onChange={(e) => setData('cedula_profesional', e.target.value)}
                    />
                    <InputError message={errors.cedula_profesional} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="especialidad" value="Especialidad / área" />
                    <TextInput
                        id="especialidad"
                        className="mt-1 block w-full"
                        value={data.especialidad}
                        onChange={(e) => setData('especialidad', e.target.value)}
                    />
                    <InputError message={errors.especialidad} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="anios_experiencia" value="Años de experiencia" />
                    <TextInput
                        id="anios_experiencia"
                        type="number"
                        min="0"
                        max="100"
                        className="mt-1 block w-full"
                        value={data.anios_experiencia}
                        onChange={(e) => setData('anios_experiencia', e.target.value)}
                    />
                    <InputError message={errors.anios_experiencia} className="mt-1" />
                </div>

                <div className="sm:col-span-2 flex justify-end">
                    <PrimaryButton disabled={processing}>Guardar cambios</PrimaryButton>
                </div>
            </form>
        </Card>
    );
}

function HistorialAcademico({ carreras, asignaturas }) {
    return (
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
    );
}

function ExperienciaAdicional({ experiencias }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        institucion: '',
        puesto_o_materia: '',
        periodo_texto: '',
        descripcion: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('docente.perfil.experiencias.store'), {
            onSuccess: () => reset(),
        });
    };

    const eliminar = (experiencia) => {
        if (confirm('¿Eliminar esta experiencia?')) {
            router.delete(route('docente.perfil.experiencias.destroy', experiencia.id));
        }
    };

    return (
        <Card>
            <h3 className="text-sm font-semibold text-slate-900 dark:text-white">Experiencia adicional</h3>
            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Registra experiencia académica previa o externa que no está en este sistema (otras instituciones o materias).
            </p>

            <ul className="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                {experiencias.length === 0 && (
                    <li className="py-4 text-sm text-slate-400 dark:text-slate-500">Sin experiencia adicional registrada.</li>
                )}
                {experiencias.map((exp) => (
                    <li key={exp.id} className="flex items-start justify-between gap-4 py-4">
                        <div>
                            <p className="text-sm font-medium text-slate-900 dark:text-white">
                                {exp.puesto_o_materia} — {exp.institucion}
                            </p>
                            {exp.periodo_texto && (
                                <p className="text-xs text-slate-500 dark:text-slate-400">{exp.periodo_texto}</p>
                            )}
                            {exp.descripcion && (
                                <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{exp.descripcion}</p>
                            )}
                        </div>
                        <DangerButton type="button" onClick={() => eliminar(exp)}>
                            Eliminar
                        </DangerButton>
                    </li>
                ))}
            </ul>

            <form onSubmit={submit} className="mt-6 grid gap-4 border-t border-slate-200 pt-6 sm:grid-cols-2 dark:border-slate-800">
                <div>
                    <InputLabel htmlFor="institucion" value="Institución" />
                    <TextInput
                        id="institucion"
                        className="mt-1 block w-full"
                        value={data.institucion}
                        onChange={(e) => setData('institucion', e.target.value)}
                    />
                    <InputError message={errors.institucion} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="puesto_o_materia" value="Puesto o materia" />
                    <TextInput
                        id="puesto_o_materia"
                        className="mt-1 block w-full"
                        value={data.puesto_o_materia}
                        onChange={(e) => setData('puesto_o_materia', e.target.value)}
                    />
                    <InputError message={errors.puesto_o_materia} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="periodo_texto" value="Periodo (texto libre)" />
                    <TextInput
                        id="periodo_texto"
                        className="mt-1 block w-full"
                        placeholder="2019-2021"
                        value={data.periodo_texto}
                        onChange={(e) => setData('periodo_texto', e.target.value)}
                    />
                    <InputError message={errors.periodo_texto} className="mt-1" />
                </div>

                <div className="sm:col-span-2">
                    <InputLabel htmlFor="descripcion" value="Descripción" />
                    <TextInput
                        id="descripcion"
                        className="mt-1 block w-full"
                        value={data.descripcion}
                        onChange={(e) => setData('descripcion', e.target.value)}
                    />
                    <InputError message={errors.descripcion} className="mt-1" />
                </div>

                <div className="sm:col-span-2 flex justify-end">
                    <PrimaryButton disabled={processing}>Agregar experiencia</PrimaryButton>
                </div>
            </form>
        </Card>
    );
}
