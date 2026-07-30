import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ResetPassword({ status }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        codigo: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Restablecer contraseña" />

            <h1 className="mb-2 text-lg font-semibold text-slate-900 dark:text-white">Restablecer contraseña</h1>

            <p className="mb-6 text-sm text-slate-600 dark:text-slate-400">
                Ingresa el código de 6 dígitos que enviamos a tu correo y tu nueva contraseña.
            </p>

            {status && (
                <div className="mb-4 text-sm font-medium text-emerald-600 dark:text-emerald-400">{status}</div>
            )}

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="codigo" value="Código de verificación" />

                    <TextInput
                        id="codigo"
                        type="text"
                        inputMode="numeric"
                        name="codigo"
                        value={data.codigo}
                        className="mt-1 block w-full"
                        autoComplete="one-time-code"
                        isFocused={true}
                        onChange={(e) => setData('codigo', e.target.value)}
                    />

                    <InputError message={errors.codigo} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Nueva contraseña" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel
                        htmlFor="password_confirmation"
                        value="Confirmar contraseña"
                    />

                    <TextInput
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                    />

                    <InputError
                        message={errors.password_confirmation}
                        className="mt-2"
                    />
                </div>

                <div className="mt-4 flex items-center justify-end">
                    <PrimaryButton disabled={processing}>
                        Restablecer contraseña
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
