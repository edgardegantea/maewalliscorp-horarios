import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Link } from '@inertiajs/react';

export default function Form({ data, setData, errors, processing, onSubmit, cancelHref, permisos, esSistema = false }) {
    const alternar = (id) => {
        setData(
            'permisos',
            data.permisos.includes(id) ? data.permisos.filter((p) => p !== id) : [...data.permisos, id]
        );
    };

    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div>
                <InputLabel htmlFor="nombre" value="Nombre del rol" />
                <TextInput
                    id="nombre"
                    className="mt-1 block w-full"
                    value={data.nombre}
                    onChange={(e) => setData('nombre', e.target.value)}
                    isFocused
                />
                <InputError message={errors.nombre} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="slug" value="Identificador (slug)" />
                <TextInput
                    id="slug"
                    className="mt-1 block w-full"
                    value={data.slug}
                    disabled={esSistema}
                    onChange={(e) => setData('slug', e.target.value)}
                />
                {esSistema && (
                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Es un rol de sistema; su identificador no se puede cambiar.
                    </p>
                )}
                <InputError message={errors.slug} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="descripcion" value="Descripción (opcional)" />
                <TextInput
                    id="descripcion"
                    className="mt-1 block w-full"
                    value={data.descripcion}
                    onChange={(e) => setData('descripcion', e.target.value)}
                />
                <InputError message={errors.descripcion} className="mt-2" />
            </div>

            <div>
                <InputLabel value="Permisos" />
                <div className="mt-2 space-y-4">
                    {Object.entries(permisos).map(([modulo, lista]) => (
                        <div key={modulo} className="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                            <h4 className="text-sm font-semibold capitalize text-slate-800 dark:text-slate-200">
                                {modulo.replace(/-/g, ' ')}
                            </h4>
                            <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                {lista.map((permiso) => (
                                    <label key={permiso.id} className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                        <input
                                            type="checkbox"
                                            className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700"
                                            checked={data.permisos.includes(permiso.id)}
                                            onChange={() => alternar(permiso.id)}
                                        />
                                        {permiso.clave.split('.')[1]}
                                    </label>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
                <InputError message={errors.permisos} className="mt-2" />
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
