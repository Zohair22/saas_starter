import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import ConfirmDialog from '../../Components/ConfirmDialog';
import InlineNotice from '../../Components/InlineNotice';
import { TableSkeleton } from '../../Components/LoadingSkeleton';
import useAppSession from '../../hooks/useAppSession';
import useProjectRealtime from '../../hooks/useProjectRealtime';
import useToast from '../../hooks/useToast';
import { priorityBadgeClass, statusBadgeClass } from '../../utils/badgeClasses';

const DAY_IN_MS = 24 * 60 * 60 * 1000;

const formatUpdateAge = (value) => {
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

const taskHealth = (task) => {
    const due = task?.due_at ? new Date(task.due_at).getTime() : null;
    const now = Date.now();

    if (task?.status === 'done') {
        return {
            label: 'Done',
            tone: 'bg-emerald-100 text-emerald-700',
        };
    }

    if (due && !Number.isNaN(due) && due < now) {
        return {
            label: 'Overdue',
            tone: 'bg-rose-100 text-rose-700',
        };
    }

    if (due && !Number.isNaN(due) && due <= now + (3 * DAY_IN_MS)) {
        return {
            label: 'Due soon',
            tone: 'bg-amber-100 text-amber-800',
        };
    }

    return {
        label: 'On track',
        tone: 'bg-sky-100 text-sky-700',
    };
};

const formatDueDate = (value) => {
    if (!value) {
        return '-';
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return '-';
    }

    return parsed.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

export default function TasksIndex({ projectId }) {
    const session = useAppSession();
    const toast = useToast();
    const { isLoading, tenantId, permissions = {} } = session;
    const canManageProjects = Boolean(permissions.canManageProjects);
    const [isPageLoading, setIsPageLoading] = useState(true);
    const [project, setProject] = useState(null);
    const [tasks, setTasks] = useState([]);
    const [message, setMessage] = useState('');
    const [taskToDelete, setTaskToDelete] = useState(null);
    const [isUpdatingStatusTaskId, setIsUpdatingStatusTaskId] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [priorityFilter, setPriorityFilter] = useState('');
    const [sortBy, setSortBy] = useState('updated_desc');
    const [pagination, setPagination] = useState({
        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        total: 0,
    });

    const fetchTasks = useCallback(async (page = 1, overrides = {}) => {
        const q = overrides.q ?? '';
        const status = overrides.status ?? '';
        const priority = overrides.priority ?? '';
        const sort = overrides.sort ?? 'updated_desc';
        const perPage = overrides.perPage ?? pagination.perPage;

        const response = await window.axios.get(`/api/v1/projects/${projectId}/tasks`, {
            params: {
                q: q || undefined,
                status: status || undefined,
                priority: priority || undefined,
                sort,
                per_page: perPage,
                page,
            },
        });

        setTasks(response?.data?.data ?? []);
        setPagination((current) => ({
            ...current,
            currentPage: response?.data?.meta?.current_page ?? page,
            lastPage: response?.data?.meta?.last_page ?? 1,
            perPage: Number(response?.data?.meta?.per_page ?? current.perPage),
            total: Number(response?.data?.meta?.total ?? 0),
        }));
    }, [projectId, pagination.perPage]);

    useEffect(() => {
        const fetchData = async () => {
            setIsPageLoading(true);

            try {
                const [projectResponse] = await Promise.all([
                    window.axios.get(`/api/v1/projects/${projectId}`),
                ]);

                setProject(projectResponse?.data?.data ?? null);
                await fetchTasks(1, {
                    q: searchQuery,
                    status: statusFilter,
                    priority: priorityFilter,
                    sort: sortBy,
                });
            } catch (error) {
                const errorMessage = error?.response?.data?.message || 'Unable to load tasks.';
                setMessage(errorMessage);
            } finally {
                setIsPageLoading(false);
            }
        };

        if (!isLoading) {
            fetchData();
        }
    }, [isLoading, projectId, fetchTasks]);

    const handleTaskUpdate = useCallback((updatedTask) => {
        setTasks((current) =>
            current.map((task) =>
                task.id === updatedTask.id ? { ...task, ...updatedTask } : task,
            ),
        );
    }, []);

    const { isConnected } = useProjectRealtime({
        tenantId: !isLoading ? tenantId : null,
        projectId: !isLoading ? projectId : null,
        onTaskCreated: useCallback((event) => {
            void event;
            fetchTasks(pagination.currentPage);
        }, [fetchTasks, pagination.currentPage]),
        onTaskUpdated: useCallback((event) => {
            handleTaskUpdate(event.task);
        }, [handleTaskUpdate]),
        onTaskCompleted: useCallback((event) => {
            handleTaskUpdate(event.task);
        }, [handleTaskUpdate]),
    });

    const handleDelete = async (taskId) => {
        setIsDeleting(true);

        try {
            await window.axios.delete(`/api/v1/projects/${projectId}/tasks/${taskId}`);
            await fetchTasks(pagination.currentPage, {
                q: searchQuery,
                status: statusFilter,
                priority: priorityFilter,
                sort: sortBy,
            });
            toast.success('Task deleted.');
            setTaskToDelete(null);
        } catch (error) {
            const errorMessage = error?.response?.data?.message || 'Unable to delete task.';
            setMessage(errorMessage);
        } finally {
            setIsDeleting(false);
        }
    };

    const handleStatusUpdate = async (task, nextStatus) => {
        if (!canManageProjects) {
            return;
        }

        setIsUpdatingStatusTaskId(task.id);

        try {
            await window.axios.patch(`/api/v1/projects/${projectId}/tasks/${task.id}`, {
                title: task.title,
                description: task.description,
                status: nextStatus,
                priority: task.priority,
                due_at: task.due_at || null,
            });

            await fetchTasks(pagination.currentPage, {
                q: searchQuery,
                status: statusFilter,
                priority: priorityFilter,
                sort: sortBy,
            });

            toast.success(nextStatus === 'done' ? 'Task marked as done.' : 'Task moved to in progress.');
        } catch (error) {
            const errorMessage = error?.response?.data?.message || 'Unable to update task status.';
            setMessage(errorMessage);
        } finally {
            setIsUpdatingStatusTaskId(null);
        }
    };

    const taskSummary = tasks.reduce((summary, task) => {
        if (task.status === 'open') {
            summary.open += 1;
        }

        if (task.status === 'in_progress') {
            summary.inProgress += 1;
        }

        if (task.status === 'done') {
            summary.done += 1;
        }

        const healthLabel = taskHealth(task).label;

        if (healthLabel === 'Overdue') {
            summary.overdue += 1;
        }

        if (healthLabel === 'Due soon') {
            summary.dueSoon += 1;
        }

        return summary;
    }, {
        open: 0,
        inProgress: 0,
        done: 0,
        overdue: 0,
        dueSoon: 0,
    });

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    return (
        <AppLayout title="Tasks" session={session}>
            <section className="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Total tasks</p>
                    <p className="mt-2 text-2xl font-semibold text-slate-900">{pagination.total}</p>
                </article>
                <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Open</p>
                    <p className="mt-2 text-2xl font-semibold text-slate-900">{taskSummary.open}</p>
                </article>
                <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">In progress</p>
                    <p className="mt-2 text-2xl font-semibold text-slate-900">{taskSummary.inProgress}</p>
                </article>
                <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Due soon</p>
                    <p className="mt-2 text-2xl font-semibold text-amber-700">{taskSummary.dueSoon}</p>
                </article>
                <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Overdue</p>
                    <p className="mt-2 text-2xl font-semibold text-rose-700">{taskSummary.overdue}</p>
                </article>
            </section>

            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <Link href={`/app/projects/${projectId}`} className="text-sm font-medium text-slate-700 underline decoration-slate-300">
                        Back to project
                    </Link>
                    <div className="mt-2 flex flex-wrap items-center gap-2">
                        <p className="text-sm text-slate-600">{project?.name ? `Project: ${project.name}` : 'Project tasks'}</p>
                        {isConnected ? (
                            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                                <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500" />
                                Live
                            </span>
                        ) : null}
                    </div>
                </div>
                {canManageProjects && (
                    <div className="flex gap-2">
                        <Link
                            href={`/app/projects/${projectId}/tasks/create`}
                            className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                        >
                            + New task
                        </Link>
                        <button
                            type="button"
                            onClick={() => fetchTasks(1, {
                                q: searchQuery,
                                status: statusFilter,
                                priority: priorityFilter,
                                sort: sortBy,
                            })}
                            className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            Refresh
                        </button>
                    </div>
                )}
            </div>

            <InlineNotice message={message} className="mb-4" />

            <div className="mb-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-5">
                <label className="lg:col-span-2">
                    <span className="mb-1 block text-xs font-semibold tracking-wide text-slate-500 uppercase">Search</span>
                    <input
                        type="text"
                        value={searchQuery}
                        onChange={(event) => setSearchQuery(event.target.value)}
                        placeholder="Search title or description"
                        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                    />
                </label>

                <label>
                    <span className="mb-1 block text-xs font-semibold tracking-wide text-slate-500 uppercase">Status</span>
                    <select
                        value={statusFilter}
                        onChange={(event) => setStatusFilter(event.target.value)}
                        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                    >
                        <option value="">All</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In progress</option>
                        <option value="done">Done</option>
                    </select>
                </label>

                <label>
                    <span className="mb-1 block text-xs font-semibold tracking-wide text-slate-500 uppercase">Priority</span>
                    <select
                        value={priorityFilter}
                        onChange={(event) => setPriorityFilter(event.target.value)}
                        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                    >
                        <option value="">All</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </label>

                <label>
                    <span className="mb-1 block text-xs font-semibold tracking-wide text-slate-500 uppercase">Sort</span>
                    <select
                        value={sortBy}
                        onChange={(event) => setSortBy(event.target.value)}
                        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                    >
                        <option value="updated_desc">Recently updated</option>
                        <option value="updated_asc">Oldest updated</option>
                        <option value="due_asc">Due date earliest</option>
                        <option value="due_desc">Due date latest</option>
                    </select>
                </label>
            </div>

            <div className="mb-4 flex items-center gap-2">
                <button
                    type="button"
                    onClick={() => fetchTasks(1, {
                        q: searchQuery,
                        status: statusFilter,
                        priority: priorityFilter,
                        sort: sortBy,
                    })}
                    className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Apply filters
                </button>
                <button
                    type="button"
                    onClick={() => {
                        setSearchQuery('');
                        setStatusFilter('');
                        setPriorityFilter('');
                        setSortBy('updated_desc');
                        fetchTasks(1, {
                            q: '',
                            status: '',
                            priority: '',
                            sort: 'updated_desc',
                        });
                    }}
                    className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Reset
                </button>
                <span className="text-xs text-slate-500">Showing {tasks.length} of {pagination.total} tasks</span>
            </div>

            {isPageLoading ? (
                <TableSkeleton rows={6} />
            ) : (
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50">
                        <tr>
                            <th className="px-4 py-3 text-left font-medium text-slate-600">Title</th>
                            <th className="px-4 py-3 text-left font-medium text-slate-600">Health</th>
                            <th className="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                            <th className="px-4 py-3 text-left font-medium text-slate-600">Priority</th>
                            <th className="px-4 py-3 text-left font-medium text-slate-600">Due</th>
                            <th className="px-4 py-3 text-left font-medium text-slate-600">Updated</th>
                            <th className="px-4 py-3 text-right font-medium text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {tasks.map((task) => (
                            <tr key={task.id}>
                                <td className="px-4 py-3 font-medium text-slate-900">{task.title}</td>
                                <td className="px-4 py-3">
                                    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${taskHealth(task).tone}`}>
                                        {taskHealth(task).label}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${statusBadgeClass(task.status)}`}>
                                        {task.status}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${priorityBadgeClass(task.priority)}`}>
                                        {task.priority}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-slate-600">{formatDueDate(task.due_at)}</td>
                                <td className="px-4 py-3 text-slate-600">{formatUpdateAge(task.updated_at)}</td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-2">
                                        <Link
                                            href={`/app/projects/${projectId}/tasks/${task.id}`}
                                            className="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                        >
                                            View
                                        </Link>
                                        {canManageProjects && (
                                            <>
                                                {task.status !== 'in_progress' && task.status !== 'done' ? (
                                                    <button
                                                        type="button"
                                                        disabled={isUpdatingStatusTaskId === task.id}
                                                        onClick={() => handleStatusUpdate(task, 'in_progress')}
                                                        className="rounded-md border border-sky-200 bg-sky-50 px-2.5 py-1.5 text-xs font-medium text-sky-700 transition hover:bg-sky-100 disabled:cursor-not-allowed disabled:opacity-60"
                                                    >
                                                        Start
                                                    </button>
                                                ) : null}
                                                {task.status !== 'done' ? (
                                                    <button
                                                        type="button"
                                                        disabled={isUpdatingStatusTaskId === task.id}
                                                        onClick={() => handleStatusUpdate(task, 'done')}
                                                        className="rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                                                    >
                                                        Done
                                                    </button>
                                                ) : null}
                                                <Link
                                                    href={`/app/projects/${projectId}/tasks/${task.id}/edit`}
                                                    className="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => setTaskToDelete(task.id)}
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
                        {tasks.length === 0 ? (
                            <tr>
                                <td colSpan={7} className="px-4 py-8 text-center text-slate-500">
                                    No tasks yet. Add one to begin execution for this project.
                                    {canManageProjects ? (
                                        <div className="mt-3">
                                            <Link
                                                href={`/app/projects/${projectId}/tasks/create`}
                                                className="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                                            >
                                                Create your first task
                                            </Link>
                                        </div>
                                    ) : null}
                                </td>
                            </tr>
                        ) : null}
                    </tbody>
                </table>
                </div>
            )}

            <div className="mt-4 flex items-center justify-between">
                <button
                    type="button"
                    disabled={pagination.currentPage <= 1 || isPageLoading}
                    onClick={() => fetchTasks(pagination.currentPage - 1, {
                        q: searchQuery,
                        status: statusFilter,
                        priority: priorityFilter,
                        sort: sortBy,
                    })}
                    className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    Previous
                </button>
                <p className="text-xs text-slate-500">Page {pagination.currentPage} of {pagination.lastPage}</p>
                <button
                    type="button"
                    disabled={pagination.currentPage >= pagination.lastPage || isPageLoading}
                    onClick={() => fetchTasks(pagination.currentPage + 1, {
                        q: searchQuery,
                        status: statusFilter,
                        priority: priorityFilter,
                        sort: sortBy,
                    })}
                    className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    Next
                </button>
            </div>

            <ConfirmDialog
                open={taskToDelete !== null}
                title="Delete task"
                description="This action cannot be undone."
                confirmLabel="Delete task"
                onCancel={() => setTaskToDelete(null)}
                onConfirm={() => handleDelete(taskToDelete)}
                isProcessing={isDeleting}
            />
        </AppLayout>
    );
}
