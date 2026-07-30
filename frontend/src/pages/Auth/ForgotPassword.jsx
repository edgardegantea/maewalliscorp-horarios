import InputError from '@/components/InputError';
import InputLabel from '@/components/InputLabel';
import PrimaryButton from '@/components/PrimaryButton';
import TextInput from '@/components/TextInput';
import GuestLayout from '@/layouts/GuestLayout';
import useForm from '@/hooks/useForm';
import { useState } from 'react';

export default function ForgotPassword() {
    const [status, setStatus] = useState(null);
    const { data, setData, post, processing, errors } = useForm({ login: '' });

    const submit = (e) => {
        e.preventDefault();
        setStatus(null);

        post('/forgot-password', {
            onSuccess: (result) => setStatus(result.status),
        });
    };

    return (
        <GuestLayout>
            <h1 className="text-lg font-semibold text-slate-900 dark:text-white">Recuperar contraseña</h1>

            <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">
                Ingresa tu usuario o correo electrónico y te enviaremos un enlace para crear una nueva contraseña.
            </p>

            {status && (
                <div className="mt-4 text-sm font-medium text-emerald-600 dark:text-emerald-400">{status}</div>
            )}

            <form onSubmit={submit} className="mt-6">
                <InputLabel htmlFor="login" value="Usuario o correo electrónico" />

                <TextInput
                    id="login"
                    type="text"
                    name="login"
                    value={data.login}
                    className="mt-1 block w-full"
                    isFocused={true}
                    onChange={(e) => setData('login', e.target.value)}
                />

                <InputError message={errors.login} className="mt-2" />

                <div className="mt-6 flex items-center justify-end">
                    <PrimaryButton disabled={processing}>Enviar enlace</PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
