import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import client from '@/api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    const loadUser = useCallback(async () => {
        const token = localStorage.getItem('token');

        if (!token) {
            setUser(null);
            setLoading(false);
            return;
        }

        try {
            const { data } = await client.get('/me');
            setUser(data.user);
        } catch {
            localStorage.removeItem('token');
            setUser(null);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        loadUser();
    }, [loadUser]);

    const login = (token, userData) => {
        localStorage.setItem('token', token);
        setUser(userData);
    };

    const logout = async () => {
        try {
            await client.post('/logout');
        } finally {
            localStorage.removeItem('token');
            setUser(null);
        }
    };

    return (
        <AuthContext.Provider value={{ user, loading, login, logout }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const context = useContext(AuthContext);

    if (!context) {
        throw new Error('useAuth debe usarse dentro de <AuthProvider>');
    }

    return context;
}
