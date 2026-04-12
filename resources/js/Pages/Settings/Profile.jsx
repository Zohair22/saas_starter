import { useEffect, useState } from 'react';
import InlineNotice from '../../Components/InlineNotice';
import AppLayout from '../../Layouts/AppLayout';
import useAppSession from '../../hooks/useAppSession';
import { clearSession } from '../../session';

export default function ProfileSettingsPage() {
    const session = useAppSession();
    const { isLoading, user } = session;

    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [currentPassword, setCurrentPassword] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [deletePassword, setDeletePassword] = useState('');
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');

    useEffect(() => {
        if (user) {
            setName(user.name ?? '');
            setEmail(user.email ?? '');
        }
    }, [user]);

    const handleProfileUpdate = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');

        try {
            const response = await window.axios.patch('/api/v1/profile', { name, email });
            const updated = response?.data?.data ?? response?.data ?? null;
            setName(updated?.name ?? name);
            setEmail(updated?.email ?? email);
            setMessage('Profile updated successfully.');
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to update profile.');
        }
    };

    const handlePasswordUpdate = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');

        try {
            await window.axios.patch('/api/v1/profile/password', {
                current_password: currentPassword,
                password,
                password_confirmation: passwordConfirmation,
            });

            setCurrentPassword('');
            setPassword('');
            setPasswordConfirmation('');
            setMessage('Password updated successfully.');
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to update password.');
        }
    };

    const handleDeleteAccount = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');

        try {
            await window.axios.delete('/api/v1/profile', {
                data: { password: deletePassword },
            });

            await clearSession();
            window.location.href = '/register';
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to delete account.');
        }
    };

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    return (
        <AppLayout title="Profile Settings" session={session}>
            <div className="space-y-4">
                <InlineNotice message={message} error={error} />

                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-slate-900">Profile</h2>
                    <p className="mt-1 text-sm text-slate-600">Update your display name and account email.</p>
                    <form onSubmit={handleProfileUpdate} className="mt-4 grid gap-3 sm:grid-cols-2">
                        <label className="text-sm text-slate-700">
                            Name
                            <input value={name} onChange={(e) => setName(e.target.value)} className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                        </label>
                        <label className="text-sm text-slate-700">
                            Email
                            <input value={email} onChange={(e) => setEmail(e.target.value)} className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                        </label>
                        <div className="sm:col-span-2">
                            <button type="submit" className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                                Save profile
                            </button>
                        </div>
                    </form>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-slate-900">Password</h2>
                    <p className="mt-1 text-sm text-slate-600">Use a strong password with at least 8 characters.</p>
                    <form onSubmit={handlePasswordUpdate} className="mt-4 grid gap-3 sm:grid-cols-2">
                        <label className="text-sm text-slate-700">
                            Current password
                            <input type="password" value={currentPassword} onChange={(e) => setCurrentPassword(e.target.value)} className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                        </label>
                        <label className="text-sm text-slate-700">
                            New password
                            <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                        </label>
                        <label className="text-sm text-slate-700 sm:col-span-2">
                            Confirm new password
                            <input type="password" value={passwordConfirmation} onChange={(e) => setPasswordConfirmation(e.target.value)} className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                        </label>
                        <div className="sm:col-span-2">
                            <button type="submit" className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                                Update password
                            </button>
                        </div>
                    </form>
                </section>

                <section className="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-rose-900">Danger zone</h2>
                    <p className="mt-1 text-sm text-rose-800">Delete your account permanently. This action cannot be undone.</p>
                    <form onSubmit={handleDeleteAccount} className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <label className="text-sm text-rose-900">
                            Confirm password
                            <input type="password" value={deletePassword} onChange={(e) => setDeletePassword(e.target.value)} className="mt-1 w-full rounded-lg border border-rose-300 px-3 py-2" />
                        </label>
                        <button type="submit" className="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800">
                            Delete account
                        </button>
                    </form>
                </section>
            </div>
        </AppLayout>
    );
}
