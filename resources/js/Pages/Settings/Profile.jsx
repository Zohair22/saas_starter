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
    const [mfaCode, setMfaCode] = useState('');
    const [mfaDisablePassword, setMfaDisablePassword] = useState('');
    const [mfaSetupSecret, setMfaSetupSecret] = useState('');
    const [mfaOtpauthUrl, setMfaOtpauthUrl] = useState('');
    const [mfaRecoveryCodes, setMfaRecoveryCodes] = useState([]);
    const [hasConfirmedRecoveryCodes, setHasConfirmedRecoveryCodes] = useState(false);
    const [isMfaEnabled, setIsMfaEnabled] = useState(false);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [isSavingProfile, setIsSavingProfile] = useState(false);
    const [isSavingPassword, setIsSavingPassword] = useState(false);
    const [isDeletingAccount, setIsDeletingAccount] = useState(false);
    const [isPreparingMfa, setIsPreparingMfa] = useState(false);
    const [isEnablingMfa, setIsEnablingMfa] = useState(false);
    const [isDisablingMfa, setIsDisablingMfa] = useState(false);

    useEffect(() => {
        if (user) {
            setName(user.name ?? '');
            setEmail(user.email ?? '');
            setIsMfaEnabled(Boolean(user.mfa_enabled));
        }
    }, [user]);

    const handleProfileUpdate = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');
        setIsSavingProfile(true);

        try {
            const response = await window.axios.patch('/api/v1/profile', { name, email });
            const updated = response?.data?.data ?? response?.data ?? null;
            setName(updated?.name ?? name);
            setEmail(updated?.email ?? email);
            setMessage('Profile updated successfully.');
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to update profile.');
        } finally {
            setIsSavingProfile(false);
        }
    };

    const handlePasswordUpdate = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');
        setIsSavingPassword(true);

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
        } finally {
            setIsSavingPassword(false);
        }
    };

    const handleDeleteAccount = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');
        setIsDeletingAccount(true);

        try {
            await window.axios.delete('/api/v1/profile', {
                data: { password: deletePassword },
            });

            await clearSession();
            window.location.href = '/register';
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to delete account.');
        } finally {
            setIsDeletingAccount(false);
        }
    };

    const downloadRecoveryCodes = () => {
        if (mfaRecoveryCodes.length === 0) {
            return;
        }

        const content = ['Your MFA recovery codes:', ...mfaRecoveryCodes].join('\n');
        const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'mfa-recovery-codes.txt';
        document.body.append(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    };

    const handlePrepareMfa = async () => {
        setMessage('');
        setError('');
        setIsPreparingMfa(true);

        try {
            const response = await window.axios.post('/api/v1/auth/mfa/setup');
            setMfaSetupSecret(response?.data?.secret ?? '');
            setMfaOtpauthUrl(response?.data?.otpauth_url ?? '');
            setMfaRecoveryCodes(response?.data?.recovery_codes ?? []);
            setHasConfirmedRecoveryCodes(false);
            setMfaCode('');
            setMessage('MFA setup initialized. Save your recovery codes before enabling.');
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to prepare MFA setup.');
        } finally {
            setIsPreparingMfa(false);
        }
    };

    const handleEnableMfa = async (event) => {
        event.preventDefault();

        if (!hasConfirmedRecoveryCodes) {
            setError('Confirm that recovery codes were safely stored before enabling MFA.');
            return;
        }

        setMessage('');
        setError('');
        setIsEnablingMfa(true);

        try {
            await window.axios.post('/api/v1/auth/mfa/enable', {
                code: mfaCode,
            });

            setMfaCode('');
            setMfaSetupSecret('');
            setMfaOtpauthUrl('');
            setMfaRecoveryCodes([]);
            setHasConfirmedRecoveryCodes(false);
            setIsMfaEnabled(true);
            setMessage('MFA enabled successfully.');
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to enable MFA.');
        } finally {
            setIsEnablingMfa(false);
        }
    };

    const handleDisableMfa = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');
        setIsDisablingMfa(true);

        try {
            await window.axios.post('/api/v1/auth/mfa/disable', {
                password: mfaDisablePassword,
            });

            setMfaDisablePassword('');
            setMfaSetupSecret('');
            setMfaOtpauthUrl('');
            setMfaRecoveryCodes([]);
            setHasConfirmedRecoveryCodes(false);
            setIsMfaEnabled(false);
            setMessage('MFA disabled successfully.');
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to disable MFA.');
        } finally {
            setIsDisablingMfa(false);
        }
    };

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    return (
        <AppLayout title="Profile Settings" session={session}>
            <div className="space-y-4">
                <InlineNotice message={message} error={error} />

                <section className="rounded-2xl border border-slate-200 bg-gradient-to-r from-slate-900 to-slate-700 p-5 text-white shadow-sm">
                    <p className="text-xs font-semibold tracking-wide uppercase text-slate-200">Account overview</p>
                    <h2 className="mt-1 text-xl font-semibold">{user?.name ?? 'User account'}</h2>
                    <p className="mt-1 text-sm text-slate-200">{user?.email ?? '-'}</p>
                </section>

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
                            <button
                                type="submit"
                                disabled={isSavingProfile}
                                className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {isSavingProfile ? 'Saving...' : 'Save profile'}
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
                            <button
                                type="submit"
                                disabled={isSavingPassword}
                                className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {isSavingPassword ? 'Updating...' : 'Update password'}
                            </button>
                        </div>
                    </form>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-slate-900">Multi-factor authentication</h2>
                    <p className="mt-1 text-sm text-slate-600">Add an authenticator app challenge for sensitive actions and login.</p>

                    {isMfaEnabled ? (
                        <div className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                            MFA is currently enabled for your account.
                        </div>
                    ) : (
                        <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                            MFA is currently disabled.
                        </div>
                    )}

                    {!isMfaEnabled ? (
                        <div className="mt-4 space-y-4">
                            <button
                                type="button"
                                onClick={handlePrepareMfa}
                                disabled={isPreparingMfa}
                                className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {isPreparingMfa ? 'Preparing...' : 'Start MFA setup'}
                            </button>

                            {mfaSetupSecret ? (
                                <div className="grid gap-4 rounded-xl border border-slate-200 p-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <p className="text-sm font-semibold text-slate-900">Manual secret</p>
                                        <code className="block rounded bg-slate-100 px-3 py-2 text-xs break-all text-slate-900">{mfaSetupSecret}</code>
                                        <p className="text-xs text-slate-500">Use this key in Google Authenticator, 1Password, or Authy.</p>
                                    </div>
                                    <div className="space-y-2">
                                        <p className="text-sm font-semibold text-slate-900">QR setup</p>
                                        {mfaOtpauthUrl ? (
                                            <img
                                                src={`https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(mfaOtpauthUrl)}`}
                                                alt="MFA setup QR code"
                                                className="h-44 w-44 rounded border border-slate-200"
                                            />
                                        ) : null}
                                        <p className="text-xs text-slate-500">Scan this QR code with your authenticator app.</p>
                                    </div>

                                    <div className="space-y-2 sm:col-span-2">
                                        <div className="flex items-center justify-between">
                                            <p className="text-sm font-semibold text-slate-900">Recovery codes</p>
                                            <button type="button" onClick={downloadRecoveryCodes} className="text-sm font-semibold text-slate-700 underline">Download</button>
                                        </div>
                                        <div className="grid gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 sm:grid-cols-2">
                                            {mfaRecoveryCodes.map((code) => (
                                                <code key={code} className="rounded bg-white px-2 py-1 text-xs text-slate-900">{code}</code>
                                            ))}
                                        </div>
                                        <label className="flex items-center gap-2 text-sm text-slate-700">
                                            <input
                                                type="checkbox"
                                                checked={hasConfirmedRecoveryCodes}
                                                onChange={(event) => setHasConfirmedRecoveryCodes(event.target.checked)}
                                            />
                                            I saved my recovery codes in a secure place.
                                        </label>
                                    </div>

                                    <form onSubmit={handleEnableMfa} className="space-y-3 sm:col-span-2">
                                        <label className="text-sm text-slate-700">
                                            Verification code
                                            <input
                                                type="text"
                                                inputMode="numeric"
                                                maxLength={6}
                                                value={mfaCode}
                                                onChange={(event) => setMfaCode(event.target.value)}
                                                className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
                                            />
                                        </label>
                                        <button
                                            type="submit"
                                            disabled={isEnablingMfa}
                                            className="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            {isEnablingMfa ? 'Enabling...' : 'Enable MFA'}
                                        </button>
                                    </form>
                                </div>
                            ) : null}
                        </div>
                    ) : (
                        <form onSubmit={handleDisableMfa} className="mt-4 space-y-3">
                            <label className="text-sm text-slate-700">
                                Confirm password to disable MFA
                                <input
                                    type="password"
                                    value={mfaDisablePassword}
                                    onChange={(event) => setMfaDisablePassword(event.target.value)}
                                    className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
                                />
                            </label>
                            <button
                                type="submit"
                                disabled={isDisablingMfa}
                                className="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {isDisablingMfa ? 'Disabling...' : 'Disable MFA'}
                            </button>
                        </form>
                    )}
                </section>

                <section className="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-rose-900">Danger zone</h2>
                    <p className="mt-1 text-sm text-rose-800">Delete your account permanently. This action cannot be undone.</p>
                    <form onSubmit={handleDeleteAccount} className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <label className="text-sm text-rose-900">
                            Confirm password
                            <input type="password" value={deletePassword} onChange={(e) => setDeletePassword(e.target.value)} className="mt-1 w-full rounded-lg border border-rose-300 px-3 py-2" />
                        </label>
                        <button
                            type="submit"
                            disabled={isDeletingAccount}
                            className="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {isDeletingAccount ? 'Deleting...' : 'Delete account'}
                        </button>
                    </form>
                </section>
            </div>
        </AppLayout>
    );
}
