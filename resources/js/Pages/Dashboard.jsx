import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';
import useAppSession from '../hooks/useAppSession';
import { roleBadgeClass } from '../utils/badgeClasses';

// Helpers

const formatLimit = (value) => {
    if (value === null || value === undefined) return '-';
    if (value === Number.MAX_SAFE_INTEGER || value > 1_000_000) return '\u221e';
    return String(value);
};

const utilColor = (pct) => {
    if (pct === null || pct === undefined) return 'bg-emerald-400';
    if (pct >= 90) return 'bg-rose-500';
    if (pct >= 70) return 'bg-amber-400';
    return 'bg-emerald-400';
};

const timeAgo = (dateString) => {
    if (!dateString) return '';
    const diff = Math.floor((Date.now() - new Date(dateString).getTime()) / 1000);
    if (diff < 60) return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
};

// Sub-components

function KpiCard({ label, value, sub, utilization, href, loading }) {
    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:p-5">
            <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">{label}</p>
            <p className={`mt-2 text-2xl font-bold text-slate-900 ${loading ? 'animate-pulse text-slate-200' : ''}`}>
                {value}
            </p>
            {sub && <p className="mt-0.5 text-xs text-slate-500">{sub}</p>}
            {utilization != null && (
                <div className="mt-3">
                    <div className="h-1.5 w-full rounded-full bg-slate-100">
                        <div
                            className={`h-1.5 rounded-full transition-all ${utilColor(utilization)}`}
                            style={{ width: `${Math.min(utilization, 100)}%` }}
                        />
                    </div>
                    <p className="mt-1 text-xs text-slate-400">{utilization}% used</p>
                </div>
            )}
            {href && (
                <Link href={href} className="mt-3 inline-block text-xs font-semibold text-slate-500 underline hover:text-slate-900">
                    View &rarr;
                </Link>
            )}
        </article>
    );
}

function UsageBarChart({ label, data, max, color }) {
    return (
        <div>
            <div className="mb-1 flex items-center justify-between">
                <p className="text-xs font-medium text-slate-600">{label}</p>
                <p className="text-xs text-slate-400">last {data.length} months</p>
            </div>
            <div className="flex h-10 items-end gap-1">
                {data.map((val, i) => (
                    <div
                        key={i}
                        title={String(val)}
                        className={`flex-1 rounded-t ${color} opacity-80 transition-all hover:opacity-100`}
                        style={{ height: `${Math.max((val / max) * 100, 4)}%` }}
                    />
                ))}
            </div>
        </div>
    );
}

function UsageRow({ label, usage, limit }) {
    const pct = limit > 0 && limit < 1_000_000 ? Math.round((usage / limit) * 100) : null;
    return (
        <div>
            <div className="flex justify-between text-xs text-slate-600">
                <span>{label}</span>
                <span>{usage} / {formatLimit(limit)}</span>
            </div>
            {pct != null && (
                <div className="mt-0.5 h-1 w-full rounded-full bg-slate-100">
                    <div className={`h-1 rounded-full ${utilColor(pct)}`} style={{ width: `${Math.min(pct, 100)}%` }} />
                </div>
            )}
        </div>
    );
}

function OnboardingChecklist({
    hasProjects,
    hasMembers,
    hasSubscription,
    hasCreatedTask,
    hasCompletedTask,
    hasReviewedLogs,
    canManageProjects,
    canManageMemberships,
    canManageBilling,
}) {
    const steps = [
        {
            allowed: canManageProjects,
            done: hasProjects,
            label: 'Create your first project',
            description: "Set up a project to organise your team's work.",
            href: '/app/projects/create',
            cta: 'Create project',
        },
        {
            allowed: canManageMemberships,
            done: hasMembers,
            label: 'Invite a team member',
            description: 'Collaboration is better with your team on board.',
            href: '/app/memberships',
            cta: 'Invite member',
        },
        {
            allowed: canManageBilling,
            done: hasSubscription,
            label: 'Choose a subscription plan',
            description: 'Unlock higher limits and features with a paid plan.',
            href: '/app/billing',
            cta: 'View plans',
        },
        {
            allowed: canManageProjects,
            done: hasCreatedTask,
            label: 'Create first task',
            description: 'Add your first task to kick off execution.',
            href: '/app/projects',
            cta: 'Create task',
        },
        {
            allowed: canManageProjects,
            done: hasCompletedTask,
            label: 'Complete first task',
            description: 'Close one task to unlock momentum.',
            href: '/app/projects',
            cta: 'Update task',
        },
        {
            allowed: true,
            done: hasReviewedLogs,
            label: 'Review activity logs',
            description: 'Use timeline data to keep projects on track.',
            href: '/app/logs?tab=activity',
            cta: 'Open logs',
        },
    ].filter((step) => step.allowed);

    const completedCount = steps.filter((s) => s.done).length;
    if (steps.length === 0 || completedCount === steps.length) return null;

    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div className="flex items-center justify-between">
                <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Getting started</p>
                <span className="text-xs text-slate-400">{completedCount} / {steps.length} complete</span>
            </div>
            <div className="mt-2 h-1.5 w-full rounded-full bg-slate-100">
                <div
                    className="h-1.5 rounded-full bg-emerald-400 transition-all"
                    style={{ width: `${(completedCount / steps.length) * 100}%` }}
                />
            </div>
            <ul className="mt-4 space-y-2">
                {steps.map((step, i) => (
                    <li
                        key={i}
                        className={`flex items-start gap-3 rounded-xl border p-3 ${
                            step.done ? 'border-emerald-100 bg-emerald-50' : 'border-slate-200 bg-slate-50/80'
                        }`}
                    >
                        <span
                            className={`mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-bold ${
                                step.done ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500'
                            }`}
                        >
                            {step.done ? '\u2713' : i + 1}
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className={`text-sm font-semibold ${step.done ? 'text-slate-400 line-through' : 'text-slate-900'}`}>
                                {step.label}
                            </p>
                            {!step.done && <p className="mt-0.5 text-xs text-slate-500">{step.description}</p>}
                        </div>
                        {!step.done && (
                            <Link href={step.href} className="shrink-0 text-xs font-semibold text-slate-700 underline hover:text-slate-900">
                                {step.cta}
                            </Link>
                        )}
                    </li>
                ))}
            </ul>
        </article>
    );
}

function MyWork({ tasks, loading }) {
    const statusBadge = (status) => {
        if (status === 'in_progress') return 'bg-sky-100 text-sky-700';
        return 'bg-slate-100 text-slate-600';
    };
    const priorityBadge = (priority) => {
        if (priority === 'high') return 'bg-rose-100 text-rose-700';
        if (priority === 'medium') return 'bg-amber-100 text-amber-700';
        return 'bg-slate-100 text-slate-500';
    };
    const isOverdue = (dueAt) => dueAt && new Date(dueAt) < new Date();

    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div className="flex items-center justify-between">
                <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">My work</p>
                <span className="text-xs text-slate-400">assigned to you</span>
            </div>
            {loading ? (
                <div className="mt-3 space-y-2">
                    {Array.from({ length: 3 }).map((_, i) => (
                        <div key={i} className="h-12 animate-pulse rounded-lg bg-slate-100" />
                    ))}
                </div>
            ) : tasks.length === 0 ? (
                <p className="mt-4 text-sm text-slate-500">No open tasks assigned to you.</p>
            ) : (
                <ul className="mt-3 space-y-2">
                    {tasks.map((task) => (
                        <li
                            key={task.id}
                            className={`rounded-lg border p-3 text-sm ${
                                isOverdue(task.due_at)
                                    ? 'border-rose-200 bg-rose-50'
                                    : 'border-slate-200 bg-slate-50/60'
                            }`}
                        >
                            <div className="flex items-start justify-between gap-2">
                                <p className="truncate font-medium leading-snug text-slate-900">{task.title}</p>
                                <div className="flex shrink-0 gap-1">
                                    <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${statusBadge(task.status)}`}>
                                        {task.status === 'in_progress' ? 'In progress' : 'Open'}
                                    </span>
                                    <span className={`rounded-full px-2 py-0.5 text-xs font-semibold capitalize ${priorityBadge(task.priority)}`}>
                                        {task.priority}
                                    </span>
                                </div>
                            </div>
                            <div className="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                <span>{task.projectName}</span>
                                {task.due_at && (
                                    <>
                                        <span>&middot;</span>
                                        <span className={isOverdue(task.due_at) ? 'font-medium text-rose-600' : ''}>
                                            Due {new Date(task.due_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                                        </span>
                                    </>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </article>
    );
}

// Page

function ProjectActivityChart({ weeks, maxVal, loading }) {
    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div className="flex items-center justify-between">
                <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Project activity</p>
                <div className="flex items-center gap-3 text-xs text-slate-400">
                    <span className="flex items-center gap-1.5">
                        <span className="inline-block h-2 w-2 rounded-full bg-sky-400" />
                        Created
                    </span>
                    <span className="flex items-center gap-1.5">
                        <span className="inline-block h-2 w-2 rounded-full bg-emerald-400" />
                        Completed
                    </span>
                </div>
            </div>
            {loading ? (
                <div className="mt-4 h-20 animate-pulse rounded-lg bg-slate-100" />
            ) : (
                <>
                    <div className="mt-4 flex h-20 items-end justify-between gap-2">
                        {weeks.map((w, i) => (
                            <div key={i} className="flex flex-1 flex-col items-center gap-1">
                                <div className="flex w-full items-end justify-center gap-0.5" style={{ height: '64px' }}>
                                    <div
                                        title={`Created: ${w.created}`}
                                        className="flex-1 rounded-t bg-sky-400 opacity-80 transition-all hover:opacity-100"
                                        style={{ height: `${Math.max((w.created / maxVal) * 100, 4)}%` }}
                                    />
                                    <div
                                        title={`Completed: ${w.completed}`}
                                        className="flex-1 rounded-t bg-emerald-400 opacity-80 transition-all hover:opacity-100"
                                        style={{ height: `${Math.max((w.completed / maxVal) * 100, 4)}%` }}
                                    />
                                </div>
                                <p className="text-[10px] text-slate-400">{w.label}</p>
                            </div>
                        ))}
                    </div>
                    {weeks.every((w) => w.created === 0 && w.completed === 0) && (
                        <p className="mt-2 text-center text-xs text-slate-400">No task activity in the last 4 weeks.</p>
                    )}
                </>
            )}
        </article>
    );
}

function TeamProductivityChart({ members, maxVal, loading }) {
    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div className="flex items-center justify-between">
                <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Team productivity</p>
                <span className="text-xs text-slate-400">tasks completed</span>
            </div>
            {loading ? (
                <div className="mt-3 space-y-2">
                    {Array.from({ length: 3 }).map((_, i) => (
                        <div key={i} className="h-7 animate-pulse rounded-lg bg-slate-100" />
                    ))}
                </div>
            ) : members.length === 0 ? (
                <p className="mt-4 text-sm text-slate-500">No completed tasks yet.</p>
            ) : (
                <ul className="mt-3 space-y-2.5">
                    {members.map((m, i) => (
                        <li key={i} className="flex items-center gap-2">
                            <span className="w-24 shrink-0 truncate text-xs font-medium text-slate-700" title={m.name}>
                                {m.name}
                            </span>
                            <div className="flex-1 rounded-full bg-slate-100" style={{ height: '8px' }}>
                                <div
                                    className="h-full rounded-full bg-violet-400 transition-all"
                                    style={{ width: `${Math.max((m.count / maxVal) * 100, 4)}%` }}
                                />
                            </div>
                            <span className="w-5 shrink-0 text-right text-xs font-semibold text-slate-600">{m.count}</span>
                        </li>
                    ))}
                </ul>
            )}
        </article>
    );
}

function RevenueChart({ usage, billing, loading }) {
    const historyData = usage?.history ?? [];
    const apiCounts = historyData.map((h) => h.api_requests_count ?? 0);
    const maxApi = Math.max(...apiCounts, 1);
    const planLabel = usage?.plan?.name ?? (billing?.subscription ? 'Active plan' : 'Free tier');

    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div className="flex items-center justify-between">
                <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Revenue overview</p>
                <span
                    className={`rounded-full px-2 py-0.5 text-xs font-semibold ${
                        billing?.subscription ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'
                    }`}
                >
                    {billing?.subscription ? 'Paid' : 'Free'}
                </span>
            </div>
            <p className={`mt-2 text-lg font-bold text-slate-900 ${loading ? 'animate-pulse text-slate-200' : ''}`}>
                {loading ? '\u2014' : planLabel}
            </p>
            <p className="mt-0.5 text-xs text-slate-500">
                {billing?.subscription
                    ? 'Paid subscription active'
                    : 'No paid subscription \u2014 upgrade to unlock full revenue tracking.'}
            </p>
            {loading ? (
                <div className="mt-4 h-10 animate-pulse rounded-lg bg-slate-100" />
            ) : historyData.length > 0 ? (
                <div className="mt-4">
                    <p className="mb-1 text-[10px] text-slate-400">API activity — 6 months</p>
                    <div className="flex h-10 items-end gap-1">
                        {apiCounts.map((val, i) => (
                            <div
                                key={i}
                                title={`${val} requests`}
                                className="flex-1 rounded-t bg-violet-400 opacity-70 transition-all hover:opacity-100"
                                style={{ height: `${Math.max((val / maxApi) * 100, 4)}%` }}
                            />
                        ))}
                    </div>
                </div>
            ) : (
                <p className="mt-4 text-xs text-slate-400">No usage history yet.</p>
            )}
            {!billing?.subscription && !loading && (
                <Link href="/app/billing" className="mt-3 inline-block text-xs font-semibold text-slate-600 underline hover:text-slate-900">
                    View plans &rarr;
                </Link>
            )}
        </article>
    );
}

function QuickActionsWidget({ permissions }) {
    const actions = [
        { href: '/app/projects/create', label: 'Create Project', allowed: permissions.canManageProjects },
        { href: '/app/projects', label: 'Create Task', allowed: permissions.canManageProjects },
        { href: '/app/memberships', label: 'Invite Member', allowed: permissions.canManageMemberships },
        { href: '/app/logs?tab=activity', label: 'View Activity Logs', allowed: permissions.isTenantMember },
        { href: '/app/logs?tab=audit', label: 'Review Audit Logs', allowed: permissions.canManageBilling },
    ];
    const visibleActions = actions.filter((action) => action.allowed);

    return (
        <section className="mt-5 rounded-2xl border border-slate-200 bg-gradient-to-br from-sky-50 via-white to-amber-50 p-4 shadow-sm sm:mt-6 sm:p-5">
            <div className="flex items-center justify-between gap-3">
                <div>
                    <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Quick actions</p>
                    <h3 className="mt-1 text-lg font-semibold text-slate-900">Move fast, keep momentum</h3>
                </div>
                <span className="rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">Fast lane</span>
            </div>
            <div className="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                {visibleActions.map((action) => (
                    <Link
                        key={action.href}
                        href={action.href}
                        className="flex min-h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 transition hover:-translate-y-0.5 hover:bg-slate-50"
                    >
                        {action.label}
                    </Link>
                ))}
                {permissions.canManageBilling && (
                    <Link
                        href="/app/billing"
                        className="flex min-h-11 items-center justify-center rounded-lg bg-slate-900 px-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        Upgrade Plan
                    </Link>
                )}
            </div>
        </section>
    );
}

function LiveActivityTimeline({ items, loading, isLive }) {
    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div className="flex items-center justify-between">
                <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Live activity timeline</p>
                <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${isLive ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>
                    {isLive ? 'Live' : 'Polling'}
                </span>
            </div>
            {loading ? (
                <div className="mt-3 space-y-2">
                    {Array.from({ length: 5 }).map((_, i) => (
                        <div key={i} className="h-11 animate-pulse rounded-lg bg-slate-100" />
                    ))}
                </div>
            ) : items.length === 0 ? (
                <p className="mt-4 text-sm text-slate-500">No activity yet. Create a project or task to start your timeline.</p>
            ) : (
                <ul className="mt-3 space-y-2">
                    {items.map((item) => (
                        <li key={item.id} className="rounded-lg border border-slate-100 bg-slate-50/60 p-3">
                            <p className="text-sm font-medium text-slate-900">{item.message}</p>
                            <p className="mt-0.5 text-xs text-slate-500">{item.actor} &middot; {timeAgo(item.created_at)}</p>
                        </li>
                    ))}
                </ul>
            )}
        </article>
    );
}

// Page

export default function Dashboard() {
    const session = useAppSession();
    const { isLoading, tenantId, tenants, user, permissions } = session;
    const currentTenant = tenants.find((tenant) => String(tenant.id) === String(tenantId));
    const hasTenant = tenants.length > 0;

    const [dataLoading, setDataLoading] = useState(true);
    const [projects, setProjects] = useState([]);
    const [memberships, setMemberships] = useState([]);
    const [billing, setBilling] = useState(null);
    const [usage, setUsage] = useState(null);
    const [recentActivity, setRecentActivity] = useState([]);
    const [myTasks, setMyTasks] = useState([]);
    const [tasksLoading, setTasksLoading] = useState(true);
    const [allTasks, setAllTasks] = useState([]);
    const [auditLogs, setAuditLogs] = useState([]);
    const [timelineItems, setTimelineItems] = useState([]);
    const [pendingInvitesCount, setPendingInvitesCount] = useState(0);
    const [isRealtimeConnected, setIsRealtimeConnected] = useState(false);

    useEffect(() => {
        if (isLoading) return;

        const loadDashboard = async () => {
            const [projRes, membRes, plansRes, usageRes, activityRes, auditRes] = await Promise.allSettled([
                window.axios.get('/api/v1/projects'),
                window.axios.get('/api/v1/memberships'),
                window.axios.get('/api/v1/billing/plans'),
                window.axios.get('/api/v1/billing/usage'),
                window.axios.get('/api/v1/activity-logs'),
                window.axios.get('/api/v1/audit-logs'),
            ]);

            if (projRes.status === 'fulfilled') setProjects(projRes.value?.data?.data ?? []);
            if (membRes.status === 'fulfilled') {
                setMemberships(membRes.value?.data?.data ?? []);
                const canManageInvitations = Boolean(permissions.canManageInvitations);
                if (canManageInvitations) {
                    try {
                        const invitationsResponse = await window.axios.get('/api/v1/invitations');
                        setPendingInvitesCount(invitationsResponse?.data?.data?.length ?? 0);
                    } catch {
                        setPendingInvitesCount(0);
                    }
                }
            }
            if (plansRes.status === 'fulfilled') {
                const rawPlans = plansRes.value?.data?.plans;
                setBilling({
                    subscription: plansRes.value?.data?.subscription ?? null,
                    plans: Array.isArray(rawPlans) ? rawPlans : Array.isArray(rawPlans?.data) ? rawPlans.data : [],
                });
            }
            if (usageRes.status === 'fulfilled') setUsage(usageRes.value?.data ?? null);
            if (activityRes.status === 'fulfilled') {
                const items = activityRes.value?.data?.data ?? [];
                setRecentActivity(items.slice(0, 12));
            }
            if (auditRes.status === 'fulfilled') setAuditLogs(auditRes.value?.data?.data?.slice(0, 12) ?? []);

            setDataLoading(false);
        };

        loadDashboard();
    }, [isLoading, permissions.canManageInvitations]);

    useEffect(() => {
        if (dataLoading) return;
        if (projects.length === 0) {
            setTasksLoading(false);
            return;
        }

        const loadTasks = async () => {
            const sample = projects.slice(0, 5);
            const results = await Promise.allSettled(
                sample.map((p) => window.axios.get(`/api/v1/projects/${p.id}/tasks`)),
            );
            const projectMap = Object.fromEntries(sample.map((p) => [p.id, p.name]));
            const fetched = results
                .filter((r) => r.status === 'fulfilled')
                .flatMap((r) => r.value?.data?.data ?? [])
                .map((t) => ({ ...t, projectName: projectMap[t.project_id] ?? 'Project' }));
            setAllTasks(fetched);
            setMyTasks(
                fetched
                    .filter((t) => Number(t.assigned_to) === Number(user?.id) && t.status !== 'done')
                    .slice(0, 6),
            );
            setTasksLoading(false);
        };

        loadTasks();
    }, [dataLoading, projects, user?.id]);

    useEffect(() => {
        const activityItems = recentActivity.map((log) => {
            const actorName = log.actor?.name ?? log.actor?.email ?? 'System';
            const metaTitle = log.metadata?.task?.title ?? log.metadata?.project?.name ?? log.metadata?.title ?? '';
            const verbMap = {
                'project.created': 'created project',
                'project.updated': 'updated project',
                'project.deleted': 'deleted project',
                'task.created': 'created task',
                'task.updated': 'updated task',
                'task.completed': 'completed task',
            };
            const verb = verbMap[log.action] ?? String(log.action).replace('.', ' ');
            return {
                id: `a-${log.id}`,
                actor: actorName,
                message: metaTitle ? `${actorName} ${verb} "${metaTitle}"` : `${actorName} ${verb}`,
                created_at: log.created_at,
            };
        });

        const auditItems = auditLogs
            .filter((log) => String(log.action).includes('billing'))
            .map((log) => ({
                id: `u-${log.id}`,
                actor: log.actor?.name ?? log.actor?.email ?? 'System',
                message: String(log.action).includes('changed') ? 'Subscription renewed or changed' : 'Subscription updated',
                created_at: log.created_at,
            }));

        const merged = [...activityItems, ...auditItems]
            .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
            .slice(0, 12);
        setTimelineItems(merged);
    }, [recentActivity, auditLogs]);

    useEffect(() => {
        if (!tenantId || projects.length === 0 || !window?.ProjectRealtime?.joinProjectRealtime) {
            setIsRealtimeConnected(false);
            return;
        }

        const joins = [];
        const projectSubset = projects.slice(0, 4);

        const pushRealtime = (event, verb) => {
            const actorName = event?.actor?.name ?? 'Someone';
            const taskTitle = event?.task?.title ? `"${event.task.title}"` : 'a task';
            setTimelineItems((current) => [
                {
                    id: `rt-${Date.now()}-${Math.random()}`,
                    actor: actorName,
                    message: `${actorName} ${verb} ${taskTitle}`,
                    created_at: new Date().toISOString(),
                },
                ...current,
            ].slice(0, 12));
        };

        projectSubset.forEach((project) => {
            const channel = window.ProjectRealtime.joinProjectRealtime({
                tenantId,
                projectId: project.id,
                onTaskCreated: (event) => pushRealtime(event, 'created'),
                onTaskUpdated: (event) => pushRealtime(event, 'updated'),
                onTaskCompleted: (event) => pushRealtime(event, 'completed'),
            });
            joins.push({ projectId: project.id, channel });
        });

        setIsRealtimeConnected(true);

        return () => {
            joins.forEach(({ projectId }) => {
                window.ProjectRealtime.leaveProjectRealtime(tenantId, projectId);
            });
            setIsRealtimeConnected(false);
        };
    }, [tenantId, projects]);

    // Derived values
    const projectCount = projects.length;
    const memberCount = memberships.length;
    const subscriptionStatus = String(billing?.subscription?.stripe_status ?? '').toLowerCase();
    const hasSubscriptionRecord = Boolean(billing?.subscription);
    const hasPaidSubscription = ['active', 'trialing'].includes(subscriptionStatus);
    const planName = usage?.plan?.name ?? 'Workspace tier';
    const userUtilization = usage?.utilization?.max_users ?? null;
    const projUtilization = usage?.utilization?.max_projects ?? null;
    const userUsage = usage?.usage?.max_users ?? 0;
    const userLimit = usage?.limits?.max_users ?? 0;
    const projUsage = usage?.usage?.max_projects ?? 0;
    const projLimit = usage?.limits?.max_projects ?? 0;
    const historyData = usage?.history ?? [];
    const maxProjects = Math.max(...historyData.map((h) => h.projects_count ?? 0), 1);
    const maxUsers = Math.max(...historyData.map((h) => h.users_count ?? 0), 1);
    const roleCounts = memberships.reduce((acc, m) => {
        acc[m.role] = (acc[m.role] ?? 0) + 1;
        return acc;
    }, {});

    // Role gating
    const canManageProjects = Boolean(permissions.canManageProjects);
    const canManageMemberships = Boolean(permissions.canManageMemberships);
    const canManageBilling = Boolean(permissions.canManageBilling);

        // Project activity chart (last 4 weeks)
        const nowDate = new Date();
        const activityWeeks = [3, 2, 1, 0].map((w) => {
            const wEnd = new Date(nowDate.getTime() - w * 7 * 24 * 60 * 60 * 1000);
            const wStart = new Date(wEnd.getTime() - 7 * 24 * 60 * 60 * 1000);
            const label = w === 0 ? 'This wk' : w === 1 ? 'Last wk' : `${w + 1}w ago`;
            return {
                label,
                created: allTasks.filter((t) => { const d = new Date(t.created_at); return d >= wStart && d < wEnd; }).length,
                completed: allTasks.filter((t) => { const d = new Date(t.updated_at); return t.status === 'done' && d >= wStart && d < wEnd; }).length,
            };
        });
        const maxActivityVal = Math.max(...activityWeeks.flatMap((w) => [w.created, w.completed]), 1);

    // Team productivity chart
    const teamProductivity = memberships
        .map((m) => ({
            id: m.user_id,
            name: m.user?.name ?? m.user?.email ?? 'Member',
            count: allTasks.filter((t) => Number(t.assigned_to) === Number(m.user_id) && t.status === 'done').length,
            joinedAt: m.created_at,
        }))
        .filter((m) => m.count > 0)
        .sort((a, b) => b.count - a.count)
        .slice(0, 6);
    const maxProductivity = Math.max(...teamProductivity.map((m) => m.count), 1);

    // Role-based personalization
    const startOfToday = new Date();
    startOfToday.setHours(0, 0, 0, 0);
    const endOfToday = new Date(startOfToday);
    endOfToday.setDate(endOfToday.getDate() + 1);
    const myTasksDueToday = allTasks.filter((t) => Number(t.assigned_to) === Number(user?.id) && t.due_at && new Date(t.due_at) >= startOfToday && new Date(t.due_at) < endOfToday && t.status !== 'done').length;
    const myOverdueTasks = allTasks.filter((t) => Number(t.assigned_to) === Number(user?.id) && t.due_at && new Date(t.due_at) < new Date() && t.status !== 'done').length;
    const myAssignedTasks = allTasks.filter((t) => Number(t.assigned_to) === Number(user?.id) && t.status !== 'done').length;
    const tasksNeedingApproval = allTasks.filter((t) => t.status === 'done').length;
    const unassignedTasks = allTasks.filter((t) => !t.assigned_to && t.status !== 'done').length;
    const activeProjectIds = new Set(allTasks.filter((t) => new Date(t.updated_at ?? t.created_at) > new Date(Date.now() - 14 * 24 * 60 * 60 * 1000)).map((t) => Number(t.project_id)));
    const projectsWithoutActivity = projects.filter((p) => !activeProjectIds.has(Number(p.id))).length;
    const recentlyJoinedMembers = memberships
        .slice()
        .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
        .slice(0, 3);

    // Smart alerts
    const alerts = [];
    if (!dataLoading && canManageBilling && !hasSubscriptionRecord) {
        alerts.push({ type: 'info', message: 'No active paid subscription. Choose a plan if you want paid billing features.', href: '/app/billing', cta: 'View plans' });
    }
    if (!dataLoading && canManageBilling && ['past_due', 'incomplete', 'unpaid'].includes(subscriptionStatus)) {
        alerts.push({ type: 'warning', message: 'Payment failed. Update your billing method to avoid service disruption.', href: '/app/billing', cta: 'Fix payment' });
    }
    if (!dataLoading && canManageBilling && billing?.subscription?.ends_at && new Date(billing.subscription.ends_at) < new Date(Date.now() + 14 * 24 * 60 * 60 * 1000)) {
        alerts.push({ type: 'warning', message: 'Subscription is expiring soon. Renew to keep access uninterrupted.', href: '/app/billing', cta: 'Renew' });
    }
    if (!dataLoading && canManageBilling && userUtilization !== null && userUtilization >= 80) {
        alerts.push({ type: 'warning', message: `Team seat usage is at ${userUtilization}% \u2014 approaching the plan limit.`, href: '/app/billing', cta: 'Upgrade' });
    }
    if (!dataLoading && canManageBilling && projUtilization !== null && projUtilization >= 80) {
        alerts.push({ type: 'warning', message: `Project usage is at ${projUtilization}% \u2014 consider upgrading your plan.`, href: '/app/billing', cta: 'Upgrade' });
    }
    if (!dataLoading && canManageBilling && (usage?.utilization?.api_rate_limit ?? 0) >= 85) {
        alerts.push({ type: 'warning', message: 'Storage/API usage is almost full. Consider upgrading before throttling starts.', href: '/app/billing', cta: 'View limits' });
    }
    if (!window?.Echo) {
        alerts.push({ type: 'info', message: 'Realtime integration appears disconnected. Live updates are currently limited.', href: '/app/logs?tab=activity', cta: 'Open logs' });
    }
    if (!dataLoading && unassignedTasks > 0) {
        alerts.push({ type: 'warning', message: `${unassignedTasks} task${unassignedTasks > 1 ? 's are' : ' is'} unassigned. Assign owners to avoid delays.`, href: '/app/projects', cta: 'Assign tasks' });
    }

    if (isLoading) {
        return (
            <AppLayout title="Dashboard" session={session}>
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {Array.from({ length: 4 }).map((_, i) => (
                        <div key={i} className="h-28 animate-pulse rounded-2xl border border-slate-200 bg-white/80 shadow-sm" />
                    ))}
                </div>
            </AppLayout>
        );
    }

    if (!hasTenant) {
        return (
            <AppLayout title="Dashboard" session={session}>
                <section className="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-amber-50 to-sky-50 p-6 shadow-sm sm:rounded-3xl sm:p-8">
                    <div className="pointer-events-none absolute -top-20 right-0 h-52 w-52 rounded-full bg-amber-200/40 blur-3xl" />
                    <div className="relative grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
                        <div>
                            <p className="text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase sm:text-xs">
                                Workspace setup
                            </p>
                            <h2 className="mt-3 text-2xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                                Create your first workspace to activate the app.
                            </h2>
                            <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                                Projects, billing, memberships, and activity feeds all unlock once you create a tenant. Pick a clear name and slug now so your workspace is ready for invites and project setup.
                            </p>
                            <div className="mt-6 flex flex-wrap gap-3">
                                <Link
                                    href="/app/tenants/create"
                                    className="inline-flex min-h-11 items-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800"
                                >
                                    Create workspace
                                </Link>
                            </div>
                        </div>

                        <aside className="rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-sm backdrop-blur">
                            <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">What happens next</p>
                            <ul className="mt-4 space-y-3 text-sm text-slate-700">
                                <li>Create a workspace with a shareable slug.</li>
                                <li>Invite teammates and assign roles.</li>
                                <li>Open your first project and start task execution.</li>
                            </ul>
                        </aside>
                    </div>
                </section>
            </AppLayout>
        );
    }

    return (
        <AppLayout title="Dashboard" session={session}>

            {/* Alerts */}
            {alerts.length > 0 && (
                <div className="mb-5 space-y-2">
                    {alerts.map((alert, i) => (
                        <div
                            key={i}
                            className={`flex items-center gap-3 rounded-xl border px-4 py-3 text-sm ${
                                alert.type === 'warning'
                                    ? 'border-amber-200 bg-amber-50 text-amber-800'
                                    : 'border-sky-200 bg-sky-50 text-sky-800'
                            }`}
                        >
                            <span className="shrink-0 font-bold">{alert.type === 'warning' ? '\u26a0' : '\u2139'}</span>
                            <span className="flex-1">{alert.message}</span>
                            {alert.href && (
                                <Link href={alert.href} className="shrink-0 font-semibold underline">
                                    {alert.cta}
                                </Link>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {/* Hero */}
            <section className="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-amber-50 to-sky-50 p-5 shadow-sm sm:rounded-3xl sm:p-8">
                <div className="pointer-events-none absolute -top-24 right-0 h-56 w-56 rounded-full bg-amber-200/40 blur-3xl" />
                <div className="relative flex flex-col gap-4 sm:gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p className="text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase sm:text-xs">
                            {currentTenant?.name ?? 'Your workspace'}
                        </p>
                        <h2 className="mt-2 text-xl font-semibold tracking-tight text-slate-900 sm:text-3xl">
                            Welcome back, {user?.name ?? 'there'}
                        </h2>
                        <p className="mt-1 text-sm text-slate-500">
                            {new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canManageProjects && (
                            <Link
                                href="/app/projects/create"
                                className="inline-flex min-h-10 items-center gap-1.5 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800"
                            >
                                + New project
                            </Link>
                        )}
                        {canManageMemberships && (
                            <Link
                                href="/app/memberships"
                                className="inline-flex min-h-10 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 transition hover:bg-slate-50"
                            >
                                + Invite member
                            </Link>
                        )}
                    </div>
                </div>
            </section>

            <QuickActionsWidget permissions={permissions} />

            {/* KPI cards */}
            <section className="mt-5 grid gap-3 sm:mt-6 sm:grid-cols-2 sm:gap-4 xl:grid-cols-4">
                <KpiCard
                    label="Projects"
                    value={dataLoading ? '\u2014' : projectCount}
                    sub={`${projUsage} / ${formatLimit(projLimit)} used`}
                    utilization={projUtilization}
                    href="/app/projects"
                    loading={dataLoading}
                />
                <KpiCard
                    label="Team members"
                    value={dataLoading ? '\u2014' : memberCount}
                    sub={`${userUsage} / ${formatLimit(userLimit)} seats`}
                    utilization={userUtilization}
                    href="/app/memberships"
                    loading={dataLoading}
                />
                <KpiCard
                    label="Current tier"
                    value={dataLoading ? '\u2014' : planName}
                    sub={hasPaidSubscription ? 'Paid subscription active' : 'No paid subscription'}
                    href="/app/billing"
                    loading={dataLoading}
                />
                <KpiCard
                    label="API requests"
                    value={dataLoading ? '\u2014' : formatLimit(usage?.usage?.api_rate_limit ?? 0)}
                    sub={`/ ${formatLimit(usage?.limits?.api_rate_limit)} limit`}
                    utilization={usage?.utilization?.api_rate_limit ?? null}
                    href="/app/logs"
                    loading={dataLoading}
                />
            </section>

            {/* Growth & activity charts */}
            <section className="mt-5 grid gap-4 sm:mt-6 sm:grid-cols-2 xl:grid-cols-3">
                <ProjectActivityChart weeks={activityWeeks} maxVal={maxActivityVal} loading={tasksLoading} />
                <TeamProductivityChart members={teamProductivity} maxVal={maxProductivity} loading={tasksLoading} />
                {canManageBilling && (
                    <RevenueChart usage={usage} billing={billing} loading={dataLoading} />
                )}
            </section>

            {/* Usage trends + Activity feed */}
            <section className="mt-5 grid gap-4 sm:mt-6 lg:grid-cols-[1fr_1.4fr]">
                <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div className="flex items-center justify-between">
                        <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Usage trends</p>
                        <Link href="/app/billing" className="text-xs font-medium text-slate-500 underline hover:text-slate-800">
                            Billing details
                        </Link>
                    </div>
                    {dataLoading ? (
                        <div className="mt-4 space-y-4">
                            <div className="h-14 animate-pulse rounded-lg bg-slate-100" />
                            <div className="h-14 animate-pulse rounded-lg bg-slate-100" />
                        </div>
                    ) : historyData.length === 0 ? (
                        <p className="mt-4 text-sm text-slate-500">No usage history yet. Data populates after first activity.</p>
                    ) : (
                        <div className="mt-4 space-y-4">
                            <UsageBarChart label="Projects" data={historyData.map((h) => h.projects_count ?? 0)} max={maxProjects} color="bg-amber-400" />
                            <UsageBarChart label="Members" data={historyData.map((h) => h.users_count ?? 0)} max={maxUsers} color="bg-sky-400" />
                        </div>
                    )}
                </article>

                <LiveActivityTimeline items={timelineItems} loading={dataLoading} isLive={isRealtimeConnected} />
            </section>

            {/* My work + Team + Billing + Quick actions */}
            <section className="mt-5 grid gap-4 sm:mt-6 sm:grid-cols-2 xl:grid-cols-4">
                {/* My work */}
                <MyWork tasks={myTasks} loading={tasksLoading} />

                {/* Team snapshot */}
                <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div className="flex items-center justify-between">
                        <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Team</p>
                        <Link href="/app/memberships" className="text-xs font-medium text-slate-500 underline hover:text-slate-800">
                            Manage
                        </Link>
                    </div>
                    <p className={`mt-2 text-3xl font-bold text-slate-900 ${dataLoading ? 'animate-pulse text-slate-200' : ''}`}>
                        {dataLoading ? '\u2014' : memberCount}
                    </p>
                    <p className="text-sm text-slate-500">active members</p>
                    <div className="mt-2 space-y-1 text-xs text-slate-600">
                        <p>Pending invitations: {pendingInvitesCount || 0}</p>
                        <p>Most active: {teamProductivity[0]?.name ?? 'No data yet'}</p>
                        <p>Recently joined: {recentlyJoinedMembers.length}</p>
                    </div>
                    {Object.keys(roleCounts).length > 0 && (
                        <div className="mt-3 flex flex-wrap gap-1.5">
                            {Object.entries(roleCounts).map(([role, count]) => (
                                <span key={role} className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${roleBadgeClass(role)}`}>
                                    {count} {role}
                                </span>
                            ))}
                        </div>
                    )}
                    <Link
                        href="/app/memberships"
                        className="mt-4 inline-flex min-h-9 w-full items-center justify-center rounded-lg border border-slate-300 bg-slate-50 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        {canManageMemberships ? '+ Invite member' : 'View members'}
                    </Link>
                </article>

                {/* Billing snapshot */}
                <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div className="flex items-center justify-between">
                        <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Billing</p>
                        <Link href="/app/billing" className="text-xs font-medium text-slate-500 underline hover:text-slate-800">
                            Manage
                        </Link>
                    </div>
                    {canManageBilling ? (
                        <>
                            <p className={`mt-2 text-lg font-bold text-slate-900 ${dataLoading ? 'animate-pulse text-slate-200' : ''}`}>
                                {dataLoading ? '\u2014' : planName}
                            </p>
                            <p className="mt-0.5 text-sm text-slate-500">
                                {hasPaidSubscription
                                    ? `Next billing date: ${billing?.subscription?.ends_at ? new Date(billing.subscription.ends_at).toLocaleDateString() : 'Not available'}`
                                    : 'No paid subscription active'}
                            </p>
                            <div className="mt-3 space-y-2">
                                <UsageRow label="Projects" usage={projUsage} limit={projLimit} />
                                <UsageRow label="Members" usage={userUsage} limit={userLimit} />
                                <UsageRow label="API" usage={usage?.usage?.api_rate_limit ?? 0} limit={usage?.limits?.api_rate_limit ?? 0} />
                            </div>
                            <Link
                                href="/app/billing"
                                className="mt-4 inline-flex min-h-9 w-full items-center justify-center rounded-lg bg-slate-900 text-xs font-semibold text-white transition hover:bg-slate-800"
                            >
                                {hasPaidSubscription ? 'Manage subscription \u2192' : 'Choose plan \u2192'}
                            </Link>
                        </>
                    ) : (
                        <p className="mt-3 text-sm text-slate-500">Billing management is restricted to owner/admin roles.</p>
                    )}
                </article>

                {/* Role-based focus */}
                <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Role focus</p>
                    {!canManageProjects ? (
                        <ul className="mt-3 space-y-2 text-sm text-slate-700">
                            <li className="rounded-lg border border-slate-200 bg-slate-50 p-2.5">My tasks due today: <strong>{myTasksDueToday}</strong></li>
                            <li className="rounded-lg border border-slate-200 bg-slate-50 p-2.5">Overdue tasks: <strong>{myOverdueTasks}</strong></li>
                            <li className="rounded-lg border border-slate-200 bg-slate-50 p-2.5">Tasks assigned to me: <strong>{myAssignedTasks}</strong></li>
                        </ul>
                    ) : (
                        <ul className="mt-3 space-y-2 text-sm text-slate-700">
                            <li className="rounded-lg border border-slate-200 bg-slate-50 p-2.5">Completed tasks: <strong>{tasksNeedingApproval}</strong></li>
                            <li className="rounded-lg border border-slate-200 bg-slate-50 p-2.5">Projects without activity: <strong>{projectsWithoutActivity}</strong></li>
                            <li className="rounded-lg border border-slate-200 bg-slate-50 p-2.5">Pending invites: <strong>{pendingInvitesCount || 0}</strong></li>
                        </ul>
                    )}
                    {teamProductivity.length > 0 && (
                        <div className="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-2.5">
                            <p className="text-xs font-semibold text-amber-800">Productivity ranking</p>
                            <p className="mt-1 text-xs text-amber-700">
                                1) {teamProductivity[0]?.name} ({teamProductivity[0]?.count})
                                {teamProductivity[1] ? ` 2) ${teamProductivity[1].name} (${teamProductivity[1].count})` : ''}
                            </p>
                        </div>
                    )}
                </article>
            </section>

            {/* Onboarding checklist (self-hides when all steps done) */}
            {!dataLoading && (
                <div className="mt-5 sm:mt-6">
                    <OnboardingChecklist
                        hasProjects={projectCount > 0}
                        hasMembers={memberCount > 1}
                        hasSubscription={!!billing?.subscription}
                        hasCreatedTask={allTasks.length > 0}
                        hasCompletedTask={allTasks.some((task) => task.status === 'done')}
                        hasReviewedLogs={recentActivity.length > 0}
                        canManageProjects={canManageProjects}
                        canManageMemberships={canManageMemberships}
                        canManageBilling={canManageBilling}
                    />
                </div>
            )}
        </AppLayout>
    );
}
