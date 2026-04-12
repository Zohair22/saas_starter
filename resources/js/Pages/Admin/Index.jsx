import { useEffect, useState } from 'react';
import InlineNotice from '../../Components/InlineNotice';
import AppLayout from '../../Layouts/AppLayout';
import useAppSession from '../../hooks/useAppSession';
import { setSession, setTenantContext } from '../../session';

export default function AdminPage() {
    const session = useAppSession();
    const { isLoading, user } = session;
    const isSuperAdmin = Boolean(user?.is_super_admin);

    const [metrics, setMetrics] = useState({ tenants: 0, users: 0 });
    const [tenantsByStatus, setTenantsByStatus] = useState([]);
    const [recentTenants, setRecentTenants] = useState([]);
    const [targetUserId, setTargetUserId] = useState('');
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');

    useEffect(() => {
        if (isLoading || !isSuperAdmin) return;

        window.axios.get('/api/v1/admin/dashboard').then((response) => {
            setMetrics(response?.data?.metrics ?? { tenants: 0, users: 0 });
            setTenantsByStatus(response?.data?.tenants_by_status ?? []);
            setRecentTenants(response?.data?.recent_tenants ?? []);
        }).catch((requestError) => {
            setError(requestError?.response?.data?.message ?? 'Unable to load admin dashboard.');
        });
    }, [isLoading, isSuperAdmin]);

    const handleImpersonate = async (event) => {
        event.preventDefault();
        setError('');
        setMessage('');

        try {
            const response = await window.axios.post(`/api/v1/admin/impersonate/${targetUserId}`);
            const token = response?.data?.auth?.token;
            const tenantId = response?.data?.auth?.tenant_id;

            if (!token) {
                setError('Impersonation token was not returned.');
                return;
            }

            setSession({ token });

            if (tenantId) {
                setTenantContext(tenantId);
            }

            window.location.href = '/app';
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to impersonate user.');
        }
    };

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    if (!isSuperAdmin) {
        return (
            <AppLayout title="Admin" session={session}>
                <section className="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-800">
                    You do not have access to this page.
                </section>
            </AppLayout>
        );
    }

    return (
        <AppLayout title="Admin" session={session}>
            <div className="space-y-4">
                <InlineNotice message={message} error={error} />

                <section className="grid gap-4 sm:grid-cols-2">
                    <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Tenants</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">{metrics.tenants}</p>
                    </article>
                    <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Users</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">{metrics.users}</p>
                    </article>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-slate-900">Tenants by billing status</h2>
                    <div className="mt-3 grid gap-2 sm:grid-cols-3">
                        {tenantsByStatus.map((row) => (
                            <div key={row.status} className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                <span className="font-semibold capitalize">{row.status}</span>: {row.total}
                            </div>
                        ))}
                    </div>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-slate-900">Recent tenants</h2>
                    <div className="mt-3 overflow-hidden rounded-lg border border-slate-200">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">ID</th>
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">Name</th>
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">Slug</th>
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {recentTenants.map((tenant) => (
                                    <tr key={tenant.id}>
                                        <td className="px-4 py-3 text-slate-700">{tenant.id}</td>
                                        <td className="px-4 py-3 text-slate-700">{tenant.name}</td>
                                        <td className="px-4 py-3 text-slate-700">{tenant.slug}</td>
                                        <td className="px-4 py-3 text-slate-700">{tenant.billing_status ?? 'none'}</td>
                                    </tr>
                                ))}
                                {recentTenants.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-8 text-center text-slate-500">No tenants found.</td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-amber-900">Impersonation</h2>
                    <p className="mt-1 text-sm text-amber-800">Switch session to a target user without exposing token output in the UI.</p>
                    <form onSubmit={handleImpersonate} className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <label className="text-sm text-amber-900">Target user ID
                            <input value={targetUserId} onChange={(e) => setTargetUserId(e.target.value)} className="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2" />
                        </label>
                        <button type="submit" className="rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">Switch session</button>
                    </form>
                </section>
            </div>
        </AppLayout>
    );
}
