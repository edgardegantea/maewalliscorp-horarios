import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Link } from '@inertiajs/react';

export default function Form({ data, setData, errors, processing, onSubmit, cancelHref }) {
    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div>
                <InputLabel htmlFor="nombre" value="Nombre" />
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
                <InputLabel htmlFor="clave" value="Clave" />
                <TextInput
                    id="clave"
                    className="mt-1 block w-full"
                    value={data.clave}
                    onChange={(e) => setData('clave', e.target.value.toUpperCase())}
                />
                <InputError message={errors.clave} className="mt-2" />
            </div>

            <div>
                <label className="flex items-center">
                    <Checkbox
                        checked={data.activo}
                        onChange={(e) => setData('activo', e.target.checked)}
                    />
                    <span className="ms-2 text-sm text-slate-600 dark:text-slate-400">Activa</span>
                </label>
                <InputError message={errors.activo} className="mt-2" />
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
