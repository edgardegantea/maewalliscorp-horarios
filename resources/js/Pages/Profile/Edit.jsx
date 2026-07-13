import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import TwoFactorAuthenticationForm from './Partials/TwoFactorAuthenticationForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status, twoFactorEnabled, twoFactorPendiente }) {
    const user = usePage().props.auth.user;
    const puedeUsarDosFactores = user.role === 'admin' || user.role === 'coordinador';

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Mi perfil</h2>}>
            <Head title="Mi perfil" />

            <div className="space-y-6">
                <PageHeader title="Mi perfil" description="Actualiza tu información de cuenta y contraseña." />

                <Card>
                    <UpdateProfileInformationForm mustVerifyEmail={mustVerifyEmail} status={status} />
                </Card>

                <Card>
                    <UpdatePasswordForm />
                </Card>

                {puedeUsarDosFactores && (
                    <Card>
                        <TwoFactorAuthenticationForm twoFactorEnabled={twoFactorEnabled} twoFactorPendiente={twoFactorPendiente} />
                    </Card>
                )}

                <Card>
                    <DeleteUserForm />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
