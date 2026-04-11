import { Link } from '@inertiajs/react';
import { useState } from 'react';
import InlineNotice from '../../Components/InlineNotice';
import AppLayout from '../../Layouts/AppLayout';
import useAppSession from '../../hooks/useAppSession';
import useToast from '../../hooks/useToast';

export default function ProjectsCreate() {
    const session = useAppSession();
    const toast = useToast();
    const { isLoading } = session;
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setErrors({});
        setMessage('');
        setIsSubmitting(true);

        try {
            const response = await window.axios.post('/api/v1/projects', {
                name,
                description,
            });

            const projectId = response?.data?.data?.id;

            if (projectId) {
                toast.success('Project created successfully.');
                window.location.href = `/app/projects/${projectId}`;
                return;
            }

            window.location.href = '/app/projects';
        } catch (error) {
            const errorMessage = error?.response?.data?.message || 'Unable to create project.';
            setErrors(error?.response?.data?.errors || {});
            setMessage(errorMessage);
        } finally {
            setIsSubmitting(false);
        }
    };

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    return (
        <AppLayout title="Create Project" session={session}>
            <div className="mb-4">
                <Link href="/app/projects" className="text-sm font-medium text-slate-700 underline decoration-slate-300">
                    Back to projects
                </Link>
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <form onSubmit={handleSubmit} className="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                    <div>
                        <h2 className="text-base font-semibold text-slate-900">Project information</h2>
                        <p className="mt-1 text-sm text-slate-600">Use a clear, team-friendly name so members can find this workspace quickly.</p>
                    </div>

                    <label className="block">
                        <span className="mb-1 block text-sm font-medium text-slate-700">Name</span>
                        <input
                            type="text"
                            required
                            value={name}
                            onChange={(event) => setName(event.target.value)}
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                        />
                        <span className="mt-1 block text-xs text-slate-500">Example: Acme - Client Portal Revamp</span>
                        {errors.name ? <span className="mt-1 block text-xs text-rose-700">{errors.name[0]}</span> : null}
                    </label>

                    <label className="block">
                        <span className="mb-1 block text-sm font-medium text-slate-700">Description</span>
                        <textarea
                            rows={5}
                            value={description}
                            onChange={(event) => setDescription(event.target.value)}
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                        />
                        <span className="mt-1 block text-xs text-slate-500">Add scope, owner, and delivery goals to reduce onboarding questions.</span>
                        {errors.description ? (
                            <span className="mt-1 block text-xs text-rose-700">{errors.description[0]}</span>
                        ) : null}
                    </label>

                    <InlineNotice message={message} />

                    <div className="flex flex-wrap items-center gap-3">
                        <button
                            type="submit"
                            disabled={isSubmitting}
                            className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {isSubmitting ? 'Saving...' : 'Create project'}
                        </button>
                        <Link
                            href="/app/projects"
                            className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>

                <aside className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Project setup tips</p>
                    <ul className="mt-2 space-y-2 text-sm text-slate-700">
                        <li>Define a clear problem statement in the description.</li>
                        <li>Add milestones as tasks immediately after creation.</li>
                        <li>Keep names specific to avoid cross-tenant confusion.</li>
                    </ul>
                </aside>
            </div>
        </AppLayout>
    );
}
