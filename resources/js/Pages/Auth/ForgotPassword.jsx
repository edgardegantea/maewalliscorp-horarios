import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
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
                ¿Olvidaste tu contraseña? No hay problema. Escribe tu correo electrónico y te
                enviaremos un enlace para restablecerla.
            </div>

            {status && (
                <div className="mt-4 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="mt-6">
                <InputLabel htmlFor="email" value="Correo electrónico" />
                <TextInput
                    id="email"
                    type="email"
                    name="email"
                    value={data.email}
                    className="mt-1 block w-full"
                    isFocused={true}
                    onChange={(e) => setData('email', e.target.value)}
                />

                <InputError message={errors.email} className="mt-2" />

                <div className="mt-4 flex items-center justify-end">
                    <PrimaryButton disabled={processing}>
                        Enviar enlace de recuperación
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
