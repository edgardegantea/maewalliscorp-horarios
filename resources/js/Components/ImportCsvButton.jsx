import Icon from '@/Components/Icon';
import InputError from '@/Components/InputError';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Botón "Importar CSV" con un modal que sube el archivo por POST a `action`.
 * `columnas` es la lista de encabezados esperados, mostrada como ayuda.
 */
export default function ImportCsvButton({ action, columnas = [], nota }) {
    const [abierto, setAbierto] = useState(false);
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        archivo: null,
    });

    const cerrar = () => {
        setAbierto(false);
        reset();
        clearErrors();
    };

    const enviar = (e) => {
        e.preventDefault();
        post(action, {
            forceFormData: true,
            onSuccess: cerrar,
        });
    };

    return (
        <>
            <SecondaryButton onClick={() => setAbierto(true)}>
                <Icon name="upload" className="h-4 w-4" />
                Importar CSV
            </SecondaryButton>

            <Modal show={abierto} onClose={cerrar} maxWidth="md">
                <form onSubmit={enviar} className="p-6">
                    <h2 className="text-base font-semibold text-slate-900 dark:text-white">Importar desde CSV</h2>

                    {columnas.length > 0 && (
                        <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            El archivo debe incluir estas columnas en la primera fila:{' '}
                            <code className="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {columnas.join(', ')}
                            </code>
                        </p>
                    )}
                    {nota && <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">{nota}</p>}

                    <div className="mt-4">
                        <input
                            type="file"
                            accept=".csv,.txt,.xlsx"
                            onChange={(e) => setData('archivo', e.target.files[0] ?? null)}
                            className="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 dark:text-slate-300 dark:file:bg-indigo-500/10 dark:file:text-indigo-400 dark:hover:file:bg-indigo-500/20"
                        />
                        <InputError message={errors.archivo} className="mt-2" />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={cerrar}>
                            Cancelar
                        </SecondaryButton>
                        <PrimaryButton disabled={processing || !data.archivo}>
                            Importar
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>
        </>
    );
}
