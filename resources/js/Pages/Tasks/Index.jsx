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

export default function TasksIndex({ projectId }) {
    const session = useAppSession();
    const toast = useToast();
    const { isLoading, tenantId } = session;
    const [isPageLoading, setIsPageLoading] = useState(true);
    const [project, setProject] = useState(null);
    const [tasks, setTasks] = useState([]);
    const [message, setMessage] = useState('');
    const [taskToDelete, setTaskToDelete] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);

    useEffect(() => {
        const fetchData = async () => {
            setIsPageLoading(true);

            try {
                const [projectResponse, tasksResponse] = await Promise.all([
                    window.axios.get(`/api/v1/projects/${projectId}`),
                    window.axios.get(`/api/v1/projects/${projectId}/tasks`),
                ]);

                setProject(projectResponse?.data?.data ?? null);
                setTasks(tasksResponse?.data?.data ?? []);
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
    }, [isLoading, projectId]);

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
            setTasks((current) => {
                if (current.some((task) => task.id === event.task.id)) {
                    return current;
                }

                return [event.task, ...current];
            });
        }, []),
        onTaskUpdated: useCallback((event) => handleTaskUpdate(event.task), [handleTaskUpdate]),
        onTaskCompleted: useCallback((event) => handleTaskUpdate(event.task), [handleTaskUpdate]),
    });

    const handleDelete = async (taskId) => {
        setIsDeleting(true);

        try {
            await window.axios.delete(`/api/v1/projects/${projectId}/tasks/${taskId}`);
            setTasks((current) => current.filter((task) => task.id !== taskId));
            toast.success('Task deleted.');
            setTaskToDelete(null);
        } catch (error) {
            const errorMessage = error?.response?.data?.message || 'Unable to delete task.';
            setMessage(errorMessage);
        } finally {
            setIsDeleting(false);
        }
    };

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    return (
        <AppLayout title="Tasks" session={session}>
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
                <Link
                    href={`/app/projects/${projectId}/tasks/create`}
                    className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                >
                    New task
                </Link>
            </div>

            <InlineNotice message={message} className="mb-4" />

            {isPageLoading ? (
                <TableSkeleton rows={6} />
            ) : (
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50">
                        <tr>
                            <th className="px-4 py-3 text-left font-medium text-slate-600">Title</th>
                            <th className="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                            <th className="px-4 py-3 text-left font-medium text-slate-600">Priority</th>
                            <th className="px-4 py-3 text-right font-medium text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {tasks.map((task) => (
                            <tr key={task.id}>
                                <td className="px-4 py-3 font-medium text-slate-900">{task.title}</td>
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
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-2">
                                        <Link
                                            href={`/app/projects/${projectId}/tasks/${task.id}`}
                                            className="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                        >
                                            View
                                        </Link>
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
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {tasks.length === 0 ? (
                            <tr>
                                <td colSpan={4} className="px-4 py-8 text-center text-slate-500">
                                    No tasks yet. Add one to begin execution for this project.
                                </td>
                            </tr>
                        ) : null}
                    </tbody>
                </table>
                </div>
            )}

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
