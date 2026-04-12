import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import ConfirmDialog from '../../Components/ConfirmDialog';
import InlineNotice from '../../Components/InlineNotice';
import { CardSkeleton, TableSkeleton } from '../../Components/LoadingSkeleton';
import useAppSession from '../../hooks/useAppSession';
import useToast from '../../hooks/useToast';

const DAY_IN_MS = 24 * 60 * 60 * 1000;

const formatProjectUpdateAge = (value) => {
    if (!value) {
        return 'Unknown';
    }

    const timestamp = new Date(value).getTime();

    if (Number.isNaN(timestamp)) {
        return 'Unknown';
    }

    const diffDays = Math.floor((Date.now() - timestamp) / DAY_IN_MS);

    if (diffDays <= 0) {
        return 'Today';
    }

    if (diffDays === 1) {
        return '1 day ago';
    }

    return `${diffDays} days ago`;
};

const resolveProjectHealth = (project) => {
    const timestamp = new Date(project?.updated_at ?? '').getTime();
    const hasDescription = Boolean(String(project?.description ?? '').trim());

    if (Number.isNaN(timestamp)) {
        return {
            label: 'Needs review',
            tone: 'bg-amber-100 text-amber-800',
        };
    }

    const diffDays = Math.floor((Date.now() - timestamp) / DAY_IN_MS);

    if (diffDays > 30) {
        return {
            label: 'At risk',
            tone: 'bg-rose-100 text-rose-700',
        };
    }

    if (!hasDescription || diffDays > 7) {
        return {
            label: 'Attention',
            tone: 'bg-amber-100 text-amber-800',
        };
    }

    return {
        label: 'Healthy',
        tone: 'bg-emerald-100 text-emerald-700',
    };
};

export default function ProjectsIndex() {
    const session = useAppSession();
    const toast = useToast();
    const { isLoading, permissions = {} } = session;
    const canManageProjects = Boolean(permissions.canManageProjects);
    const [isPageLoading, setIsPageLoading] = useState(true);
    const [projects, setProjects] = useState([]);
    const [message, setMessage] = useState('');
    const [projectToDelete, setProjectToDelete] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [sortBy, setSortBy] = useState('updated_desc');
    const [pagination, setPagination] = useState({
        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        total: 0,
    });

    const healthSummary = projects.reduce((summary, project) => {
        const health = resolveProjectHealth(project).label;

        if (health === 'Healthy') {
            summary.healthy += 1;
        } else if (health === 'Attention') {
            summary.attention += 1;
        } else {
            summary.atRisk += 1;
        }

        return summary;
    }, {
        healthy: 0,
        attention: 0,
        atRisk: 0,
    });

    const fetchProjects = async (page = 1, overrides = {}) => {
        setIsPageLoading(true);

        try {
            const q = overrides.q ?? searchQuery;
            const sort = overrides.sort ?? sortBy;
            const perPage = overrides.perPage ?? pagination.perPage;

            const response = await window.axios.get('/api/v1/projects', {
                params: {
                    q: q || undefined,
                    sort,
                    per_page: perPage,
                    page,
                },
            });

            setProjects(response?.data?.data ?? []);
            setPagination((current) => ({
                ...current,
                currentPage: response?.data?.meta?.current_page ?? page,
                lastPage: response?.data?.meta?.last_page ?? 1,
                perPage: Number(response?.data?.meta?.per_page ?? current.perPage),
                total: Number(response?.data?.meta?.total ?? 0),
            }));
        } catch (error) {
            const errorMessage = error?.response?.data?.message || 'Unable to load projects.';
            setMessage(errorMessage);
        } finally {
            setIsPageLoading(false);
        }
    };

    const handleDelete = async (projectId) => {
        setIsDeleting(true);

        try {
            await window.axios.delete(`/api/v1/projects/${projectId}`);
            await fetchProjects(pagination.currentPage);
            toast.success('Project deleted.');
            setProjectToDelete(null);
        } catch (error) {
            const errorMessage = error?.response?.data?.message || 'Unable to delete project.';
            setMessage(errorMessage);
        } finally {
            setIsDeleting(false);
        }
    };

    useEffect(() => {
        if (!isLoading) {
            fetchProjects(1);
        }
    }, [isLoading, sortBy, pagination.perPage]);

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    return (
        <AppLayout title="Projects" session={session}>
            {isPageLoading ? (
                <div className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="sm:col-span-2">
                            <CardSkeleton />
                        </div>
                        <CardSkeleton />
                    </div>
                    <TableSkeleton rows={6} />
                </div>
            ) : (
                <>
                    <div className="mb-4 grid gap-4 sm:grid-cols-4">
                        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2">
                            <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Workspace overview</p>
                            <p className="mt-2 text-base font-semibold text-slate-900">Organize delivery by project</p>
                            <p className="mt-1 text-sm text-slate-600">Group related work, track progress, and move to tasks in one click.</p>
                        </article>
                        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Total projects</p>
                            <p className="mt-2 text-2xl font-semibold text-slate-900">{pagination.total}</p>
                        </article>
                        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Health snapshot</p>
                            <div className="mt-2 space-y-1 text-sm text-slate-700">
                                <p>Healthy: <span className="font-semibold text-emerald-700">{healthSummary.healthy}</span></p>
                                <p>Attention: <span className="font-semibold text-amber-700">{healthSummary.attention}</span></p>
                                <p>At risk: <span className="font-semibold text-rose-700">{healthSummary.atRisk}</span></p>
                            </div>
                        </article>
                    </div>

                    <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-slate-600">Track all projects in your active tenant context and jump straight into task execution.</p>
                        {canManageProjects ? (
                            <div className="flex flex-wrap gap-2">
                                <Link href="/app/projects/create" className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                                    + New project
                                </Link>
                                <Link href="/app/projects" className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                    Refresh list
                                </Link>
                            </div>
                        ) : null}
                    </div>

                    <InlineNotice message={message} className="mb-4" />

                    <div className="mb-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_auto_auto] sm:items-center">
                        <label className="block">
                            <span className="mb-1 block text-xs font-semibold tracking-wide text-slate-500 uppercase">Search projects</span>
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(event) => setSearchQuery(event.target.value)}
                                placeholder="Search by name or description"
                                className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                            />
                        </label>

                        <label className="block">
                            <span className="mb-1 block text-xs font-semibold tracking-wide text-slate-500 uppercase">Sort</span>
                            <select
                                value={sortBy}
                                onChange={(event) => setSortBy(event.target.value)}
                                className="rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                            >
                                <option value="updated_desc">Recently updated</option>
                                <option value="updated_asc">Oldest updated</option>
                                <option value="name_asc">Name A-Z</option>
                                <option value="name_desc">Name Z-A</option>
                            </select>
                        </label>

                        <div className="flex items-end gap-2">
                            <button
                                type="button"
                                onClick={() => fetchProjects(1)}
                                className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Apply
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    setSearchQuery('');
                                    setSortBy('updated_desc');
                                    fetchProjects(1, { q: '', sort: 'updated_desc' });
                                }}
                                className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Reset
                            </button>
                        </div>
                    </div>

                    <div className="mb-3 text-xs text-slate-500">
                        Showing {projects.length} of {pagination.total} projects
                    </div>

                    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">Name</th>
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">Health</th>
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">Updated</th>
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">Description</th>
                                    <th className="px-4 py-3 text-right font-medium text-slate-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {projects.map((project) => (
                                    <tr key={project.id}>
                                        <td className="px-4 py-3 font-medium text-slate-900">{project.name}</td>
                                        <td className="px-4 py-3">
                                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${resolveProjectHealth(project).tone}`}>
                                                {resolveProjectHealth(project).label}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-slate-600">{formatProjectUpdateAge(project.updated_at)}</td>
                                        <td className="px-4 py-3 text-slate-600">{project.description || 'No description provided.'}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={`/app/projects/${project.id}`}
                                                    className="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    href={`/app/projects/${project.id}/tasks`}
                                                    className="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    Tasks
                                                </Link>
                                                {canManageProjects && (
                                                    <Link
                                                        href={`/app/projects/${project.id}/tasks/create`}
                                                        className="rounded-md border border-emerald-300 bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100"
                                                    >
                                                        New task
                                                    </Link>
                                                )}
                                                {canManageProjects && (
                                                    <>
                                                        <Link
                                                            href={`/app/projects/${project.id}/edit`}
                                                            className="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                        >
                                                            Edit
                                                        </Link>
                                                        <button
                                                            type="button"
                                                            onClick={() => setProjectToDelete(project.id)}
                                                            className="rounded-md border border-rose-200 px-2.5 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-50"
                                                        >
                                                            Delete
                                                        </button>
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {projects.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-8 text-center text-slate-500">
                                            {pagination.total === 0
                                                ? 'No projects yet. Create one to start grouping work.'
                                                : 'No projects match your current filters.'}
                                            {canManageProjects && pagination.total === 0 ? (
                                                <div className="mt-3">
                                                    <Link
                                                        href="/app/projects/create"
                                                        className="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                                                    >
                                                        Create your first project
                                                    </Link>
                                                </div>
                                            ) : null}
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-4 flex items-center justify-between">
                        <button
                            type="button"
                            disabled={pagination.currentPage <= 1 || isPageLoading}
                            onClick={() => fetchProjects(pagination.currentPage - 1)}
                            className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Previous
                        </button>
                        <p className="text-xs text-slate-500">
                            Page {pagination.currentPage} of {pagination.lastPage}
                        </p>
                        <button
                            type="button"
                            disabled={pagination.currentPage >= pagination.lastPage || isPageLoading}
                            onClick={() => fetchProjects(pagination.currentPage + 1)}
                            className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Next
                        </button>
                    </div>
                </>
            )}

            <ConfirmDialog
                open={projectToDelete !== null}
                title="Delete project"
                description="This action cannot be undone."
                confirmLabel="Delete project"
                onCancel={() => setProjectToDelete(null)}
                onConfirm={() => handleDelete(projectToDelete)}
                isProcessing={isDeleting}
            />
        </AppLayout>
    );
}
