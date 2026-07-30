import InputError from '@/components/InputError';
import InputLabel from '@/components/InputLabel';
import PrimaryButton from '@/components/PrimaryButton';
import TextInput from '@/components/TextInput';
import GuestLayout from '@/layouts/GuestLayout';
import { useAuth } from '@/context/AuthContext';
import useForm from '@/hooks/useForm';
import { useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

export default function TwoFactorChallenge() {
    const { login } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const twoFactorId = location.state?.twoFactorId;

    const { data, setData, post, processing, errors } = useForm({
        two_factor_id: twoFactorId ?? '',
        codigo: '',
    });

    useEffect(() => {
        if (!twoFactorId) {
            navigate('/login');
        }
    }, [twoFactorId, navigate]);

    const submit = (e) => {
        e.preventDefault();

        post('/two-factor-challenge', {
            onSuccess: (result) => {
                login(result.token, result.user);
                navigate('/dashboard');
            },
        });
    };

    return (
        <GuestLayout>
            <h1 className="text-lg font-semibold text-slate-900 dark:text-white">Verificación en dos pasos</h1>

            <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">
                Ingresa el código de tu aplicación de autenticación.
            </p>

            <form onSubmit={submit} className="mt-6">
                <InputLabel htmlFor="codigo" value="Código" />

                <TextInput
                    id="codigo"
                    type="text"
                    name="codigo"
                    value={data.codigo}
                    className="mt-1 block w-full"
                    isFocused={true}
                    onChange={(e) => setData('codigo', e.target.value)}
                />

                <InputError message={errors.codigo} className="mt-2" />

                <div className="mt-6 flex items-center justify-end">
                    <PrimaryButton disabled={processing}>Verificar</PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
