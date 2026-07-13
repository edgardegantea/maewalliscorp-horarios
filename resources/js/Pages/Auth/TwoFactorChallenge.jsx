import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function TwoFactorChallenge() {
    const { data, setData, post, processing, errors, reset } = useForm({
        codigo: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('two-factor.challenge.store'), {
            onFinish: () => reset('codigo'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Verificación en dos pasos" />

            <h1 className="text-lg font-semibold text-slate-900 dark:text-white">Verificación en dos pasos</h1>
            <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">
                Ingresa el código de 6 dígitos generado por tu app autenticadora.
            </p>

            <form onSubmit={submit} className="mt-6">
                <div>
                    <InputLabel htmlFor="codigo" value="Código" />
                    <TextInput
                        id="codigo"
                        type="text"
                        inputMode="numeric"
                        name="codigo"
                        value={data.codigo}
                        className="mt-1 block w-full"
                        autoComplete="one-time-code"
                        isFocused
                        onChange={(e) => setData('codigo', e.target.value)}
                    />
                    <InputError message={errors.codigo} className="mt-2" />
                </div>

                <div className="mt-6 flex justify-end">
                    <PrimaryButton disabled={processing}>Verificar</PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
