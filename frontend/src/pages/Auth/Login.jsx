import Checkbox from '@/components/Checkbox';
import InputError from '@/components/InputError';
import InputLabel from '@/components/InputLabel';
import PrimaryButton from '@/components/PrimaryButton';
import TextInput from '@/components/TextInput';
import GuestLayout from '@/layouts/GuestLayout';
import { useAuth } from '@/context/AuthContext';
import useForm from '@/hooks/useForm';
import { Link, useNavigate } from 'react-router-dom';

export default function Login() {
    const { login } = useAuth();
    const navigate = useNavigate();
    const { data, setData, post, processing, errors, reset } = useForm({
        login: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post('/login', {
            onSuccess: (result) => {
                if (result.two_factor_required) {
                    navigate('/two-factor-challenge', { state: { twoFactorId: result.two_factor_id } });
                    return;
                }

                login(result.token, result.user);
                navigate('/dashboard');
            },
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <h1 className="text-lg font-semibold text-slate-900 dark:text-white">Iniciar sesión</h1>

            <form onSubmit={submit} className="mt-6">
                <div>
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
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Contraseña" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4 block">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                        />
                        <span className="ms-2 text-sm text-slate-600 dark:text-slate-400">Recordarme</span>
                    </label>
                </div>

                <div className="mt-6 flex items-center justify-end">
                    <Link
                        to="/forgot-password"
                        className="rounded-md text-sm text-slate-500 underline hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-slate-400 dark:hover:text-slate-200"
                    >
                        ¿Olvidaste tu contraseña?
                    </Link>

                    <PrimaryButton className="ms-4" disabled={processing}>
                        Iniciar sesión
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
