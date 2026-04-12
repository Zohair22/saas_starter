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
    const [password, setPassword] = useState('');
    const [memberships, setMemberships] = useState([]);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');

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

        if (!currentTenant) return;

        try {
            await window.axios.patch(`/api/v1/tenants/${currentTenant.id}`, { name, slug });
            setMessage('Tenant settings updated.');
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to update tenant settings.');
        }
    };

    const handleTransferOwnership = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');

        if (!currentTenant) return;

        try {
            await window.axios.post(`/api/v1/tenants/${currentTenant.id}/transfer-ownership`, {
                new_owner_id: Number(newOwnerId),
                password,
            });
            setMessage('Ownership transferred successfully.');
            setPassword('');
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to transfer ownership.');
        }
    };

    const handleDeleteTenant = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');

        if (!currentTenant) return;

        try {
            await window.axios.delete(`/api/v1/tenants/${currentTenant.id}`, {
                data: { password },
            });
            window.location.href = '/app';
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to delete tenant.');
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
                                <button type="submit" className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save tenant settings</button>
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
                                <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} className="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2" />
                            </label>
                            <div className="sm:col-span-2">
                                <button type="submit" className="rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">Transfer ownership</button>
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
                                <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} className="mt-1 w-full rounded-lg border border-rose-300 px-3 py-2" />
                            </label>
                            <button type="submit" className="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800">Delete tenant</button>
                        </form>
                    ) : (
                        <p className="mt-4 text-sm text-rose-700">Only the current owner can delete this tenant.</p>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
