import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        login: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Recuperar contraseña" />

            <h1 className="text-lg font-semibold text-slate-900 dark:text-white">Recuperar contraseña</h1>

            <div className="mt-4 text-sm text-slate-600 dark:text-slate-400">
                ¿Olvidaste tu contraseña? No hay problema. Escribe tu usuario o correo
                electrónico y te enviaremos un enlace para crear una nueva.
            </div>

            {status && (
                <div className="mt-4 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="mt-6">
                <InputLabel htmlFor="login" value="Usuario o correo electrónico" />
                <TextInput
                    id="login"
                    type="text"
                    name="login"
                    value={data.login}
                    className="mt-1 block w-full"
                    autoComplete="username"
                    isFocused={true}
                    onChange={(e) => setData('login', e.target.value)}
                />

                <InputError message={errors.login} className="mt-2" />

                <div className="mt-4 flex items-center justify-end">
                    <PrimaryButton disabled={processing}>
                        Enviar enlace de recuperación
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
