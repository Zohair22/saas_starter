import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import InlineNotice from '../../Components/InlineNotice';
import { CardSkeleton } from '../../Components/LoadingSkeleton';
import AppLayout from '../../Layouts/AppLayout';
import useAppSession from '../../hooks/useAppSession';
import useProjectRealtime from '../../hooks/useProjectRealtime';
import { priorityBadgeClass, statusBadgeClass } from '../../utils/badgeClasses';

export default function TasksShow({ projectId, taskId }) {
    const session = useAppSession();
    const { isLoading, tenantId, permissions = {} } = session;
    const canManageProjects = Boolean(permissions.canManageProjects);
    const [isPageLoading, setIsPageLoading] = useState(true);
    const [task, setTask] = useState(null);
    const [message, setMessage] = useState('');

    useEffect(() => {
        const fetchTask = async () => {
            setIsPageLoading(true);

            try {
                const response = await window.axios.get(`/api/v1/projects/${projectId}/tasks/${taskId}`);
                setTask(response?.data?.data ?? null);
            } catch (error) {
                setMessage(error?.response?.data?.message || 'Unable to load task.');
            } finally {
                setIsPageLoading(false);
            }
        };

        if (!isLoading) {
            fetchTask();
        }
    }, [isLoading, projectId, taskId]);

    const handleRealtimeTaskUpdate = useCallback((updatedTask) => {
        if (String(updatedTask.id) !== String(taskId)) {
            return;
        }

        setTask((prev) => (prev ? { ...prev, ...updatedTask } : prev));
    }, [taskId]);

    const { isConnected } = useProjectRealtime({
        tenantId: !isLoading ? tenantId : null,
        projectId: !isLoading ? projectId : null,
        onTaskUpdated: useCallback((event) => handleRealtimeTaskUpdate(event.task), [handleRealtimeTaskUpdate]),
        onTaskCompleted: useCallback((event) => handleRealtimeTaskUpdate(event.task), [handleRealtimeTaskUpdate]),
    });

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    return (
        <AppLayout title="Task Details" session={session}>
            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <Link
                    href={`/app/projects/${projectId}/tasks`}
                    className="text-sm font-medium text-slate-700 underline decoration-slate-300"
                >
                    Back to tasks
                </Link>
                <div className="flex items-center gap-2">
                    {isConnected ? (
                        <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                            <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500" />
                            Live
                        </span>
                    ) : null}
                    {canManageProjects && (
                        <Link
                            href={`/app/projects/${projectId}/tasks/${taskId}/edit`}
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
            ) : task ? (
                <div className="grid gap-4 lg:grid-cols-3">
                    <article className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${statusBadgeClass(task.status)}`}>
                                {task.status}
                            </span>
                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${priorityBadgeClass(task.priority)}`}>
                                {task.priority}
                            </span>
                        </div>

                        <h2 className="mt-3 text-xl font-semibold tracking-tight text-slate-900">{task.title}</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-700">{task.description || 'No description provided.'}</p>

                        <dl className="mt-5 grid gap-3 sm:grid-cols-2">
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <dt className="text-xs uppercase tracking-wide text-slate-500">Status</dt>
                                <dd className="mt-1 text-sm font-semibold text-slate-900">{task.status}</dd>
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <dt className="text-xs uppercase tracking-wide text-slate-500">Priority</dt>
                                <dd className="mt-1 text-sm font-semibold text-slate-900">{task.priority}</dd>
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <dt className="text-xs uppercase tracking-wide text-slate-500">Due Date</dt>
                                <dd className="mt-1 text-sm font-semibold text-slate-900">{task.due_at || '-'}</dd>
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <dt className="text-xs uppercase tracking-wide text-slate-500">Task ID</dt>
                                <dd className="mt-1 text-sm font-semibold text-slate-900">{task.id}</dd>
                            </div>
                        </dl>
                    </article>

                    <aside className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Execution tips</p>
                        <ul className="mt-2 space-y-2 text-sm text-slate-700">
                            <li>Keep status current so teammates trust the board.</li>
                            <li>Use due dates only when a deadline is real.</li>
                            <li>Move done tasks quickly to keep focus clear.</li>
                        </ul>

                        <div className="mt-4 flex flex-col gap-2">
                            {canManageProjects && (
                                <Link
                                    href={`/app/projects/${projectId}/tasks/${taskId}/edit`}
                                    className="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                                >
                                    Update task
                                </Link>
                            )}
                            <Link
                                href={`/app/projects/${projectId}/tasks`}
                                className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Back to tasks
                            </Link>
                        </div>
                    </aside>
                </div>
            ) : null}
        </AppLayout>
    );
}
