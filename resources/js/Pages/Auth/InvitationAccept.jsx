import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import InlineNotice from '../../Components/InlineNotice';
import { completeAuthentication, getAuthToken } from '../../session';

const ROLE_LABELS = {
    admin: 'Admin',
    member: 'Member',
    viewer: 'Viewer',
};

function formatExpiry(dateString) {
    if (!dateString) {
        return null;
    }

    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

export default function InvitationAccept({ token }) {
    const [invitation, setInvitation] = useState(null);
    const [loadError, setLoadError] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [name, setName] = useState('');
    const [password, setPassword] = useState('');
    const [message, setMessage] = useState(null);
    const [messageType, setMessageType] = useState('error');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [accepted, setAccepted] = useState(false);
    const [showLoginPrompt, setShowLoginPrompt] = useState(false);
    const isAuthenticated = Boolean(getAuthToken());

    useEffect(() => {
        window.axios
            .get(`/api/v1/invitations/${token}`)
            .then((response) => {
                setInvitation(response.data?.data ?? response.data);
            })
            .catch((error) => {
                const apiMessage =
                    error?.response?.data?.errors?.token?.[0] ||
                    error?.response?.data?.message ||
                    'This invitation link is invalid or has expired.';

                setLoadError(apiMessage);
            })
            .finally(() => setIsLoading(false));
    }, [token]);

    const handleSubmit = async (event) => {
        event.preventDefault();

        setMessage(null);
        setIsSubmitting(true);

        try {
            await window.axios.post(`/api/v1/invitations/${token}/accept`, {
                name: isAuthenticated ? undefined : name.trim() || undefined,
                password: isAuthenticated ? undefined : password || undefined,
            });

            if (isAuthenticated) {
                window.location.href = '/app';
                return;
            }

            try {
                const loginResponse = await window.axios.post('/api/v1/login', {
                    email: invitation?.email,
                    password,
                });

                const authToken = loginResponse?.data?.token;

                if (authToken) {
                    await completeAuthentication({ token: authToken });
                    return;
                }
            } catch {
                // Acceptance already succeeded; if auto-login fails, show success with manual sign-in.
            }

            setMessageType('success');
            setMessage('Invitation accepted. Please sign in to continue.');
            setAccepted(true);
        } catch (error) {
            const apiMessage =
                error?.response?.data?.errors?.email?.[0] ||
                error?.response?.data?.errors?.name?.[0] ||
                error?.response?.data?.errors?.password?.[0] ||
                error?.response?.data?.message ||
                'Failed to accept invitation.';

            if (apiMessage.toLowerCase().includes('please login')) {
                setShowLoginPrompt(true);
            }

            setMessageType('error');
            setMessage(apiMessage);
        } finally {
            setIsSubmitting(false);
        }
    };

    if (isLoading) {
        return (
            <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
                <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                    <div className="space-y-3 animate-pulse">
                        <div className="h-5 w-2/3 rounded bg-slate-200" />
                        <div className="h-4 w-1/2 rounded bg-slate-200" />
                        <div className="mt-6 h-10 rounded bg-slate-100" />
                        <div className="h-10 rounded bg-slate-100" />
                        <div className="h-10 rounded bg-slate-900/10" />
                    </div>
                </div>
            </main>
        );
    }

    if (loadError) {
        return (
            <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
                <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm text-center">
                    <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-rose-50">
                        <svg className="h-6 w-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h1 className="text-lg font-semibold text-slate-900">Invitation not found</h1>
                    <p className="mt-2 text-sm text-slate-500">{loadError}</p>
                    <Link
                        href="/login"
                        className="mt-6 inline-block text-sm font-medium text-slate-700 underline decoration-slate-300"
                    >
                        Go to sign in
                    </Link>
                </div>
            </main>
        );
    }

    if (accepted) {
        return (
            <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
                <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm text-center">
                    <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50">
                        <svg className="h-6 w-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h1 className="text-lg font-semibold text-slate-900">You're in!</h1>
                    <p className="mt-2 text-sm text-slate-500">
                        Your invitation has been accepted. Sign in to access your workspace.
                    </p>
                    <Link
                        href="/login"
                        className="mt-6 inline-block rounded-md bg-slate-900 px-5 py-2 text-sm font-medium text-white hover:bg-slate-800 transition"
                    >
                        Sign in
                    </Link>
                </div>
            </main>
        );
    }

    const roleLabel = ROLE_LABELS[invitation?.role] ?? invitation?.role ?? 'Member';
    const tenantName = invitation?.tenant_name ?? invitation?.tenant?.name ?? 'a workspace';
    const inviterName = invitation?.invited_by_name ?? 'A team admin';
    const expiresAt = formatExpiry(invitation?.expires_at);

    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
            <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-100 px-6 py-5">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-white text-sm font-semibold">
                            {tenantName.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">Invitation to join</p>
                            <h1 className="text-base font-semibold text-slate-900">{tenantName}</h1>
                        </div>
                    </div>
                </div>

                <div className="px-6 py-4 space-y-2 border-b border-slate-100">
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-slate-500">Role</span>
                        <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                            {roleLabel}
                        </span>
                    </div>
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-slate-500">Invited to</span>
                        <span className="font-medium text-slate-900 truncate max-w-[60%] text-right">{invitation?.email}</span>
                    </div>
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-slate-500">Invited by</span>
                        <span className="font-medium text-slate-900">{inviterName}</span>
                    </div>
                    {expiresAt ? (
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-slate-500">Expires</span>
                            <span className="text-slate-700">{expiresAt}</span>
                        </div>
                    ) : null}
                </div>

                <div className="px-6 py-5">
                    {showLoginPrompt ? (
                        <div className="space-y-4 text-center">
                            <p className="text-sm text-slate-600">
                                An account already exists for <strong>{invitation?.email}</strong>. Sign in to accept
                                the invitation.
                            </p>
                            <Link
                                href="/login"
                                className="block w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white text-center transition hover:bg-slate-800"
                            >
                                Sign in to accept
                            </Link>
                        </div>
                    ) : (
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <p className="text-sm text-slate-600">
                                {isAuthenticated ? (
                                    <>Accept this invitation to join <strong>{tenantName}</strong>.</>
                                ) : (
                                    <>Create your account to join <strong>{tenantName}</strong>.</>
                                )}
                            </p>

                            {!isAuthenticated ? (
                                <>
                                    <label className="block">
                                        <span className="mb-1 block text-sm font-medium text-slate-700">Full name</span>
                                        <input
                                            type="text"
                                            required
                                            value={name}
                                            onChange={(event) => setName(event.target.value)}
                                            placeholder="Jane Smith"
                                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                                        />
                                    </label>

                                    <label className="block">
                                        <span className="mb-1 block text-sm font-medium text-slate-700">Password</span>
                                        <input
                                            type="password"
                                            required
                                            minLength={8}
                                            value={password}
                                            onChange={(event) => setPassword(event.target.value)}
                                            placeholder="At least 8 characters"
                                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                                        />
                                    </label>
                                </>
                            ) : null}

                            <InlineNotice message={message} type={messageType} />

                            <button
                                type="submit"
                                disabled={isSubmitting}
                                className="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {isSubmitting ? 'Accepting...' : 'Accept invitation'}
                            </button>
                        </form>
                    )}

                    {!showLoginPrompt ? (
                        <p className="mt-4 text-center text-sm text-slate-500">
                            Already have an account?{' '}
                            <Link href="/login" className="font-medium text-slate-900 underline decoration-slate-300">
                                Sign in
                            </Link>
                        </p>
                    ) : null}
                </div>
            </div>
        </main>
    );
}
