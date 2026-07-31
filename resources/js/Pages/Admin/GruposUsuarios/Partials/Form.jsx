import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Link } from '@inertiajs/react';

export default function Form({ data, setData, errors, processing, onSubmit, cancelHref, usuarios }) {
    const alternar = (id) => {
        setData(
            'usuarios',
            data.usuarios.includes(id) ? data.usuarios.filter((u) => u !== id) : [...data.usuarios, id]
        );
    };

    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div>
                <InputLabel htmlFor="nombre" value="Nombre del grupo" />
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
                <InputLabel value="Miembros" />
                <div className="mt-2 max-h-72 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                    {usuarios.map((usuario) => (
                        <label key={usuario.id} className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                            <input
                                type="checkbox"
                                className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700"
                                checked={data.usuarios.includes(usuario.id)}
                                onChange={() => alternar(usuario.id)}
                            />
                            {usuario.name} <span className="text-slate-400">({usuario.username})</span>
                        </label>
                    ))}
                </div>
                <InputError message={errors.usuarios} className="mt-2" />
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
