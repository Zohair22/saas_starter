import { useEffect, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import useAppSession from '../../hooks/useAppSession';

function StepCard({ title, done, hint, actionHref, actionLabel }) {
    return (
        <article className={`rounded-2xl border p-5 shadow-sm ${done ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white'}`}>
            <div className="flex items-center justify-between">
                <h3 className="text-base font-semibold text-slate-900">{title}</h3>
                <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${done ? 'bg-emerald-200 text-emerald-900' : 'bg-slate-100 text-slate-700'}`}>
                    {done ? 'Done' : 'Pending'}
                </span>
            </div>
            <p className="mt-2 text-sm text-slate-600">{hint}</p>
            {!done ? (
                <a href={actionHref} className="mt-4 inline-block rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                    {actionLabel}
                </a>
            ) : null}
        </article>
    );
}

export default function OnboardingPage() {
    const session = useAppSession();
    const { isLoading } = session;

    const [projectCount, setProjectCount] = useState(0);
    const [memberCount, setMemberCount] = useState(0);
    const [hasPlan, setHasPlan] = useState(false);

    useEffect(() => {
        if (isLoading) return;

        Promise.allSettled([
            window.axios.get('/api/v1/projects'),
            window.axios.get('/api/v1/memberships'),
            window.axios.get('/api/v1/billing/plans'),
        ]).then(([projectsResult, membersResult, plansResult]) => {
            if (projectsResult.status === 'fulfilled') {
                setProjectCount((projectsResult.value?.data?.data ?? []).length);
            }

            if (membersResult.status === 'fulfilled') {
                setMemberCount((membersResult.value?.data?.data ?? []).length);
            }

            if (plansResult.status === 'fulfilled') {
                const current = plansResult.value?.data?.current_plan;
                setHasPlan(Boolean(current?.code));
            }
        });
    }, [isLoading]);

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    const completed = [projectCount > 0, memberCount > 1, hasPlan].filter(Boolean).length;

    return (
        <AppLayout title="Onboarding" session={session}>
            <section className="mb-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Workspace activation</p>
                <h2 className="mt-1 text-xl font-semibold text-slate-900">{completed}/3 setup steps completed</h2>
                <p className="mt-1 text-sm text-slate-600">Finish these steps to get your workspace fully operational.</p>
            </section>

            <div className="grid gap-4 md:grid-cols-3">
                <StepCard
                    title="Create first project"
                    done={projectCount > 0}
                    hint="Projects are the core container for your work and tasks."
                    actionHref="/app/projects/create"
                    actionLabel="Create project"
                />
                <StepCard
                    title="Invite teammate"
                    done={memberCount > 1}
                    hint="Invite at least one teammate so collaboration can begin."
                    actionHref="/app/memberships"
                    actionLabel="Invite members"
                />
                <StepCard
                    title="Choose billing plan"
                    done={hasPlan}
                    hint="Pick a plan now to remove limits and avoid write lock later."
                    actionHref="/app/billing"
                    actionLabel="Pick a plan"
                />
            </div>
        </AppLayout>
    );
}
