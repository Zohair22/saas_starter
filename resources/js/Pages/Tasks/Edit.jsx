import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import InlineNotice from '../../Components/InlineNotice';
import AppLayout from '../../Layouts/AppLayout';
import useAppSession from '../../hooks/useAppSession';

const statusOptions = ['open', 'in_progress', 'done'];
const priorityOptions = ['low', 'medium', 'high'];

export default function TasksEdit({ projectId, taskId }) {
    const session = useAppSession();
    const { isLoading } = session;
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [status, setStatus] = useState('open');
    const [priority, setPriority] = useState('medium');
    const [dueAt, setDueAt] = useState('');
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        const fetchTask = async () => {
            try {
                const response = await window.axios.get(`/api/v1/projects/${projectId}/tasks/${taskId}`);
                const task = response?.data?.data;

                setTitle(task?.title ?? '');
                setDescription(task?.description ?? '');
                setStatus(task?.status ?? 'open');
                setPriority(task?.priority ?? 'medium');
                setDueAt(task?.due_at ? String(task.due_at).slice(0, 16) : '');
            } catch (error) {
                setMessage(error?.response?.data?.message || 'Unable to load task.');
            }
        };

        if (!isLoading) {
            fetchTask();
        }
    }, [isLoading, projectId, taskId]);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setErrors({});
        setMessage('');
        setIsSubmitting(true);

        try {
            await window.axios.patch(`/api/v1/projects/${projectId}/tasks/${taskId}`, {
                title,
                description,
                status,
                priority,
                due_at: dueAt || null,
            });

            window.location.href = `/app/projects/${projectId}/tasks/${taskId}`;
        } catch (error) {
            setErrors(error?.response?.data?.errors || {});
            setMessage(error?.response?.data?.message || 'Unable to update task.');
        } finally {
            setIsSubmitting(false);
        }
    };

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    return (
        <AppLayout title="Edit Task" session={session}>
            <div className="mb-4">
                <Link
                    href={`/app/projects/${projectId}/tasks/${taskId}`}
                    className="text-sm font-medium text-slate-700 underline decoration-slate-300"
                >
                    Back to task
                </Link>
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <form onSubmit={handleSubmit} className="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                    <div>
                        <h2 className="text-base font-semibold text-slate-900">Task information</h2>
                        <p className="mt-1 text-sm text-slate-600">Update title, status, and priority so execution aligns with reality.</p>
                    </div>

                    <label className="block">
                        <span className="mb-1 block text-sm font-medium text-slate-700">Title</span>
                        <input
                            type="text"
                            required
                            value={title}
                            onChange={(event) => setTitle(event.target.value)}
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                        />
                        {errors.title ? <span className="mt-1 block text-xs text-rose-700">{errors.title[0]}</span> : null}
                    </label>

                    <label className="block">
                        <span className="mb-1 block text-sm font-medium text-slate-700">Description</span>
                        <textarea
                            rows={5}
                            value={description}
                            onChange={(event) => setDescription(event.target.value)}
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                        />
                        {errors.description ? (
                            <span className="mt-1 block text-xs text-rose-700">{errors.description[0]}</span>
                        ) : null}
                    </label>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <label className="block">
                            <span className="mb-1 block text-sm font-medium text-slate-700">Status</span>
                            <select
                                value={status}
                                onChange={(event) => setStatus(event.target.value)}
                                className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                            >
                                {statusOptions.map((value) => (
                                    <option key={value} value={value}>
                                        {value}
                                    </option>
                                ))}
                            </select>
                        </label>

                        <label className="block">
                            <span className="mb-1 block text-sm font-medium text-slate-700">Priority</span>
                            <select
                                value={priority}
                                onChange={(event) => setPriority(event.target.value)}
                                className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                            >
                                {priorityOptions.map((value) => (
                                    <option key={value} value={value}>
                                        {value}
                                    </option>
                                ))}
                            </select>
                        </label>
                    </div>

                    <label className="block">
                        <span className="mb-1 block text-sm font-medium text-slate-700">Due date</span>
                        <input
                            type="datetime-local"
                            value={dueAt}
                            onChange={(event) => setDueAt(event.target.value)}
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                        />
                        {errors.due_at ? <span className="mt-1 block text-xs text-rose-700">{errors.due_at[0]}</span> : null}
                    </label>

                    <InlineNotice message={message} />

                    <div className="flex flex-wrap items-center gap-3">
                        <button
                            type="submit"
                            disabled={isSubmitting}
                            className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {isSubmitting ? 'Saving...' : 'Save changes'}
                        </button>
                        <Link
                            href={`/app/projects/${projectId}/tasks/${taskId}`}
                            className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>

                <aside className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Quality checklist</p>
                    <ul className="mt-2 space-y-2 text-sm text-slate-700">
                        <li>Use clear acceptance criteria in description.</li>
                        <li>Set status based on current reality, not intent.</li>
                        <li>Keep priorities meaningful for sprint planning.</li>
                    </ul>
                </aside>
            </div>
        </AppLayout>
    );
}
