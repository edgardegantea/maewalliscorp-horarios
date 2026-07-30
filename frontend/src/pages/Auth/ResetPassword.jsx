import InputError from '@/components/InputError';
import InputLabel from '@/components/InputLabel';
import PrimaryButton from '@/components/PrimaryButton';
import TextInput from '@/components/TextInput';
import GuestLayout from '@/layouts/GuestLayout';
import useForm from '@/hooks/useForm';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';

export default function ResetPassword() {
    const { token } = useParams();
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();

    const { data, setData, post, processing, errors } = useForm({
        token,
        email: searchParams.get('email') ?? '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post('/reset-password', {
            onSuccess: () => navigate('/login'),
        });
    };

    return (
        <GuestLayout>
            <h1 className="text-lg font-semibold text-slate-900 dark:text-white">Crear nueva contraseña</h1>

            <form onSubmit={submit} className="mt-6">
                <div>
                    <InputLabel htmlFor="email" value="Correo electrónico" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
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
                        isFocused={true}
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password_confirmation" value="Confirmar contraseña" />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                    />

                    <InputError message={errors.password_confirmation} className="mt-2" />
                </div>

                <div className="mt-6 flex items-center justify-end">
                    <PrimaryButton disabled={processing}>Restablecer contraseña</PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
