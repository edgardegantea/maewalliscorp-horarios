import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

const EVENTOS_ACTIVIDAD = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'];
const AVISO_SEGUNDOS_ANTES = 60;

export default function InactivityLogout() {
    const minutos = usePage().props.inactividadMinutos ?? 0;
    const [avisoVisible, setAvisoVisible] = useState(false);
    const [segundosRestantes, setSegundosRestantes] = useState(AVISO_SEGUNDOS_ANTES);
    const timeoutAviso = useRef(null);
    const timeoutCierre = useRef(null);
    const intervalCuenta = useRef(null);

    const cerrarSesion = useCallback(() => {
        router.post(route('logout'));
    }, []);

    const limpiarTimers = () => {
        clearTimeout(timeoutAviso.current);
        clearTimeout(timeoutCierre.current);
        clearInterval(intervalCuenta.current);
    };

    const reiniciar = useCallback(() => {
        if (!minutos || minutos <= 0) {
            return;
        }

        limpiarTimers();
        setAvisoVisible(false);

        const totalMs = minutos * 60 * 1000;
        const avisoMs = Math.max(totalMs - AVISO_SEGUNDOS_ANTES * 1000, 0);

        timeoutAviso.current = setTimeout(() => {
            setSegundosRestantes(AVISO_SEGUNDOS_ANTES);
            setAvisoVisible(true);
            intervalCuenta.current = setInterval(() => {
                setSegundosRestantes((s) => Math.max(s - 1, 0));
            }, 1000);
        }, avisoMs);

        timeoutCierre.current = setTimeout(cerrarSesion, totalMs);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [minutos]);

    useEffect(() => {
        if (!minutos || minutos <= 0) {
            return;
        }

        reiniciar();

        const alHaberActividad = () => {
            if (!avisoVisible) {
                reiniciar();
            }
        };

        EVENTOS_ACTIVIDAD.forEach((evento) => window.addEventListener(evento, alHaberActividad));

        return () => {
            EVENTOS_ACTIVIDAD.forEach((evento) => window.removeEventListener(evento, alHaberActividad));
            limpiarTimers();
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [minutos, avisoVisible]);

    if (!minutos || minutos <= 0) {
        return null;
    }

    return (
        <Modal show={avisoVisible} onClose={reiniciar} maxWidth="sm">
            <div className="p-6">
                <h3 className="text-lg font-medium text-slate-900 dark:text-white">¿Sigues ahí?</h3>
                <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Tu sesión se cerrará por inactividad en {segundosRestantes} segundos.
                </p>
                <div className="mt-6 flex justify-end">
                    <PrimaryButton onClick={reiniciar}>Seguir conectado</PrimaryButton>
                </div>
            </div>
        </Modal>
    );
}
