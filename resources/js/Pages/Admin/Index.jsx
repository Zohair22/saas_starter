import { useEffect, useState } from 'react';
import InlineNotice from '../../Components/InlineNotice';
import StepUpModal from '../../Components/StepUpModal';
import AppLayout from '../../Layouts/AppLayout';
import useAppSession from '../../hooks/useAppSession';
import { completeAuthentication } from '../../session';

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
    const [showStepUpModal, setShowStepUpModal] = useState(false);
    const [stepUpModalError, setStepUpModalError] = useState('');
    const [isStepUpSubmitting, setIsStepUpSubmitting] = useState(false);
    const [pendingStepUpAction, setPendingStepUpAction] = useState(null);

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

    const runImpersonation = async (stepUpPayload = {}) => {
        const response = await window.axios.post(`/api/v1/admin/impersonate/${targetUserId}`, stepUpPayload);
        const token = response?.data?.auth?.token;
        const tenantId = response?.data?.auth?.tenant_id;

        if (!token) {
            setError('Impersonation token was not returned.');
            return;
        }

        await completeAuthentication({ token, tenantId });
    };

    const handleImpersonate = async (event) => {
        event.preventDefault();
        setError('');
        setMessage('');

        try {
            await runImpersonation();
        } catch (requestError) {
            if (requestError?.response?.data?.step_up_required) {
                setStepUpModalError(requestError?.response?.data?.message ?? 'MFA verification is required.');
                setPendingStepUpAction(() => runImpersonation);
                setShowStepUpModal(true);
                return;
            }

            setError(requestError?.response?.data?.message ?? 'Unable to impersonate user.');
        }
    };

    const handleStepUpConfirm = async (credentials) => {
        if (!pendingStepUpAction) {
            setShowStepUpModal(false);
            return;
        }

        setIsStepUpSubmitting(true);

        try {
            await pendingStepUpAction(credentials);
            setShowStepUpModal(false);
            setPendingStepUpAction(null);
            setStepUpModalError('');
        } catch (requestError) {
            if (requestError?.response?.data?.step_up_required) {
                setStepUpModalError(requestError?.response?.data?.message ?? 'Invalid MFA credentials.');
                return;
            }

            setShowStepUpModal(false);
            setPendingStepUpAction(null);
            setError(requestError?.response?.data?.message ?? 'Unable to complete secure action.');
        } finally {
            setIsStepUpSubmitting(false);
        }
    };

    const handleStepUpCancel = () => {
        if (isStepUpSubmitting) {
            return;
        }

        setShowStepUpModal(false);
        setPendingStepUpAction(null);
        setStepUpModalError('');
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
                    <p className="mt-1 text-sm text-amber-800">Switch session to a target user without exposing token output in the UI. MFA step-up applies when enabled.</p>
                    <form onSubmit={handleImpersonate} className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <label className="text-sm text-amber-900">Target user ID
                            <input value={targetUserId} onChange={(e) => setTargetUserId(e.target.value)} className="mt-1 w-full rounded-lg border border-amber-300 px-3 py-2" />
                        </label>
                        <button type="submit" className="rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">Switch session</button>
                    </form>
                </section>
            </div>

            <StepUpModal
                open={showStepUpModal}
                title="Verify Before Impersonation"
                description="Confirm this admin action with your authenticator code or a recovery code."
                error={stepUpModalError}
                isProcessing={isStepUpSubmitting}
                onConfirm={handleStepUpConfirm}
                onCancel={handleStepUpCancel}
            />
        </AppLayout>
    );
}
