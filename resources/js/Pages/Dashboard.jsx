import { Link } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';
import useAppSession from '../hooks/useAppSession';

export default function Dashboard() {
    const session = useAppSession();
    const { isLoading, tenantId, tenants, user } = session;
    const currentTenant = tenants.find((tenant) => String(tenant.id) === String(tenantId));

    if (isLoading) {
        return (
            <AppLayout title="Dashboard" session={session}>
                <div className="grid gap-4 md:grid-cols-3">
                    {Array.from({ length: 3 }).map((_, index) => (
                        <div
                            key={index}
                            className="h-36 animate-pulse rounded-2xl border border-slate-200 bg-white/80 shadow-sm"
                        />
                    ))}
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout title="Dashboard" session={session}>
            <section className="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-amber-50 to-sky-50 p-5 shadow-sm sm:rounded-3xl sm:p-8">
                <div className="pointer-events-none absolute -top-24 right-0 h-56 w-56 rounded-full bg-amber-200/40 blur-3xl" />

                <div className="relative flex flex-col gap-4 sm:gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p className="text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase sm:text-xs sm:tracking-[0.18em]">Workspace overview</p>
                        <h2 className="mt-2 text-xl font-semibold tracking-tight text-slate-900 sm:text-3xl">
                            Welcome back, {user?.name ?? 'there'}
                        </h2>
                        <p className="mt-1.5 max-w-2xl text-sm text-slate-700 sm:mt-2 sm:text-base">
                            Keep your team aligned by planning projects, executing tasks, and tracking account health from one place.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <div className="rounded-xl border border-white/80 bg-white/80 px-4 py-3">
                            <p className="text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase">Signed in</p>
                            <p className="mt-1 text-sm font-medium text-slate-900">{user?.email ?? 'user@example.com'}</p>
                        </div>

                        <div className="rounded-xl border border-white/80 bg-white/80 px-4 py-3">
                            <p className="text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase">Active tenant</p>
                            <p className="mt-1 text-sm font-medium text-slate-900">{currentTenant?.name ?? 'Select tenant'}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section className="mt-5 grid gap-3 sm:mt-6 sm:gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:p-5">
                    <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Projects</p>
                    <p className="mt-1.5 text-lg font-semibold text-slate-900 sm:mt-2 sm:text-xl">Structure initiatives</p>
                    <p className="mt-1.5 text-sm text-slate-600 sm:mt-2">Create project spaces, milestones, and owners for each stream.</p>
                    <Link
                        href="/app/projects"
                        className="mt-3 inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-slate-50 px-4 text-sm font-semibold text-slate-900 transition hover:bg-slate-100 sm:mt-4"
                    >
                        Open projects
                    </Link>
                </article>

                <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:p-5">
                    <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Tasks</p>
                    <p className="mt-1.5 text-lg font-semibold text-slate-900 sm:mt-2 sm:text-xl">Run daily delivery</p>
                    <p className="mt-1.5 text-sm text-slate-600 sm:mt-2">Prioritize execution and keep dependencies visible.</p>
                    <Link
                        href="/app/projects"
                        className="mt-3 inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-slate-50 px-4 text-sm font-semibold text-slate-900 transition hover:bg-slate-100 sm:mt-4"
                    >
                        View project tasks
                    </Link>
                </article>

                <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:p-5">
                    <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Billing</p>
                    <p className="mt-1.5 text-lg font-semibold text-slate-900 sm:mt-2 sm:text-xl">Protect growth</p>
                    <p className="mt-1.5 text-sm text-slate-600 sm:mt-2">Review limits, subscriptions, and payment readiness.</p>
                    <Link
                        href="/app/billing"
                        className="mt-3 inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-slate-50 px-4 text-sm font-semibold text-slate-900 transition hover:bg-slate-100 sm:mt-4"
                    >
                        Open billing
                    </Link>
                </article>

                <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:p-5">
                    <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">People</p>
                    <p className="mt-1.5 text-lg font-semibold text-slate-900 sm:mt-2 sm:text-xl">Scale teamwork</p>
                    <p className="mt-1.5 text-sm text-slate-600 sm:mt-2">Invite members, assign roles, and reduce access risk.</p>
                    <Link
                        href="/app/memberships"
                        className="mt-3 inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-slate-50 px-4 text-sm font-semibold text-slate-900 transition hover:bg-slate-100 sm:mt-4"
                    >
                        Open membership
                    </Link>
                </article>
            </section>

            <section className="mt-5 grid gap-3 sm:mt-6 sm:gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Operational visibility</p>
                        <Link
                            href="/app/logs?tab=activity"
                            className="inline-flex min-h-10 items-center rounded-lg border border-slate-300 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Open logs
                        </Link>
                    </div>

                    <p className="mt-2 text-sm text-slate-600">
                        Use activity and audit streams to explain what changed, when it changed, and who initiated the change.
                    </p>

                    <div className="mt-3 grid gap-2.5 sm:mt-4 sm:gap-3 sm:grid-cols-2">
                        <Link href="/app/logs?tab=activity" className="rounded-xl border border-slate-200 bg-slate-50/80 px-3.5 py-3 text-sm text-slate-700 transition hover:bg-slate-100 sm:px-4">
                            <p className="font-semibold text-slate-900">Activity timeline</p>
                            <p className="mt-1 text-xs text-slate-600">Track events, actions, and operational flow.</p>
                        </Link>
                        <Link href="/app/logs?tab=audit" className="rounded-xl border border-slate-200 bg-slate-50/80 px-3.5 py-3 text-sm text-slate-700 transition hover:bg-slate-100 sm:px-4">
                            <p className="font-semibold text-slate-900">Audit differences</p>
                            <p className="mt-1 text-xs text-slate-600">Review field-level before and after changes.</p>
                        </Link>
                    </div>
                </article>

                <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                    <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Suggested next actions</p>
                    <ul className="mt-3 space-y-2 text-sm text-slate-700">
                        <li className="rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2">Create a project to establish delivery scope.</li>
                        <li className="rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2">Invite one teammate and assign role permissions.</li>
                        <li className="rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2">Confirm billing plan and current limit usage.</li>
                    </ul>
                </article>
            </section>
        </AppLayout>
    );
}
