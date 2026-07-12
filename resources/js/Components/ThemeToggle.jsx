import Dropdown from '@/Components/Dropdown';
import Icon from '@/Components/Icon';
import { aplicarTema, esOscuroActivo, obtenerTema } from '@/theme';
import { useEffect, useState } from 'react';

const OPCIONES = [
    { valor: 'claro', label: 'Claro', icon: 'sun' },
    { valor: 'oscuro', label: 'Oscuro', icon: 'moon' },
    { valor: 'sistema', label: 'Sistema', icon: 'computerDesktop' },
];

export default function ThemeToggle() {
    const [tema, setTema] = useState('sistema');
    const [oscuro, setOscuro] = useState(false);

    useEffect(() => {
        setTema(obtenerTema());
        setOscuro(esOscuroActivo());
    }, []);

    const elegir = (valor) => {
        aplicarTema(valor);
        setTema(valor);
        setOscuro(esOscuroActivo());
    };

    return (
        <Dropdown align="right" width="40">
            <Dropdown.Trigger>
                <button
                    type="button"
                    className="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                    aria-label="Cambiar tema"
                >
                    <Icon name={oscuro ? 'moon' : 'sun'} className="h-5 w-5" />
                </button>
            </Dropdown.Trigger>

            <Dropdown.Content contentClasses="py-1 bg-white dark:bg-slate-800">
                {OPCIONES.map((opcion) => (
                    <button
                        key={opcion.valor}
                        type="button"
                        onClick={() => elegir(opcion.valor)}
                        className={`flex w-full items-center gap-2 px-4 py-2 text-start text-sm transition-colors ${
                            tema === opcion.valor
                                ? 'font-medium text-indigo-600 dark:text-indigo-400'
                                : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700'
                        }`}
                    >
                        <Icon name={opcion.icon} className="h-4 w-4" />
                        {opcion.label}
                    </button>
                ))}
            </Dropdown.Content>
        </Dropdown>
    );
}
