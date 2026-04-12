import { Link } from '@inertiajs/react';
import { useState } from 'react';
import InlineNotice from '../../Components/InlineNotice';
import { completeAuthentication } from '../../session';

export default function Login() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [mfaCode, setMfaCode] = useState('');
    const [mfaRecoveryCode, setMfaRecoveryCode] = useState('');
    const [mfaRequired, setMfaRequired] = useState(false);
    const [message, setMessage] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setMessage('');
        setIsSubmitting(true);

        try {
            const payload = {
                email,
                password,
            };

            if (mfaCode.trim() !== '') {
                payload.mfa_code = mfaCode.trim();
            }

            if (mfaRecoveryCode.trim() !== '') {
                payload.mfa_recovery_code = mfaRecoveryCode.trim();
            }

            const response = await window.axios.post('/api/v1/login', payload);
            const token = response?.data?.token;

            if (!token) {
                setMessage('Unable to start session.');
                return;
            }

            setMfaRequired(false);
            await completeAuthentication({ token });
        } catch (error) {
            const nextMfaRequired = Boolean(error?.response?.data?.mfa_required);
            setMfaRequired(nextMfaRequired);
            setMessage(error?.response?.data?.message || 'Login failed.');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
            <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Sign in</h1>
                <p className="mt-1 text-sm text-slate-600">Access your tenant workspace.</p>

                <form onSubmit={handleSubmit} className="mt-6 space-y-4">
                    <label className="block">
                        <span className="mb-1 block text-sm font-medium text-slate-700">Email</span>
                        <input
                            type="email"
                            required
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                        />
                    </label>

                    <label className="block">
                        <span className="mb-1 block text-sm font-medium text-slate-700">Password</span>
                        <input
                            type="password"
                            required
                            value={password}
                            onChange={(event) => setPassword(event.target.value)}
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                        />
                    </label>

                    {mfaRequired ? (
                        <>
                            <label className="block">
                                <span className="mb-1 block text-sm font-medium text-slate-700">Authenticator code</span>
                                <input
                                    type="text"
                                    inputMode="numeric"
                                    maxLength={6}
                                    value={mfaCode}
                                    onChange={(event) => setMfaCode(event.target.value)}
                                    className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                                />
                            </label>

                            <label className="block">
                                <span className="mb-1 block text-sm font-medium text-slate-700">Recovery code (optional)</span>
                                <input
                                    type="text"
                                    value={mfaRecoveryCode}
                                    onChange={(event) => setMfaRecoveryCode(event.target.value)}
                                    className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                                />
                            </label>
                        </>
                    ) : null}

                    <InlineNotice message={message} />

                    <button
                        type="submit"
                        disabled={isSubmitting}
                        className="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {isSubmitting ? 'Signing in...' : 'Sign in'}
                    </button>
                </form>

                <p className="mt-6 text-sm text-slate-600">
                    New here?{' '}
                    <Link href="/register" className="font-medium text-slate-900 underline decoration-slate-300">
                        Create an account
                    </Link>
                </p>
            </div>
        </main>
    );
}
