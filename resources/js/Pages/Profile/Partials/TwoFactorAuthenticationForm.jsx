import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function TwoFactorAuthenticationForm({ className = '', twoFactorEnabled, twoFactorPendiente }) {
    const [configurando, setConfigurando] = useState(twoFactorPendiente);
    const [qr, setQr] = useState(null);
    const [cargandoQr, setCargandoQr] = useState(false);

    const confirmacion = useForm({ codigo: '' });
    const desactivacion = useForm({ codigo: '' });

    const iniciarConfiguracion = () => {
        router.post(route('two-factor.enable'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                setConfigurando(true);
                cargarQr();
            },
        });
    };

    const cargarQr = () => {
        setCargandoQr(true);
        window.axios
            .get(route('two-factor.qr-code'))
            .then((res) => setQr(res.data))
            .finally(() => setCargandoQr(false));
    };

    useEffect(() => {
        if (configurando && !qr && !cargandoQr) {
            cargarQr();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [configurando]);

    const confirmar = (e) => {
        e.preventDefault();
        confirmacion.post(route('two-factor.confirm'), {
            preserveScroll: true,
            onSuccess: () => {
                setConfigurando(false);
                setQr(null);
                confirmacion.reset();
            },
        });
    };

    const cancelarConfiguracion = () => {
        desactivacion.setData('codigo', '');
        setConfigurando(false);
        setQr(null);
    };

    const desactivar = (e) => {
        e.preventDefault();
        desactivacion.delete(route('two-factor.disable'), {
            preserveScroll: true,
            onSuccess: () => desactivacion.reset(),
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-slate-900 dark:text-white">Verificación en dos pasos</h2>
                <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    Agrega una capa extra de seguridad pidiendo un código de tu app autenticadora (Google
                    Authenticator, Authy, etc.) al iniciar sesión.
                </p>
            </header>

            <div className="mt-6">
                {twoFactorEnabled ? (
                    <div className="space-y-4">
                        <p className="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                            La verificación en dos pasos está activada.
                        </p>
                        <form onSubmit={desactivar} className="max-w-xs space-y-3">
                            <InputLabel htmlFor="codigo_desactivar" value="Código actual para desactivar" />
                            <TextInput
                                id="codigo_desactivar"
                                className="block w-full"
                                inputMode="numeric"
                                value={desactivacion.data.codigo}
                                onChange={(e) => desactivacion.setData('codigo', e.target.value)}
                            />
                            <InputError message={desactivacion.errors.codigo} />
                            <SecondaryButton disabled={desactivacion.processing}>Desactivar</SecondaryButton>
                        </form>
                    </div>
                ) : configurando ? (
                    <div className="space-y-4">
                        <p className="text-sm text-slate-600 dark:text-slate-400">
                            Escanea este código QR con tu app autenticadora y captura el código de 6 dígitos que
                            genere para confirmar.
                        </p>
                        {qr ? (
                            <div className="flex flex-wrap items-start gap-6">
                                <div
                                    className="w-48 rounded-lg bg-white p-2"
                                    dangerouslySetInnerHTML={{ __html: qr.svg }}
                                />
                                <div className="text-sm text-slate-500 dark:text-slate-400">
                                    <p>¿No puedes escanear? Ingresa esta clave manualmente:</p>
                                    <code className="mt-1 block rounded bg-slate-100 px-2 py-1 text-xs dark:bg-slate-800 dark:text-slate-300">
                                        {qr.secreto}
                                    </code>
                                </div>
                            </div>
                        ) : (
                            <p className="text-sm text-slate-400">Generando código QR…</p>
                        )}

                        <form onSubmit={confirmar} className="max-w-xs space-y-3">
                            <InputLabel htmlFor="codigo_confirmar" value="Código de 6 dígitos" />
                            <TextInput
                                id="codigo_confirmar"
                                className="block w-full"
                                inputMode="numeric"
                                autoFocus
                                value={confirmacion.data.codigo}
                                onChange={(e) => confirmacion.setData('codigo', e.target.value)}
                            />
                            <InputError message={confirmacion.errors.codigo} />
                            <div className="flex gap-3">
                                <PrimaryButton disabled={confirmacion.processing}>Confirmar y activar</PrimaryButton>
                                <SecondaryButton type="button" onClick={cancelarConfiguracion}>
                                    Cancelar
                                </SecondaryButton>
                            </div>
                        </form>
                    </div>
                ) : (
                    <PrimaryButton onClick={iniciarConfiguracion}>Activar verificación en dos pasos</PrimaryButton>
                )}
            </div>
        </section>
    );
}
