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

            <div className="flex items-center gap-4">
                <PrimaryButton disabled={processing}>Guardar</PrimaryButton>
                <Link href={cancelHref} className="text-sm text-slate-600 underline dark:text-slate-400 dark:hover:text-slate-200">
                    Cancelar
                </Link>
            </div>
        </form>
    );
}
