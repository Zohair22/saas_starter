import { useEffect, useState } from 'react';
import ConfirmDialog from '../../Components/ConfirmDialog';
import InlineNotice from '../../Components/InlineNotice';
import { CardSkeleton, TableSkeleton } from '../../Components/LoadingSkeleton';
import AppLayout from '../../Layouts/AppLayout';
import useAppSession from '../../hooks/useAppSession';

const formatLimit = (value) => {
    if (value === null || value === undefined) {
        return '-';
    }

    if (value === Number.MAX_SAFE_INTEGER || value > 1_000_000) {
        return 'Unlimited';
    }

    return String(value);
};

const formatUsage = (value, limit) => {
    if (value === null || value === undefined) {
        return '-';
    }

    if (limit === Number.MAX_SAFE_INTEGER || limit > 1_000_000) {
        return `${value}`;
    }

    return `${value} / ${limit}`;
};

const formatPercent = (value) => {
    if (value === null || value === undefined) {
        return 'N/A';
    }

    return `${value}%`;
};

export default function BillingIndex() {
    const session = useAppSession();
    const { isLoading } = session;
    const [isPageLoading, setIsPageLoading] = useState(true);
    const [plans, setPlans] = useState([]);
    const [subscription, setSubscription] = useState(null);
    const [usage, setUsage] = useState(null);
    const [planCode, setPlanCode] = useState('');
    const [paymentMethod, setPaymentMethod] = useState('');
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [pendingPaymentId, setPendingPaymentId] = useState('');
    const [showCancelModal, setShowCancelModal] = useState(false);
    const [isCancellingSubscription, setIsCancellingSubscription] = useState(false);
    const [historyMonths, setHistoryMonths] = useState(6);

    const loadBilling = async () => {
        setIsPageLoading(true);
        setError('');

        try {
            const [plansResponse, usageResponse] = await Promise.allSettled([
                window.axios.get('/api/v1/billing/plans'),
                window.axios.get('/api/v1/billing/usage', {
                    params: {
                        months: historyMonths,
                    },
                }),
            ]);

            if (plansResponse.status === 'fulfilled') {
                const rawPlans = plansResponse.value?.data?.plans;
                const fetchedPlans = Array.isArray(rawPlans)
                    ? rawPlans
                    : Array.isArray(rawPlans?.data)
                        ? rawPlans.data
                        : [];

                setPlans(fetchedPlans);
                setSubscription(plansResponse.value?.data?.subscription ?? null);

                if (fetchedPlans.length > 0) {
                    const selectedExists = fetchedPlans.some((plan) => plan.code === planCode);

                    if (!selectedExists) {
                        setPlanCode(fetchedPlans[0].code);
                    }
                }
            } else {
                setPlans([]);
                setSubscription(null);
                setError(plansResponse.reason?.response?.data?.message || 'Unable to load plans.');
            }

            if (usageResponse.status === 'fulfilled') {
                setUsage(usageResponse.value?.data ?? null);
            } else {
                setUsage(null);
                setError((currentError) => currentError || usageResponse.reason?.response?.data?.message || 'Unable to load usage data.');
            }
        } catch (requestError) {
            setError(requestError?.response?.data?.message || 'Unable to load billing data.');
        } finally {
            setIsPageLoading(false);
        }
    };

    useEffect(() => {
        if (!isLoading) {
            loadBilling();
        }
    }, [isLoading, historyMonths]);

    const handleSubscribe = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');
        setPendingPaymentId('');

        try {
            const response = await window.axios.post('/api/v1/billing/subscribe', {
                plan_code: planCode,
                payment_method: paymentMethod || null,
            });

            setMessage(response?.data?.message || 'Subscription created.');
            await loadBilling();
        } catch (requestError) {
            const status = requestError?.response?.status;
            if (status === 402) {
                setPendingPaymentId(String(requestError?.response?.data?.payment_id ?? ''));
            }
            setError(requestError?.response?.data?.message || 'Unable to subscribe.');
        }
    };

    const handleSwap = async () => {
        setMessage('');
        setError('');

        try {
            const response = await window.axios.patch('/api/v1/billing/subscription', {
                plan_code: planCode,
            });

            setMessage(response?.data?.message || 'Subscription swapped.');
            await loadBilling();
        } catch (requestError) {
            setError(requestError?.response?.data?.message || 'Unable to swap subscription.');
        }
    };

    const handleCancel = async () => {
        setIsCancellingSubscription(true);

        setMessage('');
        setError('');

        try {
            const response = await window.axios.delete('/api/v1/billing/subscription');
            setMessage(response?.data?.message || 'Subscription cancellation scheduled.');
            await loadBilling();
            setShowCancelModal(false);
        } catch (requestError) {
            setError(requestError?.response?.data?.message || 'Unable to cancel subscription.');
        } finally {
            setIsCancellingSubscription(false);
        }
    };

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    const currentPlanCode = usage?.plan?.code;
    const usageCards = [
        {
            key: 'max_users',
            title: 'Users',
            limit: usage?.limits?.max_users,
            used: usage?.usage?.max_users,
            utilization: usage?.utilization?.max_users,
        },
        {
            key: 'max_projects',
            title: 'Projects',
            limit: usage?.limits?.max_projects,
            used: usage?.usage?.max_projects,
            utilization: usage?.utilization?.max_projects,
        },
        {
            key: 'api_rate_limit',
            title: 'API Rate / min',
            limit: usage?.limits?.api_rate_limit,
            used: usage?.usage?.api_rate_limit,
            utilization: usage?.utilization?.api_rate_limit,
        },
    ];

    return (
        <AppLayout title="Billing" session={session}>
            {isPageLoading ? (
                <div className="space-y-4">
                    <div className="grid gap-4 lg:grid-cols-3">
                        <div className="lg:col-span-2">
                            <CardSkeleton />
                        </div>
                        <CardSkeleton />
                    </div>
                    <section className="grid gap-4 lg:grid-cols-3">
                        <CardSkeleton />
                        <CardSkeleton />
                        <CardSkeleton />
                    </section>
                    <TableSkeleton rows={5} />
                </div>
            ) : (
                <>
                    <div className="grid gap-4 lg:grid-cols-3">
                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <h2 className="text-base font-semibold text-slate-900">Plans and Subscription</h2>
                    <p className="mt-1 text-sm text-slate-600">Compare limits, select your target plan, and manage the full subscription lifecycle from one place.</p>

                    <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        {plans.map((plan) => {
                            const isCurrent = currentPlanCode === plan.code;

                            return (
                                <article
                                    key={plan.id}
                                    className={`rounded-lg border p-4 ${
                                        isCurrent ? 'border-slate-800 bg-slate-900 text-white' : 'border-slate-200 bg-slate-50'
                                    }`}
                                >
                                    <div className="flex items-center justify-between">
                                        <h3 className="text-sm font-semibold tracking-tight">{plan.name}</h3>
                                        {isCurrent ? (
                                            <span className="rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-semibold uppercase">
                                                Current
                                            </span>
                                        ) : null}
                                    </div>
                                    <p className={`mt-1 text-xs ${isCurrent ? 'text-slate-200' : 'text-slate-600'}`}>{plan.code}</p>

                                    <dl className="mt-3 space-y-1 text-xs">
                                        <div className="flex justify-between">
                                            <dt>Max users</dt>
                                            <dd className="font-medium">{formatLimit(plan.max_users)}</dd>
                                        </div>
                                        <div className="flex justify-between">
                                            <dt>Max projects</dt>
                                            <dd className="font-medium">{formatLimit(plan.max_projects)}</dd>
                                        </div>
                                        <div className="flex justify-between">
                                            <dt>API / min</dt>
                                            <dd className="font-medium">{formatLimit(plan.api_rate_limit)}</dd>
                                        </div>
                                    </dl>
                                </article>
                            );
                        })}
                    </div>

                    <form onSubmit={handleSubscribe} className="mt-4 space-y-4">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <label className="block">
                                <span className="mb-1 block text-sm font-medium text-slate-700">Target plan</span>
                                <select
                                    value={planCode}
                                    onChange={(event) => setPlanCode(event.target.value)}
                                    className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                >
                                    {plans.map((plan) => (
                                        <option key={plan.id} value={plan.code}>
                                            {plan.name} ({plan.code})
                                        </option>
                                    ))}
                                </select>
                            </label>

                            <label className="block">
                                <span className="mb-1 block text-sm font-medium text-slate-700">Payment method (optional)</span>
                                <input
                                    type="text"
                                    value={paymentMethod}
                                    onChange={(event) => setPaymentMethod(event.target.value)}
                                    placeholder="pm_xxx"
                                    className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                />
                            </label>
                        </div>

                        <div className="flex flex-wrap gap-2 pt-1">
                            <button type="submit" className="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                                Subscribe
                            </button>
                            <button
                                type="button"
                                onClick={handleSwap}
                                className="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                            >
                                Swap plan
                            </button>
                            <button
                                type="button"
                                onClick={() => setShowCancelModal(true)}
                                className="rounded-md border border-rose-200 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50"
                            >
                                Cancel subscription
                            </button>
                        </div>
                        <p className="text-xs text-slate-500">Tip: use Swap plan for in-place upgrades/downgrades. Use Cancel only when you intend to stop billing for this tenant.</p>
                    </form>

                    <InlineNotice type="success" message={message} className="mt-4" />
                    <InlineNotice message={error} className="mt-4" />
                    {pendingPaymentId ? (
                        <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                            <p className="text-xs font-semibold tracking-wide text-amber-700 uppercase">Payment action required</p>
                            <p className="mt-1 text-sm text-amber-900">Complete payment confirmation in Stripe using payment ID:</p>
                            <p className="mt-1 rounded bg-white px-2 py-1 text-xs font-mono text-amber-900">{pendingPaymentId}</p>
                        </div>
                    ) : null}
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-slate-900">Current Subscription</h2>

                    {subscription ? (
                        <dl className="mt-3 space-y-2 text-sm">
                            {Object.entries(subscription).map(([key, value]) => (
                                <div key={key} className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">{key}</dt>
                                    <dd className="text-right font-medium text-slate-800">{String(value ?? '-')}</dd>
                                </div>
                            ))}
                        </dl>
                    ) : (
                        <p className="mt-3 rounded-md bg-slate-50 px-3 py-2 text-sm text-slate-600">No active subscription payload.</p>
                    )}

                    <div className="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">How to read this</p>
                        <p className="mt-1 text-sm text-slate-600">These values are returned by billing endpoints and can help support/debug subscription state changes.</p>
                    </div>
                </section>
                    </div>

                    <section className="mt-4 grid gap-4 lg:grid-cols-3">
                        {usageCards.map((card) => (
                            <article key={card.key} className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                <p className="text-sm font-medium text-slate-500">{card.title}</p>
                                <p className="mt-2 text-xl font-semibold text-slate-900">{formatUsage(card.used, card.limit)}</p>
                                <p className="mt-1 text-xs text-slate-600">Utilization: {formatPercent(card.utilization)}</p>
                            </article>
                        ))}
                    </section>

                    <section className="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-base font-semibold text-slate-900">Usage History</h2>
                                <p className="mt-1 text-sm text-slate-600">Historical snapshots of key usage dimensions used for plan governance.</p>
                            </div>
                            <label className="flex items-center gap-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                Window
                                <select
                                    value={historyMonths}
                                    onChange={(event) => setHistoryMonths(Number(event.target.value))}
                                    className="rounded-md border border-slate-300 px-2 py-1.5 text-xs font-medium text-slate-700"
                                >
                                    <option value={3}>Last 3 months</option>
                                    <option value={6}>Last 6 months</option>
                                    <option value={12}>Last 12 months</option>
                                </select>
                            </label>
                        </div>

                <div className="mt-3 overflow-hidden rounded-lg border border-slate-200">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-slate-600">Period Start</th>
                                <th className="px-4 py-3 text-left font-medium text-slate-600">Users</th>
                                <th className="px-4 py-3 text-left font-medium text-slate-600">Projects</th>
                                <th className="px-4 py-3 text-left font-medium text-slate-600">API Requests</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {(usage?.history ?? []).map((item, index) => (
                                <tr key={`${item.period_start}-${index}`}>
                                    <td className="px-4 py-3 text-slate-700">{String(item.period_start ?? '-')}</td>
                                    <td className="px-4 py-3 text-slate-700">{String(item.users_count ?? '-')}</td>
                                    <td className="px-4 py-3 text-slate-700">{String(item.projects_count ?? '-')}</td>
                                    <td className="px-4 py-3 text-slate-700">{String(item.api_requests_count ?? '-')}</td>
                                </tr>
                            ))}
                            {(usage?.history ?? []).length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="px-4 py-8 text-center text-slate-500">
                                        No usage history yet.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                        </div>
                    </section>
                </>
            )}

            <ConfirmDialog
                open={showCancelModal}
                title="Cancel subscription"
                description="Access may be downgraded after the current billing period."
                confirmLabel="Cancel subscription"
                onCancel={() => setShowCancelModal(false)}
                onConfirm={handleCancel}
                isProcessing={isCancellingSubscription}
            />
        </AppLayout>
    );
}
