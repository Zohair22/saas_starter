import { useEffect, useMemo, useState } from 'react';
import InlineNotice from '../../Components/InlineNotice';
import AppLayout from '../../Layouts/AppLayout';
import useAppSession from '../../hooks/useAppSession';

export default function TenantSettingsPage() {
    const session = useAppSession();
    const { isLoading, tenantId, tenants = [], permissions = {} } = session;

    const currentTenant = useMemo(() => tenants.find((t) => String(t.id) === String(tenantId)) ?? null, [tenants, tenantId]);
    const canManageTenantSettings = Boolean(permissions.canManageTenantSettings);
    const isTenantOwner = Boolean(permissions.isTenantOwner);

    const [name, setName] = useState('');
    const [slug, setSlug] = useState('');
    const [newOwnerId, setNewOwnerId] = useState('');
    const [transferPassword, setTransferPassword] = useState('');
    const [deletePassword, setDeletePassword] = useState('');
    const [memberships, setMemberships] = useState([]);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [isSavingTenant, setIsSavingTenant] = useState(false);
    const [isTransferringOwnership, setIsTransferringOwnership] = useState(false);
    const [isDeletingTenant, setIsDeletingTenant] = useState(false);

    useEffect(() => {
        if (!currentTenant) {
            return;
        }

        setName(currentTenant.name ?? '');
        setSlug(currentTenant.slug ?? '');
    }, [currentTenant]);

    useEffect(() => {
        if (isLoading || !currentTenant) {
            return;
        }

        window.axios.get('/api/v1/memberships').then((response) => {
            setMemberships(response?.data?.data ?? []);
        }).catch(() => {
            setMemberships([]);
        });
    }, [isLoading, currentTenant]);

    const handleTenantUpdate = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');
        setIsSavingTenant(true);

        if (!currentTenant) return;

        try {
            await window.axios.patch(`/api/v1/tenants/${currentTenant.id}`, { name, slug });
            setMessage('Tenant settings updated.');
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to update tenant settings.');
        } finally {
            setIsSavingTenant(false);
        }
    };

    const handleTransferOwnership = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');
        setIsTransferringOwnership(true);

        if (!currentTenant) return;

        try {
            await window.axios.post(`/api/v1/tenants/${currentTenant.id}/transfer-ownership`, {
                new_owner_id: Number(newOwnerId),
                password: transferPassword,
            });
            setMessage('Ownership transferred successfully.');
            setTransferPassword('');
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to transfer ownership.');
        } finally {
            setIsTransferringOwnership(false);
        }
    };

    const handleDeleteTenant = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');
        setIsDeletingTenant(true);

        if (!currentTenant) return;

        try {
            await window.axios.delete(`/api/v1/tenants/${currentTenant.id}`, {
                data: { password: deletePassword },
            });
            window.location.href = '/app';
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to delete tenant.');
        } finally {
            setIsDeletingTenant(false);
        }
    };

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    if (!currentTenant) {
        return (
            <AppLayout title="Tenant Settings" session={session}>
                <p className="text-sm text-slate-600">No active tenant selected.</p>
            </AppLayout>
        );
    }

    return (
        <AppLayout title="Tenant Settings" session={session}>
            <div className="space-y-4">
                <InlineNotice message={message} error={error} />

                <section className="rounded-2xl border border-slate-200 bg-gradient-to-r from-indigo-900 to-indigo-700 p-5 text-white shadow-sm">
                    <p className="text-xs font-semibold tracking-wide uppercase text-indigo-100">Current workspace</p>
                    <h2 className="mt-1 text-xl font-semibold">{currentTenant.name}</h2>
                    <p className="mt-1 text-sm text-indigo-100">Slug: {currentTenant.slug}</p>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-slate-900">Workspace identity</h2>
                    <p className="mt-1 text-sm text-slate-600">Update the tenant name and slug.</p>
                    {canManageTenantSettings ? (
                        <form onSubmit={handleTenantUpdate} className="mt-4 grid gap-3 sm:grid-cols-2">
                            <label className="text-sm text-slate-700">Name
                                <input value={name} onChange={(e) => setName(e.target.value)} className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                            </label>
                            <label className="text-sm text-slate-700">Slug
                                <input value={slug} onChange={(e) => setSlug(e.target.value)} className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                            </label>
                            <div className="sm:col-span-2">
                                <button
                                    type="submit"
                                    disabled={isSavingTenant}
                                    className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {isSavingTenant ? 'Saving...' : 'Save tenant settings'}
                                </button>
                            </div>
                        </form>
                    ) : (
                        <p className="mt-4 text-sm text-slate-500">You do not have permission to edit tenant settings.</p>
                    )}
                </section>

                <section className="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-amber-900">Transfer ownership</h2>
                    <p className="mt-1 text-sm text-amber-800">Move workspace ownership to another existing member.</p>
                    {isTenantOwner ? (
                        <form onSubmit={handleTransferOwnership} className="mt-4 grid gap-3 sm:grid-cols-2">
                            <label className="text-sm text-amber-900">New owner
                                <select value={newOwnerId} onChange={(e) => setNewOwnerId(e.target.value)} className="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2">
                                    <option value="">Select member</option>
                                    {memberships.map((m) => (
                                        <option key={m.id} value={String(m.user_id)}>{m.user?.name ?? m.user?.email ?? `User ${m.user_id}`}</option>
                                    ))}
                                </select>
                            </label>
                            <label className="text-sm text-amber-900">Confirm password
                                <input type="password" value={transferPassword} onChange={(e) => setTransferPassword(e.target.value)} className="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2" />
                            </label>
                            <div className="sm:col-span-2">
                                <button
                                    type="submit"
                                    disabled={isTransferringOwnership}
                                    className="rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {isTransferringOwnership ? 'Transferring...' : 'Transfer ownership'}
                                </button>
                            </div>
                        </form>
                    ) : (
                        <p className="mt-4 text-sm text-amber-700">Only the current owner can transfer ownership.</p>
                    )}
                </section>

                <section className="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-rose-900">Danger zone</h2>
                    <p className="mt-1 text-sm text-rose-800">Delete this tenant and all related records.</p>
                    {isTenantOwner ? (
                        <form onSubmit={handleDeleteTenant} className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                            <label className="text-sm text-rose-900">Confirm password
                                <input type="password" value={deletePassword} onChange={(e) => setDeletePassword(e.target.value)} className="mt-1 w-full rounded-lg border border-rose-300 px-3 py-2" />
                            </label>
                            <button
                                type="submit"
                                disabled={isDeletingTenant}
                                className="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {isDeletingTenant ? 'Deleting...' : 'Delete tenant'}
                            </button>
                        </form>
                    ) : (
                        <p className="mt-4 text-sm text-rose-700">Only the current owner can delete this tenant.</p>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
