import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import ConfirmDialog from '../../Components/ConfirmDialog';
import InlineNotice from '../../Components/InlineNotice';
import { CardSkeleton, TableSkeleton } from '../../Components/LoadingSkeleton';
import useAppSession from '../../hooks/useAppSession';
import useToast from '../../hooks/useToast';

export default function ProjectsIndex() {
    const session = useAppSession();
    const toast = useToast();
    const { isLoading } = session;
    const [isPageLoading, setIsPageLoading] = useState(true);
    const [projects, setProjects] = useState([]);
    const [message, setMessage] = useState('');
    const [projectToDelete, setProjectToDelete] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const fetchProjects = async () => {
        setIsPageLoading(true);

        try {
            const response = await window.axios.get('/api/v1/projects');
            setProjects(response?.data?.data ?? []);
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
            setProjects((current) => current.filter((project) => project.id !== projectId));
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
            fetchProjects();
        }
    }, [isLoading]);

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
                    <div className="mb-4 grid gap-4 sm:grid-cols-3">
                        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2">
                            <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Workspace overview</p>
                            <p className="mt-2 text-base font-semibold text-slate-900">Organize delivery by project</p>
                            <p className="mt-1 text-sm text-slate-600">Group related work, track progress, and move to tasks in one click.</p>
                        </article>
                        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Total projects</p>
                            <p className="mt-2 text-2xl font-semibold text-slate-900">{projects.length}</p>
                        </article>
                    </div>

                    <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-slate-600">Track all projects in your active tenant context.</p>
                        <Link href="/app/projects/create" className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                            New project
                        </Link>
                    </div>

                    <InlineNotice message={message} className="mb-4" />

                    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">Name</th>
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">Description</th>
                                    <th className="px-4 py-3 text-right font-medium text-slate-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {projects.map((project) => (
                                    <tr key={project.id}>
                                        <td className="px-4 py-3 font-medium text-slate-900">{project.name}</td>
                                        <td className="px-4 py-3 text-slate-600">{project.description || '-'}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={`/app/projects/${project.id}`}
                                                    className="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    href={`/app/projects/${project.id}/edit`}
                                                    className="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    Edit
                                                </Link>
                                                <Link
                                                    href={`/app/projects/${project.id}/tasks`}
                                                    className="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    Tasks
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => setProjectToDelete(project.id)}
                                                    className="rounded-md border border-rose-200 px-2.5 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-50"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {projects.length === 0 ? (
                                    <tr>
                                        <td colSpan={3} className="px-4 py-8 text-center text-slate-500">
                                            No projects yet. Create one to start grouping work.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
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
