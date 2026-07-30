import { useState } from 'react';
import client from '@/api/client';

/**
 * Replica la ergonomía de Inertia's useForm sobre axios: data/setData,
 * processing, errors (bag 422), y post/put que hacen la petición HTTP.
 */
export default function useForm(initialData) {
    const [data, setDataState] = useState(initialData);
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    const setData = (key, value) => {
        setDataState((prev) => ({ ...prev, [key]: value }));
    };

    const reset = (...keys) => {
        if (keys.length === 0) {
            setDataState(initialData);
            return;
        }

        setDataState((prev) => {
            const next = { ...prev };
            keys.forEach((key) => {
                next[key] = initialData[key];
            });
            return next;
        });
    };

    const submit = async (method, url, options = {}) => {
        setProcessing(true);
        setErrors({});

        try {
            const response = await client[method](url, data);
            options.onSuccess?.(response.data);
            return response.data;
        } catch (error) {
            if (error.response?.status === 422) {
                const bag = error.response.data.errors ?? {};
                setErrors(Object.fromEntries(Object.entries(bag).map(([k, v]) => [k, v[0]])));
            } else {
                options.onError?.(error);
            }
            throw error;
        } finally {
            setProcessing(false);
            options.onFinish?.();
        }
    };

    return {
        data,
        setData,
        errors,
        processing,
        reset,
        post: (url, options) => submit('post', url, options),
        put: (url, options) => submit('put', url, options),
    };
}
