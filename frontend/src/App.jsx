import { AuthProvider, useAuth } from '@/context/AuthContext';
import Login from '@/pages/Auth/Login';
import ForgotPassword from '@/pages/Auth/ForgotPassword';
import ResetPassword from '@/pages/Auth/ResetPassword';
import TwoFactorChallenge from '@/pages/Auth/TwoFactorChallenge';
import Dashboard from '@/pages/Dashboard';
import { Navigate, Route, BrowserRouter, Routes } from 'react-router-dom';

function ProtectedRoute({ children }) {
    const { user, loading } = useAuth();

    if (loading) {
        return null;
    }

    if (!user) {
        return <Navigate to="/login" replace />;
    }

    return children;
}

function GuestRoute({ children }) {
    const { user, loading } = useAuth();

    if (loading) {
        return null;
    }

    if (user) {
        return <Navigate to="/dashboard" replace />;
    }

    return children;
}

function AppRoutes() {
    return (
        <Routes>
            <Route path="/" element={<Navigate to="/dashboard" replace />} />
            <Route
                path="/login"
                element={
                    <GuestRoute>
                        <Login />
                    </GuestRoute>
                }
            />
            <Route
                path="/forgot-password"
                element={
                    <GuestRoute>
                        <ForgotPassword />
                    </GuestRoute>
                }
            />
            <Route
                path="/reset-password/:token"
                element={
                    <GuestRoute>
                        <ResetPassword />
                    </GuestRoute>
                }
            />
            <Route
                path="/two-factor-challenge"
                element={
                    <GuestRoute>
                        <TwoFactorChallenge />
                    </GuestRoute>
                }
            />
            <Route
                path="/dashboard"
                element={
                    <ProtectedRoute>
                        <Dashboard />
                    </ProtectedRoute>
                }
            />
        </Routes>
    );
}

export default function App() {
    return (
        <BrowserRouter>
            <AuthProvider>
                <AppRoutes />
            </AuthProvider>
        </BrowserRouter>
    );
}
