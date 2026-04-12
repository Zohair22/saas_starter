import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import InlineNotice from '../../Components/InlineNotice';
import { CardSkeleton } from '../../Components/LoadingSkeleton';
import AppLayout from '../../Layouts/AppLayout';
import useAppSession from '../../hooks/useAppSession';

export default function ProjectsShow({ id }) {
    const session = useAppSession();
    const { isLoading, permissions = {} } = session;
    const canManageProjects = Boolean(permissions.canManageProjects);
    const [isPageLoading, setIsPageLoading] = useState(true);
    const [project, setProject] = useState(null);
    const [message, setMessage] = useState('');

    const formatDateTime = (value) => {
        if (!value) {
            return '-';
        }

        const parsed = new Date(value);

        if (Number.isNaN(parsed.getTime())) {
            return '-';
        }

        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        }).format(parsed);
    };

    useEffect(() => {
        const fetchProject = async () => {
            setIsPageLoading(true);

            try {
                const response = await window.axios.get(`/api/v1/projects/${id}`);
                setProject(response?.data?.data ?? null);
            } catch (error) {
                const errorMessage = error?.response?.data?.message || 'Unable to load project.';
                setMessage(errorMessage);
            } finally {
                setIsPageLoading(false);
            }
        };

        if (!isLoading) {
            fetchProject();
        }
    }, [id, isLoading]);

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    return (
        <AppLayout title="Project Details" session={session}>
            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <Link href="/app/projects" className="text-sm font-medium text-slate-700 underline decoration-slate-300">
                    Back to projects
                </Link>
                <div className="flex items-center gap-2">
                    <Link
                        href={`/app/projects/${id}/tasks`}
                        className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Manage tasks
                    </Link>
                    {canManageProjects && (
                        <Link
                            href={`/app/projects/${id}/edit`}
                            className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            Edit
                        </Link>
                    )}
                </div>
            </div>

            <InlineNotice message={message} className="mb-4" />

            {isPageLoading ? (
                <CardSkeleton />
            ) : project ? (
                <div className="grid gap-4 lg:grid-cols-3">
                    <article className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                        <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Project summary</p>
                        <h2 className="mt-2 text-xl font-semibold tracking-tight text-slate-900">{project.name}</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-700">{project.description || 'No description provided.'}</p>

                        <dl className="mt-6 grid gap-3 sm:grid-cols-2">
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <dt className="text-xs uppercase tracking-wide text-slate-500">Project ID</dt>
                                <dd className="mt-1 text-sm font-semibold text-slate-900">{project.id}</dd>
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <dt className="text-xs uppercase tracking-wide text-slate-500">Tenant ID</dt>
                                <dd className="mt-1 text-sm font-semibold text-slate-900">{project.tenant_id ?? '-'}</dd>
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <dt className="text-xs uppercase tracking-wide text-slate-500">Created By</dt>
                                <dd className="mt-1 text-sm font-semibold text-slate-900">{project?.creator?.name ?? project?.creator?.email ?? '-'}</dd>
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <dt className="text-xs uppercase tracking-wide text-slate-500">Last Updated</dt>
                                <dd className="mt-1 text-sm font-semibold text-slate-900">{formatDateTime(project.updated_at)}</dd>
                            </div>
                        </dl>
                    </article>

                    <aside className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Recommended next step</p>
                        <p className="mt-2 text-sm text-slate-700">Break this project into actionable tasks and assign priorities so your team can execute clearly.</p>
                        <div className="mt-4 flex flex-col gap-2">
                            {canManageProjects && (
                                <Link
                                    href={`/app/projects/${id}/tasks/create`}
                                    className="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                                >
                                    Create first task
                                </Link>
                            )}
                            <Link
                                href={`/app/projects/${id}/tasks`}
                                className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                View task board
                            </Link>
                        </div>
                    </aside>
                </div>
            ) : null}
        </AppLayout>
    );
}
