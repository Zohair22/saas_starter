import { Link } from '@inertiajs/react';
import { useState } from 'react';
import InlineNotice from '../../Components/InlineNotice';
import { setSession, setTenantContext } from '../../session';

export default function Login() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [message, setMessage] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setMessage('');
        setIsSubmitting(true);

        try {
            const response = await window.axios.post('/api/v1/login', { email, password });
            const token = response?.data?.token;

            if (!token) {
                setMessage('Unable to start session.');
                return;
            }

            setSession({ token });

            const tenantsResponse = await window.axios.get('/api/v1/tenants');
            const firstTenantId = tenantsResponse?.data?.data?.[0]?.id;

            if (firstTenantId) {
                setTenantContext(firstTenantId);
            }

            window.location.href = '/app';
        } catch (error) {
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
