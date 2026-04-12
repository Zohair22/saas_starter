import { useEffect, useState } from 'react';
import ConfirmDialog from '../../Components/ConfirmDialog';
import InlineNotice from '../../Components/InlineNotice';
import { CardSkeleton, TableSkeleton } from '../../Components/LoadingSkeleton';
import StepUpModal from '../../Components/StepUpModal';
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

const formatDate = (value) => {
    if (!value) {
        return null;
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return null;
    }

    return parsed.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const utilizationColor = (pct) => {
    if (pct === null || pct === undefined) {
        return 'bg-slate-300';
    }

    if (pct >= 90) {
        return 'bg-rose-500';
    }

    if (pct >= 70) {
        return 'bg-amber-400';
    }

    return 'bg-emerald-500';
};

export default function BillingIndex() {
    const session = useAppSession();
    const { isLoading, permissions = {} } = session;
    const canManageBilling = Boolean(permissions.canManageBilling);
    const [isPageLoading, setIsPageLoading] = useState(true);
    const [plans, setPlans] = useState([]);
    const [subscription, setSubscription] = useState(null);
    const [usage, setUsage] = useState(null);
    const [invoices, setInvoices] = useState([]);
    const [paymentMethods, setPaymentMethods] = useState([]);
    const [defaultPaymentMethod, setDefaultPaymentMethod] = useState(null);
    const [planCode, setPlanCode] = useState('');
    const [paymentMethod, setPaymentMethod] = useState('');
    const [newPaymentMethodId, setNewPaymentMethodId] = useState('');
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [pendingPaymentId, setPendingPaymentId] = useState('');
    const [showCancelModal, setShowCancelModal] = useState(false);
    const [isCancellingSubscription, setIsCancellingSubscription] = useState(false);
    const [isAddingPaymentMethod, setIsAddingPaymentMethod] = useState(false);
    const [isSettingDefault, setIsSettingDefault] = useState(null);
    const [isRemovingPaymentMethod, setIsRemovingPaymentMethod] = useState(null);
    const [historyMonths, setHistoryMonths] = useState(6);
    const [showStepUpModal, setShowStepUpModal] = useState(false);
    const [stepUpModalError, setStepUpModalError] = useState('');
    const [isStepUpSubmitting, setIsStepUpSubmitting] = useState(false);
    const [pendingStepUpAction, setPendingStepUpAction] = useState(null);

    const openStepUpModal = (action, message = 'MFA step-up is required for this action.') => {
        setPendingStepUpAction(() => action);
        setStepUpModalError(message);
        setShowStepUpModal(true);
    };

    const loadBilling = async () => {
        setIsPageLoading(true);
        setError('');

        try {
            const [plansResponse, usageResponse, invoicesResponse, paymentMethodsResponse] = await Promise.allSettled([
                window.axios.get('/api/v1/billing/plans'),
                window.axios.get('/api/v1/billing/usage', { params: { months: historyMonths } }),
                window.axios.get('/api/v1/billing/invoices'),
                window.axios.get('/api/v1/billing/payment-methods'),
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

            if (invoicesResponse.status === 'fulfilled') {
                setInvoices(invoicesResponse.value?.data?.data ?? []);
            } else {
                setInvoices([]);
            }

            if (paymentMethodsResponse.status === 'fulfilled') {
                setPaymentMethods(paymentMethodsResponse.value?.data?.data ?? []);
                setDefaultPaymentMethod(paymentMethodsResponse.value?.data?.default_payment_method ?? null);
            } else {
                setPaymentMethods([]);
                setDefaultPaymentMethod(null);
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

        if (!canManageBilling) {
            setError('Only owner/admin can manage subscriptions for this tenant.');
            return;
        }

        const runSubscribe = async (stepUpPayload = {}) => {
            const response = await window.axios.post('/api/v1/billing/subscribe', {
                plan_code: planCode,
                payment_method: paymentMethod || null,
                ...stepUpPayload,
            });

            setMessage(response?.data?.message || 'Subscription created.');
            await loadBilling();
        };

        try {
            await runSubscribe();
        } catch (requestError) {
            const status = requestError?.response?.status;
            if (status === 402) {
                setPendingPaymentId(String(requestError?.response?.data?.payment_id ?? ''));
            }
            if (requestError?.response?.data?.step_up_required) {
                openStepUpModal(runSubscribe, requestError?.response?.data?.message);
                return;
            }

            setError(requestError?.response?.data?.message || 'Unable to subscribe.');
        }
    };

    const handleSwap = async () => {
        setMessage('');
        setError('');

        if (!canManageBilling) {
            setError('Only owner/admin can manage subscriptions for this tenant.');
            return;
        }

        const runSwap = async (stepUpPayload = {}) => {
            const response = await window.axios.patch('/api/v1/billing/subscription', {
                plan_code: planCode,
                ...stepUpPayload,
            });

            setMessage(response?.data?.message || 'Subscription swapped.');
            await loadBilling();
        };

        try {
            await runSwap();
        } catch (requestError) {
            if (requestError?.response?.data?.step_up_required) {
                openStepUpModal(runSwap, requestError?.response?.data?.message);
                return;
            }

            setError(requestError?.response?.data?.message || 'Unable to swap subscription.');
        }
    };

    const handleCancel = async () => {
        if (!canManageBilling) {
            setError('Only owner/admin can manage subscriptions for this tenant.');
            return;
        }

        setIsCancellingSubscription(true);

        setMessage('');
        setError('');

        const runCancel = async (stepUpPayload = {}) => {
            const response = await window.axios.delete('/api/v1/billing/subscription', {
                data: stepUpPayload,
            });

            setMessage(response?.data?.message || 'Subscription cancellation scheduled.');
            await loadBilling();
            setShowCancelModal(false);
        };

        try {
            await runCancel();
        } catch (requestError) {
            if (requestError?.response?.data?.step_up_required) {
                openStepUpModal(runCancel, requestError?.response?.data?.message);
                return;
            }

            setError(requestError?.response?.data?.message || 'Unable to cancel subscription.');
        } finally {
            setIsCancellingSubscription(false);
        }
    };

    const handleAddPaymentMethod = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');

        if (!canManageBilling) {
            setError('Only owner/admin can manage payment methods.');
            return;
        }

        const runAddPaymentMethod = async (stepUpPayload = {}) => {
            await window.axios.post('/api/v1/billing/payment-methods', {
                payment_method: newPaymentMethodId,
                ...stepUpPayload,
            });

            setNewPaymentMethodId('');
            setMessage('Payment method added.');
            await loadBilling();
        };

        setIsAddingPaymentMethod(true);

        try {
            await runAddPaymentMethod();
        } catch (requestError) {
            if (requestError?.response?.data?.step_up_required) {
                openStepUpModal(runAddPaymentMethod, requestError?.response?.data?.message);
                return;
            }

            setError(requestError?.response?.data?.message || 'Unable to add payment method.');
        } finally {
            setIsAddingPaymentMethod(false);
        }
    };

    const handleSetDefault = async (pmId) => {
        setMessage('');
        setError('');
        setIsSettingDefault(pmId);

        const runSetDefault = async (stepUpPayload = {}) => {
            await window.axios.patch('/api/v1/billing/payment-methods/default', {
                payment_method: pmId,
                ...stepUpPayload,
            });

            setMessage('Default payment method updated.');
            await loadBilling();
        };

        try {
            await runSetDefault();
        } catch (requestError) {
            if (requestError?.response?.data?.step_up_required) {
                openStepUpModal(runSetDefault, requestError?.response?.data?.message);
                return;
            }

            setError(requestError?.response?.data?.message || 'Unable to update default payment method.');
        } finally {
            setIsSettingDefault(null);
        }
    };

    const handleRemovePaymentMethod = async (pmId) => {
        setMessage('');
        setError('');
        setIsRemovingPaymentMethod(pmId);

        const runRemovePaymentMethod = async (stepUpPayload = {}) => {
            await window.axios.delete(`/api/v1/billing/payment-methods/${pmId}`, {
                data: stepUpPayload,
            });

            setPaymentMethods((current) => current.filter((pm) => pm.id !== pmId));
            setMessage('Payment method removed.');
        };

        try {
            await runRemovePaymentMethod();
        } catch (requestError) {
            if (requestError?.response?.data?.step_up_required) {
                openStepUpModal(runRemovePaymentMethod, requestError?.response?.data?.message);
                return;
            }

            setError(requestError?.response?.data?.message || 'Unable to remove payment method.');
        } finally {
            setIsRemovingPaymentMethod(null);
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
            setPendingStepUpAction(null);
            setShowStepUpModal(false);
            setStepUpModalError('');
        } catch (requestError) {
            if (requestError?.response?.data?.step_up_required) {
                setStepUpModalError(requestError?.response?.data?.message || 'Invalid MFA credentials.');
                return;
            }

            setShowStepUpModal(false);
            setPendingStepUpAction(null);
            setError(requestError?.response?.data?.message || 'Unable to complete secure action.');
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

    const currentPlanCode = usage?.plan?.code;
    const currentPlan = plans.find((plan) => plan.code === currentPlanCode) ?? null;
    const currentTenant = (session.tenants ?? []).find((tenant) => String(tenant.id) === String(session.tenantId));
    const tenantLifecycle = currentTenant?.lifecycle ?? null;
    const billingStatus = currentTenant?.billing_status ?? null;
    const subscriptionStatus = subscription?.stripe_status ?? subscription?.status ?? null;
    const hasActiveSubscription = Boolean(subscription);
    const isWriteLocked = Boolean(tenantLifecycle?.is_write_locked);
    const isGraceActive = Boolean(tenantLifecycle?.is_grace_active);
    const subscriptionEndsAt = formatDate(subscription?.ends_at);
    const subscriptionCreatedAt = formatDate(subscription?.created_at);
    const trialEndsAt = formatDate(subscription?.trial_ends_at);
    const isPaidPlan = Boolean(currentPlan?.is_paid);

    const statusBadgeClass = (() => {
        if (subscriptionStatus === 'active') {
            return 'bg-emerald-100 text-emerald-800';
        }

        if (subscriptionStatus === 'trialing') {
            return 'bg-sky-100 text-sky-800';
        }

        if (subscriptionStatus === 'past_due') {
            return 'bg-rose-100 text-rose-800';
        }

        if (subscriptionStatus === 'canceled') {
            return 'bg-slate-100 text-slate-600';
        }

        return 'bg-amber-100 text-amber-800';
    })();

    const usageDimensions = [
        {
            key: 'max_users',
            label: 'Team members',
            used: usage?.usage?.max_users ?? 0,
            limit: usage?.limits?.max_users,
            utilization: usage?.utilization?.max_users,
        },
        {
            key: 'max_projects',
            label: 'Projects',
            used: usage?.usage?.max_projects ?? 0,
            limit: usage?.limits?.max_projects,
            utilization: usage?.utilization?.max_projects,
        },
        {
            key: 'api_rate_limit',
            label: 'API requests / min',
            used: usage?.usage?.api_rate_limit ?? 0,
            limit: usage?.limits?.api_rate_limit,
            utilization: usage?.utilization?.api_rate_limit,
        },
    ];

    const subscriptionFields = subscription
        ? [
            { label: 'Subscription ID', value: subscription.stripe_id ?? subscription.id ?? '-' },
            { label: 'Status', value: subscriptionStatus ?? '-' },
            { label: 'Stripe price', value: subscription.stripe_price ?? '-' },
            { label: 'Quantity', value: String(subscription.quantity ?? 1) },
            { label: 'Trial ends', value: trialEndsAt ?? 'No trial' },
            { label: 'Ends / renews', value: subscriptionEndsAt ?? 'Auto-renews' },
            { label: 'Subscribed since', value: subscriptionCreatedAt ?? '-' },
        ]
        : [];

    return (
        <AppLayout title="Billing" session={session}>
            {isPageLoading ? (
                <div className="space-y-4">
                    <CardSkeleton />
                    <div className="grid gap-4 lg:grid-cols-3">
                        <CardSkeleton />
                        <CardSkeleton />
                        <CardSkeleton />
                    </div>
                    <CardSkeleton />
                    <TableSkeleton rows={5} />
                </div>
            ) : (
                <div className="space-y-4">

                    {/* ── 1. Plan Summary ── */}
                    <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Current plan</p>
                                <h2 className="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                                    {usage?.plan?.name ?? 'No plan selected'}
                                </h2>
                                <div className="mt-2 flex flex-wrap items-center gap-2">
                                    {hasActiveSubscription && subscriptionStatus ? (
                                        <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ${statusBadgeClass}`}>
                                            {subscriptionStatus}
                                        </span>
                                    ) : (
                                        <span className="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                                            No subscription
                                        </span>
                                    )}
                                    <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${isPaidPlan ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600'}`}>
                                        {isPaidPlan ? 'Paid plan' : 'Free plan'}
                                    </span>
                                    {isGraceActive ? (
                                        <span className="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                            Grace period active
                                        </span>
                                    ) : null}
                                </div>
                                <div className="mt-3 grid grid-cols-2 gap-x-6 gap-y-1 text-sm sm:grid-cols-3">
                                    <div>
                                        <span className="text-slate-500">Next charge</span>
                                        <p className="font-medium text-slate-900">
                                            {subscriptionEndsAt ? `Ends ${subscriptionEndsAt}` : hasActiveSubscription ? 'Auto-renews' : '—'}
                                        </p>
                                    </div>
                                    {trialEndsAt ? (
                                        <div>
                                            <span className="text-slate-500">Trial ends</span>
                                            <p className="font-medium text-slate-900">{trialEndsAt}</p>
                                        </div>
                                    ) : null}
                                    <div>
                                        <span className="text-slate-500">Member since</span>
                                        <p className="font-medium text-slate-900">{subscriptionCreatedAt ?? '—'}</p>
                                    </div>
                                </div>
                            </div>
                            {canManageBilling && hasActiveSubscription ? (
                                <div className="flex shrink-0 flex-wrap gap-2 sm:flex-col sm:items-end">
                                    <button
                                        type="button"
                                        onClick={() => document.getElementById('change-plan-section')?.scrollIntoView({ behavior: 'smooth' })}
                                        className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                                    >
                                        Change plan
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setShowCancelModal(true)}
                                        className="rounded-lg border border-rose-200 px-4 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-50"
                                    >
                                        Cancel subscription
                                    </button>
                                </div>
                            ) : null}
                        </div>
                    </section>

                    {/* ── 2. Billing Alerts ── */}
                    {(isWriteLocked || billingStatus === 'past_due' || pendingPaymentId) ? (
                        <div className="space-y-2">
                            {(isWriteLocked || billingStatus === 'past_due') && (
                                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 shadow-sm">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-rose-700">
                                        Action required — workspace writes blocked
                                    </p>
                                    <p className="mt-1 text-sm text-rose-900">
                                        Billing status is <strong>{billingStatus}</strong>. Recover your subscription below to restore full access.
                                    </p>
                                </div>
                            )}
                            {pendingPaymentId ? (
                                <div className="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-sm">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-amber-700">Payment action required</p>
                                    <p className="mt-1 text-sm text-amber-900">
                                        Complete payment confirmation in Stripe. Payment ID:
                                    </p>
                                    <p className="mt-1 inline-block rounded bg-white px-2 py-1 text-xs font-mono text-amber-900">{pendingPaymentId}</p>
                                </div>
                            ) : null}
                        </div>
                    ) : null}

                    <InlineNotice type="success" message={message} />
                    <InlineNotice message={error} />

                    {/* ── 3. Usage & Limits ── */}
                    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="mb-4 flex items-center justify-between">
                            <div>
                                <h2 className="text-base font-semibold text-slate-900">Usage &amp; Limits</h2>
                                <p className="mt-0.5 text-sm text-slate-600">Live usage against your current plan limits.</p>
                            </div>
                            <span className="text-xs text-slate-500">{usage?.plan?.name ?? '—'}</span>
                        </div>
                        <div className="space-y-4">
                            {usageDimensions.map((dim) => {
                                const pct = dim.utilization ?? 0;
                                const isUnlimited = dim.limit === null || dim.limit === undefined || dim.limit > 1_000_000;

                                return (
                                    <div key={dim.key}>
                                        <div className="mb-1 flex items-center justify-between text-sm">
                                            <span className="font-medium text-slate-700">{dim.label}</span>
                                            <span className="text-slate-500">
                                                {isUnlimited
                                                    ? `${dim.used} / Unlimited`
                                                    : `${dim.used} / ${formatLimit(dim.limit)}`}
                                                {!isUnlimited && dim.utilization !== null ? ` (${dim.utilization}%)` : ''}
                                            </span>
                                        </div>
                                        <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                            {!isUnlimited ? (
                                                <div
                                                    className={`h-full rounded-full transition-all ${utilizationColor(pct)}`}
                                                    style={{ width: `${Math.min(pct, 100)}%` }}
                                                />
                                            ) : (
                                                <div className="h-full w-full rounded-full bg-emerald-100" />
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </section>

                    {/* ── 4. Plans Comparison & Change Plan ── */}
                    <section id="change-plan-section" className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-base font-semibold text-slate-900">Plans</h2>
                        <p className="mt-1 text-sm text-slate-600">Compare plans and subscribe or switch at any time.</p>

                        <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            {plans.map((plan) => {
                                const isCurrent = currentPlanCode === plan.code;
                                const isTarget = planCode === plan.code && !isCurrent;

                                return (
                                    <button
                                        key={plan.id}
                                        type="button"
                                        onClick={() => setPlanCode(plan.code)}
                                        className={`rounded-xl border p-4 text-left transition ${
                                            isCurrent
                                                ? 'border-slate-800 bg-slate-900 text-white'
                                                : isTarget
                                                    ? 'border-indigo-400 bg-indigo-50 ring-1 ring-indigo-400'
                                                    : 'border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-white'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm font-semibold">{plan.name}</span>
                                            <span className={`rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase ${
                                                isCurrent ? 'bg-white/20 text-white' : plan.is_paid ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600'
                                            }`}>
                                                {isCurrent ? 'Current' : plan.is_paid ? 'Paid' : 'Free'}
                                            </span>
                                        </div>
                                        <dl className="mt-3 space-y-1 text-xs">
                                            <div className="flex justify-between">
                                                <dt className={isCurrent ? 'text-slate-300' : 'text-slate-500'}>Users</dt>
                                                <dd className="font-medium">{formatLimit(plan.max_users)}</dd>
                                            </div>
                                            <div className="flex justify-between">
                                                <dt className={isCurrent ? 'text-slate-300' : 'text-slate-500'}>Projects</dt>
                                                <dd className="font-medium">{formatLimit(plan.max_projects)}</dd>
                                            </div>
                                            <div className="flex justify-between">
                                                <dt className={isCurrent ? 'text-slate-300' : 'text-slate-500'}>API / min</dt>
                                                <dd className="font-medium">{formatLimit(plan.api_rate_limit)}</dd>
                                            </div>
                                        </dl>
                                    </button>
                                );
                            })}
                        </div>

                        {canManageBilling ? (
                            <form onSubmit={handleSubscribe} className="mt-5 space-y-4 border-t border-slate-100 pt-5">
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-slate-700">Selected plan</label>
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
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-slate-700">Payment method</label>
                                        <input
                                            type="text"
                                            value={paymentMethod}
                                            onChange={(event) => setPaymentMethod(event.target.value)}
                                            placeholder="pm_xxx (leave blank to use default)"
                                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        />
                                    </div>
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <button
                                        type="submit"
                                        className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                                    >
                                        {hasActiveSubscription ? 'Create new subscription' : 'Subscribe'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={handleSwap}
                                        disabled={!hasActiveSubscription}
                                        className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Swap to selected plan
                                    </button>
                                </div>
                                <p className="text-xs text-slate-500">
                                    Click a plan card to select it, then use <em>Swap to selected plan</em> for in-place upgrades / downgrades,
                                    or <em>Subscribe</em> to start a new subscription lifecycle.
                                </p>
                            </form>
                        ) : (
                            <p className="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                Plan details are visible to all members. Only owner/admin can change subscriptions.
                            </p>
                        )}
                    </section>

                    {/* ── 5. Payment Methods ── */}
                    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-base font-semibold text-slate-900">Payment Methods</h2>
                        <p className="mt-1 text-sm text-slate-600">Cards and payment methods attached to this tenant's Stripe customer.</p>

                        {paymentMethods.length > 0 ? (
                            <ul className="mt-4 divide-y divide-slate-100">
                                {paymentMethods.map((pm) => (
                                    <li key={pm.id} className="flex items-center justify-between gap-3 py-3">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-9 w-14 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                                                {pm.brand ?? pm.type}
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-slate-900">
                                                    {pm.brand ? `•••• ${pm.last4}` : pm.type}
                                                    {pm.is_default ? (
                                                        <span className="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-emerald-700">
                                                            Default
                                                        </span>
                                                    ) : null}
                                                </p>
                                                {pm.exp_month ? (
                                                    <p className="text-xs text-slate-500">
                                                        Expires {String(pm.exp_month).padStart(2, '0')} / {pm.exp_year}
                                                    </p>
                                                ) : null}
                                                {pm.name ? <p className="text-xs text-slate-400">{pm.name}</p> : null}
                                            </div>
                                        </div>
                                        {canManageBilling ? (
                                            <div className="flex shrink-0 gap-2">
                                                {!pm.is_default ? (
                                                    <button
                                                        type="button"
                                                        disabled={isSettingDefault === pm.id}
                                                        onClick={() => handleSetDefault(pm.id)}
                                                        className="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                                                    >
                                                        {isSettingDefault === pm.id ? 'Saving…' : 'Set default'}
                                                    </button>
                                                ) : null}
                                                <button
                                                    type="button"
                                                    disabled={isRemovingPaymentMethod === pm.id}
                                                    onClick={() => handleRemovePaymentMethod(pm.id)}
                                                    className="rounded-md border border-rose-200 px-2.5 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60"
                                                >
                                                    {isRemovingPaymentMethod === pm.id ? 'Removing…' : 'Remove'}
                                                </button>
                                            </div>
                                        ) : null}
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                {hasActiveSubscription
                                    ? 'No payment methods found on this Stripe customer.'
                                    : 'No Stripe customer exists yet. Subscribe to a paid plan to create one.'}
                            </p>
                        )}

                        {canManageBilling ? (
                            <form onSubmit={handleAddPaymentMethod} className="mt-5 border-t border-slate-100 pt-5">
                                <p className="mb-2 text-sm font-medium text-slate-700">Add a payment method</p>
                                <div className="flex gap-2">
                                    <input
                                        type="text"
                                        required
                                        value={newPaymentMethodId}
                                        onChange={(event) => setNewPaymentMethodId(event.target.value)}
                                        placeholder="pm_xxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                        className="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm"
                                    />
                                    <button
                                        type="submit"
                                        disabled={isAddingPaymentMethod}
                                        className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {isAddingPaymentMethod ? 'Adding…' : 'Add'}
                                    </button>
                                </div>
                                <p className="mt-1.5 text-xs text-slate-500">
                                    Obtain a <code className="rounded bg-slate-100 px-1">pm_</code> ID from your Stripe Dashboard under Customers → Payment methods.
                                </p>
                            </form>
                        ) : null}
                    </section>

                    {/* ── 6. Subscription Details ── */}
                    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-base font-semibold text-slate-900">Subscription Details</h2>
                        <p className="mt-1 text-sm text-slate-600">Raw subscription data returned by the billing system.</p>
                        {subscriptionFields.length > 0 ? (
                            <dl className="mt-4 grid gap-3 sm:grid-cols-2">
                                {subscriptionFields.map(({ label, value }) => (
                                    <div key={label} className="rounded-lg border border-slate-100 bg-slate-50 p-3">
                                        <dt className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</dt>
                                        <dd className="mt-1 truncate text-sm font-semibold text-slate-900">{value}</dd>
                                    </div>
                                ))}
                            </dl>
                        ) : (
                            <p className="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">No active subscription found.</p>
                        )}
                    </section>

                    {/* ── 7. Invoices ── */}
                    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-base font-semibold text-slate-900">Invoices</h2>
                        <p className="mt-1 text-sm text-slate-600">Stripe billing history for this tenant.</p>
                        <div className="mt-4 overflow-hidden rounded-lg border border-slate-200">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-medium text-slate-600">Invoice</th>
                                        <th className="px-4 py-3 text-left font-medium text-slate-600">Period</th>
                                        <th className="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                                        <th className="px-4 py-3 text-left font-medium text-slate-600">Total</th>
                                        <th className="px-4 py-3 text-right font-medium text-slate-600">Links</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {invoices.map((invoice) => (
                                        <tr key={invoice.id} className="hover:bg-slate-50">
                                            <td className="px-4 py-3 font-mono text-xs text-slate-700">{invoice.number ?? invoice.id}</td>
                                            <td className="px-4 py-3 text-slate-600">
                                                {invoice.period_start && invoice.period_end
                                                    ? `${invoice.period_start} – ${invoice.period_end}`
                                                    : invoice.created ?? '-'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize ${
                                                    invoice.status === 'paid'
                                                        ? 'bg-emerald-100 text-emerald-700'
                                                        : invoice.status === 'open'
                                                            ? 'bg-amber-100 text-amber-700'
                                                            : 'bg-slate-100 text-slate-600'
                                                }`}>
                                                    {invoice.status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 font-medium text-slate-900">{invoice.total}</td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex justify-end gap-2">
                                                    {invoice.hosted_invoice_url ? (
                                                        <a
                                                            href={invoice.hosted_invoice_url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                        >
                                                            View
                                                        </a>
                                                    ) : null}
                                                    {invoice.pdf_url ? (
                                                        <a
                                                            href={invoice.pdf_url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                        >
                                                            PDF
                                                        </a>
                                                    ) : null}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {invoices.length === 0 ? (
                                        <tr>
                                            <td colSpan={5} className="px-4 py-8 text-center text-slate-500">
                                                No invoices yet.
                                            </td>
                                        </tr>
                                    ) : null}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    {/* ── 8. Usage History ── */}
                    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-base font-semibold text-slate-900">Usage History</h2>
                                <p className="mt-0.5 text-sm text-slate-600">Periodic snapshots of resource usage used for plan governance.</p>
                            </div>
                            <label className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
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
                        <div className="mt-4 overflow-hidden rounded-lg border border-slate-200">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-medium text-slate-600">Period</th>
                                        <th className="px-4 py-3 text-left font-medium text-slate-600">Users</th>
                                        <th className="px-4 py-3 text-left font-medium text-slate-600">Projects</th>
                                        <th className="px-4 py-3 text-left font-medium text-slate-600">API requests</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {(usage?.history ?? []).map((item, index) => (
                                        <tr key={`${item.period_start}-${index}`} className="hover:bg-slate-50">
                                            <td className="px-4 py-3 font-medium text-slate-800">{String(item.period_start ?? '-')}</td>
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
                </div>
            )}

            <ConfirmDialog
                open={showCancelModal}
                title="Cancel subscription"
                description="Access may be downgraded after the current billing period ends."
                confirmLabel="Cancel subscription"
                onCancel={() => setShowCancelModal(false)}
                onConfirm={handleCancel}
                isProcessing={isCancellingSubscription}
            />

            <StepUpModal
                open={showStepUpModal}
                title="Verify Billing Action"
                description="For billing changes, confirm with your authenticator code or recovery code."
                error={stepUpModalError}
                isProcessing={isStepUpSubmitting}
                onConfirm={handleStepUpConfirm}
                onCancel={handleStepUpCancel}
            />
        </AppLayout>
    );
}
