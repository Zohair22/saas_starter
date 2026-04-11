import { useEffect, useState } from 'react';
import ConfirmDialog from '../../Components/ConfirmDialog';
import InlineNotice from '../../Components/InlineNotice';
import { CardSkeleton, TableSkeleton } from '../../Components/LoadingSkeleton';
import AppLayout from '../../Layouts/AppLayout';
import useAppSession from '../../hooks/useAppSession';
import useToast from '../../hooks/useToast';
import { roleBadgeClass } from '../../utils/badgeClasses';

const roleOptions = ['owner', 'admin', 'member'];

export default function MembershipsIndex() {
    const session = useAppSession();
    const toast = useToast();
    const { isLoading } = session;
    const [isPageLoading, setIsPageLoading] = useState(true);
    const [memberships, setMemberships] = useState([]);
    const [currentMembership, setCurrentMembership] = useState(null);
    const [email, setEmail] = useState('');
    const [role, setRole] = useState('member');
    const [expiresAt, setExpiresAt] = useState('');
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [lastInvitation, setLastInvitation] = useState(null);
    const [isRevokingInvitation, setIsRevokingInvitation] = useState(false);
    const [membershipToRemove, setMembershipToRemove] = useState(null);
    const [isRemovingMembership, setIsRemovingMembership] = useState(false);

    const loadMemberships = async () => {
        setIsPageLoading(true);
        setError('');

        try {
            const response = await window.axios.get('/api/v1/memberships');
            setMemberships(response?.data?.data ?? []);
            setCurrentMembership(response?.data?.meta?.current_membership?.data ?? null);
        } catch (requestError) {
            const errorMessage = requestError?.response?.data?.message || 'Unable to load memberships.';
            setError(errorMessage);
        } finally {
            setIsPageLoading(false);
        }
    };

    useEffect(() => {
        if (!isLoading) {
            loadMemberships();
        }
    }, [isLoading]);

    const handleInvite = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');

        try {
            const response = await window.axios.post('/api/v1/invitations', {
                email,
                role,
                expires_at: expiresAt || null,
            });

            const invitation = response?.data?.data ?? null;
            const token = invitation?.token;
            setMessage(token ? `Invitation sent. Token: ${token}` : 'Invitation sent.');
            setLastInvitation(invitation);
            toast.success('Invitation sent successfully.');
            setEmail('');
            setRole('member');
            setExpiresAt('');
        } catch (requestError) {
            const errorMessage = requestError?.response?.data?.message || 'Unable to send invitation.';
            setError(errorMessage);
        }
    };

    const handleRevokeLastInvitation = async () => {
        if (!lastInvitation?.id) {
            return;
        }

        setIsRevokingInvitation(true);
        setError('');

        try {
            await window.axios.delete(`/api/v1/invitations/${lastInvitation.id}`);
            setMessage('Last invitation revoked.');
            setLastInvitation(null);
            toast.success('Invitation revoked.');
        } catch (requestError) {
            const errorMessage = requestError?.response?.data?.message || 'Unable to revoke invitation.';
            setError(errorMessage);
        } finally {
            setIsRevokingInvitation(false);
        }
    };

    const handleRemoveMembership = async (membershipId) => {
        setIsRemovingMembership(true);

        setMessage('');
        setError('');

        try {
            await window.axios.delete(`/api/v1/memberships/${membershipId}`);
            setMemberships((current) => current.filter((item) => item.id !== membershipId));
            setMessage('Membership removed.');
            toast.success('Membership removed.');
            setMembershipToRemove(null);
        } catch (requestError) {
            const errorMessage = requestError?.response?.data?.message || 'Unable to remove membership.';
            setError(errorMessage);
        } finally {
            setIsRemovingMembership(false);
        }
    };

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    return (
        <AppLayout title="Membership" session={session}>
            {isPageLoading ? (
                <div className="space-y-4">
                    <section className="grid gap-4 sm:grid-cols-3">
                        <CardSkeleton />
                        <CardSkeleton />
                        <CardSkeleton />
                    </section>
                    <TableSkeleton rows={6} />
                    <CardSkeleton />
                </div>
            ) : (
                <>
                    <section className="grid gap-4 sm:grid-cols-3">
                        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-sm font-medium text-slate-500">Current Role</p>
                            <p className="mt-2 text-base font-semibold text-slate-900 capitalize">{currentMembership?.role ?? 'Unknown'}</p>
                            {currentMembership?.role_flags ? (
                                <p className="mt-1 text-xs text-slate-500">
                                    Owner: {currentMembership.role_flags.is_owner ? 'Yes' : 'No'} | Admin: {currentMembership.role_flags.is_admin ? 'Yes' : 'No'}
                                </p>
                            ) : null}
                        </article>
                        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-sm font-medium text-slate-500">Members</p>
                            <p className="mt-2 text-2xl font-semibold text-slate-900">{memberships.length}</p>
                        </article>
                        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-sm font-medium text-slate-500">Invitations</p>
                            <p className="mt-2 text-sm text-slate-700">Invite users with role-scoped access and optional expiry windows.</p>
                        </article>
                    </section>

                    <section className="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-base font-semibold text-slate-900">Members</h2>
                        <p className="mt-1 text-sm text-slate-600">People who currently have access to this tenant and their effective role scope.</p>

                <div className="mt-3 overflow-hidden rounded-lg border border-slate-200">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-slate-600">Member</th>
                                <th className="px-4 py-3 text-left font-medium text-slate-600">Email</th>
                                <th className="px-4 py-3 text-left font-medium text-slate-600">Role</th>
                                <th className="px-4 py-3 text-left font-medium text-slate-600">Scope</th>
                                <th className="px-4 py-3 text-left font-medium text-slate-600">Joined</th>
                                <th className="px-4 py-3 text-right font-medium text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {memberships.map((membership) => (
                                <tr key={membership.id}>
                                    <td className="px-4 py-3 text-slate-700">{membership?.user?.name ?? `User #${membership.user_id}`}</td>
                                    <td className="px-4 py-3 text-slate-700">{membership?.user?.email ?? '-'}</td>
                                    <td className="px-4 py-3">
                                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize ${roleBadgeClass(membership.role)}`}>
                                            {membership.role}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">
                                        {membership.is_current_user ? 'You' : 'Member'}
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">{membership.created_at ?? '-'}</td>
                                    <td className="px-4 py-3 text-right">
                                        {!membership.is_current_user ? (
                                            <button
                                                type="button"
                                                onClick={() => setMembershipToRemove(membership.id)}
                                                className="rounded-md border border-rose-200 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50"
                                            >
                                                Remove
                                            </button>
                                        ) : (
                                            <span className="text-xs text-slate-500">Current user</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {memberships.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-slate-500">
                                        No memberships found.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                        </div>
                    </section>

                    <section className="mt-4 grid gap-4 lg:grid-cols-3">
                        <form onSubmit={handleInvite} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                            <h2 className="text-base font-semibold text-slate-900">Send Invitation</h2>
                            <p className="mt-1 text-sm text-slate-600">Invite a user by email, assign a role, and optionally set expiry to limit invite lifetime.</p>

                            <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                <label className="block">
                                    <span className="mb-1 block text-sm font-medium text-slate-700">Email</span>
                                    <input
                                        type="email"
                                        required
                                        value={email}
                                        onChange={(event) => setEmail(event.target.value)}
                                        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                    />
                                </label>

                                <label className="block">
                                    <span className="mb-1 block text-sm font-medium text-slate-700">Role</span>
                                    <select
                                        value={role}
                                        onChange={(event) => setRole(event.target.value)}
                                        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                    >
                                        {roleOptions.map((option) => (
                                            <option key={option} value={option}>
                                                {option}
                                            </option>
                                        ))}
                                    </select>
                                </label>

                                <label className="block sm:col-span-2">
                                    <span className="mb-1 block text-sm font-medium text-slate-700">Expires at (optional)</span>
                                    <input
                                        type="datetime-local"
                                        value={expiresAt}
                                        onChange={(event) => setExpiresAt(event.target.value)}
                                        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                    />
                                </label>

                                <div className="sm:col-span-2">
                                    <button type="submit" className="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                                        Send invitation
                                    </button>
                                </div>
                            </div>

                            <InlineNotice type="success" message={message} className="mt-4" />
                            <InlineNotice message={error} className="mt-4" />

                            {lastInvitation?.id ? (
                                <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                                    <p className="text-xs font-semibold tracking-wide text-amber-700 uppercase">Last invitation</p>
                                    <p className="mt-1 text-sm text-amber-900">{lastInvitation.email} • role {lastInvitation.role}</p>
                                    <p className="mt-1 text-xs text-amber-800">Token: {lastInvitation.token}</p>
                                    <div className="mt-2">
                                        <button
                                            type="button"
                                            onClick={handleRevokeLastInvitation}
                                            disabled={isRevokingInvitation}
                                            className="rounded-md border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            {isRevokingInvitation ? 'Revoking...' : 'Revoke invitation'}
                                        </button>
                                    </div>
                                </div>
                            ) : null}
                        </form>

                        <aside className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Role guidance</p>
                            <ul className="mt-2 space-y-2 text-sm text-slate-700">
                                <li>Owner: full tenant control and billing authority.</li>
                                <li>Admin: operational management without ownership transfer.</li>
                                <li>Member: standard contributor access.</li>
                            </ul>
                        </aside>
                    </section>

                    <section className="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-base font-semibold text-slate-900">Membership operations</h2>
                        <p className="mt-1 text-sm text-slate-600">Membership changes and invitation results update live after successful actions in this workspace.</p>
                    </section>
                </>
            )}

            <ConfirmDialog
                open={membershipToRemove !== null}
                title="Remove member"
                description="This user will lose access to the current tenant."
                confirmLabel="Remove member"
                onCancel={() => setMembershipToRemove(null)}
                onConfirm={() => handleRemoveMembership(membershipToRemove)}
                isProcessing={isRemovingMembership}
            />
        </AppLayout>
    );
}
