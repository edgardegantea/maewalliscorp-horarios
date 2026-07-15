import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import { Link } from '@inertiajs/react';

export default function Form({ data, setData, errors, processing, onSubmit, cancelHref, carreras, periodos }) {
    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor="carrera_id" value="Carrera" />
                    <SelectInput
                        id="carrera_id"
                        className="mt-1 block w-full"
                        value={data.carrera_id}
                        onChange={(e) => setData('carrera_id', e.target.value)}
                    >
                        <option value="">Selecciona una carrera</option>
                        {carreras.map((carrera) => (
                            <option key={carrera.id} value={carrera.id}>
                                {carrera.nombre}
                            </option>
                        ))}
                    </SelectInput>
                    <InputError message={errors.carrera_id} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="periodo_escolar_id" value="Periodo escolar" />
                    <SelectInput
                        id="periodo_escolar_id"
                        className="mt-1 block w-full"
                        value={data.periodo_escolar_id}
                        onChange={(e) => setData('periodo_escolar_id', e.target.value)}
                    >
                        <option value="">Selecciona un periodo</option>
                        {periodos.map((periodo) => (
                            <option key={periodo.id} value={periodo.id}>
                                {periodo.nombre}
                            </option>
                        ))}
                    </SelectInput>
                    <InputError message={errors.periodo_escolar_id} className="mt-2" />
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor="nombre" value="Nombre del grupo" />
                    <TextInput
                        id="nombre"
                        className="mt-1 block w-full"
                        placeholder="1A"
                        value={data.nombre}
                        onChange={(e) => setData('nombre', e.target.value)}
                    />
                    <InputError message={errors.nombre} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="semestre" value="Semestre (opcional)" />
                    <TextInput
                        id="semestre"
                        type="number"
                        min="1"
                        className="mt-1 block w-full"
                        value={data.semestre ?? ''}
                        onChange={(e) => setData('semestre', e.target.value)}
                    />
                    <InputError message={errors.semestre} className="mt-2" />
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor="matricula" value="Matrícula (alumnos inscritos)" />
                    <TextInput
                        id="matricula"
                        type="number"
                        min="1"
                        className="mt-1 block w-full"
                        value={data.matricula}
                        onChange={(e) => setData('matricula', e.target.value)}
                    />
                    <InputError message={errors.matricula} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="modalidad" value="Modalidad" />
                    <TextInput
                        id="modalidad"
                        className="mt-1 block w-full"
                        placeholder="Escolarizado"
                        value={data.modalidad}
                        onChange={(e) => setData('modalidad', e.target.value)}
                    />
                    <InputError message={errors.modalidad} className="mt-2" />
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor="hora_inicio" value="Horario del grupo — inicio (opcional)" />
                    <TextInput
                        id="hora_inicio"
                        type="time"
                        className="mt-1 block w-full"
                        value={data.hora_inicio ?? ''}
                        onChange={(e) => setData('hora_inicio', e.target.value)}
                    />
                    <InputError message={errors.hora_inicio} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="hora_fin" value="Horario del grupo — fin (opcional)" />
                    <TextInput
                        id="hora_fin"
                        type="time"
                        className="mt-1 block w-full"
                        value={data.hora_fin ?? ''}
                        onChange={(e) => setData('hora_fin', e.target.value)}
                    />
                    <InputError message={errors.hora_fin} className="mt-2" />
                </div>
            </div>
            <p className="-mt-4 text-xs text-slate-500 dark:text-slate-400">
                Si defines un horario, las cargas académicas asignadas a este grupo deberán caer dentro de ese
                rango (además de la disponibilidad del docente).
            </p>

            <div>
                <InputLabel htmlFor="fecha_corte_modulo" value="Fecha de corte de módulo (opcional)" />
                <TextInput
                    id="fecha_corte_modulo"
                    type="date"
                    className="mt-1 block w-full sm:w-1/2"
                    value={data.fecha_corte_modulo ?? ''}
                    onChange={(e) => setData('fecha_corte_modulo', e.target.value)}
                />
                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Para grupos que tienen clase el sábado (terminados en "F" o "B", p. ej. 1F o 1B): fecha en la que
                    termina el módulo 1 (primera mitad del semestre, hasta 3 materias) y empieza el módulo 2
                    (segunda mitad, el resto de las materias).
                </p>
                <InputError message={errors.fecha_corte_modulo} className="mt-2" />
            </div>

            <div className="flex items-center gap-4">
                <PrimaryButton disabled={processing}>Guardar</PrimaryButton>
                <Link href={cancelHref} className="text-sm text-slate-600 underline dark:text-slate-400 dark:hover:text-slate-200">
                    Cancelar
                </Link>
            </div>
        </form>
    );
}
