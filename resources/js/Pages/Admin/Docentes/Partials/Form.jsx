import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Link } from '@inertiajs/react';

export default function Form({ data, setData, errors, processing, onSubmit, cancelHref, showPassword = false }) {
    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div>
                <InputLabel htmlFor="name" value="Nombre completo" />
                <TextInput
                    id="name"
                    className="mt-1 block w-full"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    isFocused
                />
                <InputError message={errors.name} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="username" value="Nombre de usuario" />
                <TextInput
                    id="username"
                    className="mt-1 block w-full"
                    value={data.username ?? ''}
                    onChange={(e) => setData('username', e.target.value)}
                />
                <InputError message={errors.username} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="email" value="Correo electrónico" />
                <TextInput
                    id="email"
                    type="email"
                    className="mt-1 block w-full"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                />
                <InputError message={errors.email} className="mt-2" />
            </div>

            {showPassword && (
                <div>
                    <InputLabel htmlFor="password" value="Contraseña inicial" />
                    <TextInput
                        id="password"
                        type="password"
                        className="mt-1 block w-full"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>
            )}

            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor="numero_empleado" value="Número de empleado (opcional)" />
                    <TextInput
                        id="numero_empleado"
                        className="mt-1 block w-full"
                        value={data.numero_empleado ?? ''}
                        onChange={(e) => setData('numero_empleado', e.target.value)}
                    />
                    <InputError message={errors.numero_empleado} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="telefono" value="Teléfono (opcional)" />
                    <TextInput
                        id="telefono"
                        className="mt-1 block w-full"
                        value={data.telefono ?? ''}
                        onChange={(e) => setData('telefono', e.target.value)}
                    />
                    <InputError message={errors.telefono} className="mt-2" />
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
