import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import Tabs from '@/Components/ui/Tabs';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';

// Años descendentes desde el actual hasta 1970, para los <select> de periodo.
function rangoAnios() {
    const actual = new Date().getFullYear();
    const anios = [];
    for (let a = actual + 1; a >= 1970; a--) {
        anios.push(a);
    }
    return anios;
}

const ANIOS = rangoAnios();

const ROLES_PROYECTO = [
    { value: 'responsable', label: 'Responsable' },
    { value: 'colaborador', label: 'Colaborador' },
    { value: 'asesor', label: 'Asesor' },
    { value: 'otro', label: 'Otro' },
];

export default function Perfil({
    usuario,
    docente,
    carreras,
    asignaturas,
    experiencias,
    proyectos,
    productosAcademicos,
    gradosAcademicos,
    tiposProductoAcademico,
}) {
    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Mi perfil</h2>}>
            <Head title="Mi perfil" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Mi perfil' }]}
                    title="Mi perfil"
                    description="Edita tus datos personales y profesionales, y consulta tu historial académico."
                />

                <p className="text-sm text-slate-500 dark:text-slate-400">
                    {usuario.name} · {usuario.username} · {usuario.email ?? 'sin correo'} · Nº empleado:{' '}
                    {docente.numero_empleado ?? '—'}
                </p>

                <Tabs
                    tabs={[
                        'Datos personales',
                        'Datos profesionales',
                        'Historial académico',
                        'Experiencia adicional',
                        'Proyectos',
                        'Productos académicos',
                    ]}
                >
                    <DatosPersonales docente={docente} />
                    <DatosProfesionales docente={docente} gradosAcademicos={gradosAcademicos} />
                    <HistorialAcademico carreras={carreras} asignaturas={asignaturas} />
                    <ExperienciaAdicional experiencias={experiencias} />
                    <Proyectos proyectos={proyectos} />
                    <ProductosAcademicos productos={productosAcademicos} tipos={tiposProductoAcademico} />
                </Tabs>
            </div>
        </AuthenticatedLayout>
    );
}

function DatosPersonales({ docente }) {
    const { data, setData, put, processing, errors } = useForm({
        telefono: docente.telefono ?? '',
        direccion: docente.direccion ?? '',
        fecha_nacimiento: docente.fecha_nacimiento ?? '',
        curp: docente.curp ?? '',
        rfc: docente.rfc ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('docente.perfil.update'), { preserveScroll: true });
    };

    return (
        <Card>
            <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
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

                <div className="sm:col-span-2 flex justify-end">
                    <PrimaryButton disabled={processing}>Guardar cambios</PrimaryButton>
                </div>
            </form>
        </Card>
    );
}

function DatosProfesionales({ docente, gradosAcademicos }) {
    const { data, setData, put, processing, errors } = useForm({
        grado_academico: docente.grado_academico ?? '',
        cedula_profesional: docente.cedula_profesional ?? '',
        especialidad: docente.especialidad ?? '',
        anios_experiencia: docente.anios_experiencia ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('docente.perfil.update'), { preserveScroll: true });
    };

    return (
        <Card>
            <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor="grado_academico" value="Grado académico" />
                    <SelectInput
                        id="grado_academico"
                        className="mt-1 block w-full"
                        value={data.grado_academico}
                        onChange={(e) => setData('grado_academico', e.target.value)}
                    >
                        <option value="">Selecciona una opción</option>
                        {gradosAcademicos.map((g) => (
                            <option key={g.value} value={g.value}>
                                {g.label}
                            </option>
                        ))}
                    </SelectInput>
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
                    <SelectInput
                        id="anios_experiencia"
                        className="mt-1 block w-full"
                        value={data.anios_experiencia}
                        onChange={(e) => setData('anios_experiencia', e.target.value)}
                    >
                        <option value="">Selecciona una opción</option>
                        {Array.from({ length: 51 }, (_, i) => i).map((n) => (
                            <option key={n} value={n}>
                                {n}
                            </option>
                        ))}
                    </SelectInput>
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
        anio_inicio: '',
        anio_fin: '',
        descripcion: '',
    });

    const submit = (e) => {
        e.preventDefault();

        const periodo_texto = data.anio_inicio
            ? `${data.anio_inicio} - ${data.anio_fin || 'Actual'}`
            : '';

        router.post(
            route('docente.perfil.experiencias.store'),
            { ...data, periodo_texto },
            { preserveScroll: true, onSuccess: () => reset() }
        );
    };

    const eliminar = (experiencia) => {
        if (confirm('¿Eliminar esta experiencia?')) {
            router.delete(route('docente.perfil.experiencias.destroy', experiencia.id), { preserveScroll: true });
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
                    <InputLabel htmlFor="anio_inicio" value="Año de inicio" />
                    <SelectInput
                        id="anio_inicio"
                        className="mt-1 block w-full"
                        value={data.anio_inicio}
                        onChange={(e) => setData('anio_inicio', e.target.value)}
                    >
                        <option value="">Selecciona un año</option>
                        {ANIOS.map((a) => (
                            <option key={a} value={a}>
                                {a}
                            </option>
                        ))}
                    </SelectInput>
                    <InputError message={errors.anio_inicio} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="anio_fin" value="Año de fin (vacío = actual)" />
                    <SelectInput
                        id="anio_fin"
                        className="mt-1 block w-full"
                        value={data.anio_fin}
                        onChange={(e) => setData('anio_fin', e.target.value)}
                    >
                        <option value="">Actual</option>
                        {ANIOS.map((a) => (
                            <option key={a} value={a}>
                                {a}
                            </option>
                        ))}
                    </SelectInput>
                    <InputError message={errors.anio_fin} className="mt-1" />
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

function Proyectos({ proyectos }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        nombre: '',
        rol: '',
        anio_inicio: '',
        anio_fin: '',
        institucion: '',
        descripcion: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('docente.perfil.proyectos.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const eliminar = (proyecto) => {
        if (confirm('¿Eliminar este proyecto?')) {
            router.delete(route('docente.perfil.proyectos.destroy', proyecto.id), { preserveScroll: true });
        }
    };

    return (
        <Card>
            <h3 className="text-sm font-semibold text-slate-900 dark:text-white">Proyectos realizados</h3>
            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Registra proyectos académicos, de investigación o de vinculación en los que has participado.
            </p>

            <ul className="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                {proyectos.length === 0 && (
                    <li className="py-4 text-sm text-slate-400 dark:text-slate-500">Sin proyectos registrados.</li>
                )}
                {proyectos.map((p) => (
                    <li key={p.id} className="flex items-start justify-between gap-4 py-4">
                        <div>
                            <p className="text-sm font-medium text-slate-900 dark:text-white">{p.nombre}</p>
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                {ROLES_PROYECTO.find((r) => r.value === p.rol)?.label ?? p.rol} · {p.anio_inicio} -{' '}
                                {p.anio_fin ?? 'Actual'}
                                {p.institucion ? ` · ${p.institucion}` : ''}
                            </p>
                            {p.descripcion && (
                                <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{p.descripcion}</p>
                            )}
                        </div>
                        <DangerButton type="button" onClick={() => eliminar(p)}>
                            Eliminar
                        </DangerButton>
                    </li>
                ))}
            </ul>

            <form onSubmit={submit} className="mt-6 grid gap-4 border-t border-slate-200 pt-6 sm:grid-cols-2 dark:border-slate-800">
                <div className="sm:col-span-2">
                    <InputLabel htmlFor="nombre" value="Nombre del proyecto" />
                    <TextInput
                        id="nombre"
                        className="mt-1 block w-full"
                        value={data.nombre}
                        onChange={(e) => setData('nombre', e.target.value)}
                    />
                    <InputError message={errors.nombre} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="rol" value="Rol" />
                    <SelectInput
                        id="rol"
                        className="mt-1 block w-full"
                        value={data.rol}
                        onChange={(e) => setData('rol', e.target.value)}
                    >
                        <option value="">Selecciona una opción</option>
                        {ROLES_PROYECTO.map((r) => (
                            <option key={r.value} value={r.value}>
                                {r.label}
                            </option>
                        ))}
                    </SelectInput>
                    <InputError message={errors.rol} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="institucion_proyecto" value="Institución (opcional)" />
                    <TextInput
                        id="institucion_proyecto"
                        className="mt-1 block w-full"
                        value={data.institucion}
                        onChange={(e) => setData('institucion', e.target.value)}
                    />
                    <InputError message={errors.institucion} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="proyecto_anio_inicio" value="Año de inicio" />
                    <SelectInput
                        id="proyecto_anio_inicio"
                        className="mt-1 block w-full"
                        value={data.anio_inicio}
                        onChange={(e) => setData('anio_inicio', e.target.value)}
                    >
                        <option value="">Selecciona un año</option>
                        {ANIOS.map((a) => (
                            <option key={a} value={a}>
                                {a}
                            </option>
                        ))}
                    </SelectInput>
                    <InputError message={errors.anio_inicio} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="proyecto_anio_fin" value="Año de fin (vacío = actual)" />
                    <SelectInput
                        id="proyecto_anio_fin"
                        className="mt-1 block w-full"
                        value={data.anio_fin}
                        onChange={(e) => setData('anio_fin', e.target.value)}
                    >
                        <option value="">Actual</option>
                        {ANIOS.map((a) => (
                            <option key={a} value={a}>
                                {a}
                            </option>
                        ))}
                    </SelectInput>
                    <InputError message={errors.anio_fin} className="mt-1" />
                </div>

                <div className="sm:col-span-2">
                    <InputLabel htmlFor="descripcion_proyecto" value="Descripción" />
                    <TextInput
                        id="descripcion_proyecto"
                        className="mt-1 block w-full"
                        value={data.descripcion}
                        onChange={(e) => setData('descripcion', e.target.value)}
                    />
                    <InputError message={errors.descripcion} className="mt-1" />
                </div>

                <div className="sm:col-span-2 flex justify-end">
                    <PrimaryButton disabled={processing}>Agregar proyecto</PrimaryButton>
                </div>
            </form>
        </Card>
    );
}

function ProductosAcademicos({ productos, tipos }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        tipo: '',
        titulo: '',
        anio: '',
        editorial_o_medio: '',
        enlace: '',
        descripcion: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('docente.perfil.productos-academicos.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const eliminar = (producto) => {
        if (confirm('¿Eliminar este producto académico?')) {
            router.delete(route('docente.perfil.productos-academicos.destroy', producto.id), { preserveScroll: true });
        }
    };

    return (
        <Card>
            <h3 className="text-sm font-semibold text-slate-900 dark:text-white">Productos académicos</h3>
            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Publicaciones, materiales didácticos, ponencias u otros productos académicos que has generado.
            </p>

            <ul className="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                {productos.length === 0 && (
                    <li className="py-4 text-sm text-slate-400 dark:text-slate-500">Sin productos académicos registrados.</li>
                )}
                {productos.map((p) => (
                    <li key={p.id} className="flex items-start justify-between gap-4 py-4">
                        <div>
                            <p className="text-sm font-medium text-slate-900 dark:text-white">{p.titulo}</p>
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                {tipos.find((t) => t.value === (p.tipo?.value ?? p.tipo))?.label ?? p.tipo} · {p.anio}
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
                        </div>
                        <DangerButton type="button" onClick={() => eliminar(p)}>
                            Eliminar
                        </DangerButton>
                    </li>
                ))}
            </ul>

            <form onSubmit={submit} className="mt-6 grid gap-4 border-t border-slate-200 pt-6 sm:grid-cols-2 dark:border-slate-800">
                <div>
                    <InputLabel htmlFor="tipo" value="Tipo" />
                    <SelectInput
                        id="tipo"
                        className="mt-1 block w-full"
                        value={data.tipo}
                        onChange={(e) => setData('tipo', e.target.value)}
                    >
                        <option value="">Selecciona una opción</option>
                        {tipos.map((t) => (
                            <option key={t.value} value={t.value}>
                                {t.label}
                            </option>
                        ))}
                    </SelectInput>
                    <InputError message={errors.tipo} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="anio_producto" value="Año" />
                    <SelectInput
                        id="anio_producto"
                        className="mt-1 block w-full"
                        value={data.anio}
                        onChange={(e) => setData('anio', e.target.value)}
                    >
                        <option value="">Selecciona un año</option>
                        {ANIOS.map((a) => (
                            <option key={a} value={a}>
                                {a}
                            </option>
                        ))}
                    </SelectInput>
                    <InputError message={errors.anio} className="mt-1" />
                </div>

                <div className="sm:col-span-2">
                    <InputLabel htmlFor="titulo" value="Título" />
                    <TextInput
                        id="titulo"
                        className="mt-1 block w-full"
                        value={data.titulo}
                        onChange={(e) => setData('titulo', e.target.value)}
                    />
                    <InputError message={errors.titulo} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="editorial_o_medio" value="Editorial / medio (opcional)" />
                    <TextInput
                        id="editorial_o_medio"
                        className="mt-1 block w-full"
                        value={data.editorial_o_medio}
                        onChange={(e) => setData('editorial_o_medio', e.target.value)}
                    />
                    <InputError message={errors.editorial_o_medio} className="mt-1" />
                </div>

                <div>
                    <InputLabel htmlFor="enlace" value="Enlace (opcional)" />
                    <TextInput
                        id="enlace"
                        type="url"
                        className="mt-1 block w-full"
                        placeholder="https://..."
                        value={data.enlace}
                        onChange={(e) => setData('enlace', e.target.value)}
                    />
                    <InputError message={errors.enlace} className="mt-1" />
                </div>

                <div className="sm:col-span-2">
                    <InputLabel htmlFor="descripcion_producto" value="Descripción" />
                    <TextInput
                        id="descripcion_producto"
                        className="mt-1 block w-full"
                        value={data.descripcion}
                        onChange={(e) => setData('descripcion', e.target.value)}
                    />
                    <InputError message={errors.descripcion} className="mt-1" />
                </div>

                <div className="sm:col-span-2 flex justify-end">
                    <PrimaryButton disabled={processing}>Agregar producto</PrimaryButton>
                </div>
            </form>
        </Card>
    );
}
