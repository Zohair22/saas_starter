import { Link } from '@inertiajs/react';
import { useState } from 'react';
import InlineNotice from '../../Components/InlineNotice';
import AppLayout from '../../Layouts/AppLayout';
import useAppSession from '../../hooks/useAppSession';
import { setTenantContext } from '../../session';

const slugify = (value) => value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');

export default function TenantCreate() {
    const session = useAppSession();
    const { isLoading, tenants = [] } = session;

    const [name, setName] = useState('');
    const [slug, setSlug] = useState('');
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [hasEditedSlug, setHasEditedSlug] = useState(false);

    const handleNameChange = (event) => {
        const nextName = event.target.value;
        setName(nextName);

        if (!hasEditedSlug) {
            setSlug(slugify(nextName));
        }
    };

    const handleSlugChange = (event) => {
        setHasEditedSlug(true);
        setSlug(slugify(event.target.value));
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setErrors({});
        setMessage('');
        setIsSubmitting(true);

        try {
            const response = await window.axios.post('/api/v1/tenants', {
                name,
                slug,
            });

            const tenantId = response?.data?.data?.id;

            if (tenantId) {
                setTenantContext(tenantId);
                window.location.href = '/app';
                return;
            }

            window.location.href = '/app';
        } catch (error) {
            setErrors(error?.response?.data?.errors ?? {});
            setMessage(error?.response?.data?.message ?? 'Unable to create workspace.');
        } finally {
            setIsSubmitting(false);
        }
    };

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    return (
        <AppLayout title="Create Workspace" session={session}>
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <Link href="/app" className="text-sm font-medium text-slate-700 underline decoration-slate-300">
                    Back to dashboard
                </Link>
                {tenants.length > 0 ? (
                    <p className="text-sm text-slate-500">You can create another workspace without leaving your current one.</p>
                ) : null}
            </div>

            <div className="grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
                <form onSubmit={handleSubmit} className="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div>
                        <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Workspace identity</p>
                        <h2 className="mt-2 text-xl font-semibold tracking-tight text-slate-900">Create a tenant with an explicit slug</h2>
                        <p className="mt-2 text-sm text-slate-600">
                            The slug becomes the stable workspace identifier for URLs and tenant resolution. Keep it short, lowercase, and easy to share.
                        </p>
                    </div>

                    <label className="block">
                        <span className="mb-1 block text-sm font-medium text-slate-700">Workspace name</span>
                        <input
                            type="text"
                            required
                            value={name}
                            onChange={handleNameChange}
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                            placeholder="Acme Studio"
                        />
                        <span className="mt-1 block text-xs text-slate-500">Choose the name your team will see across projects, billing, and activity feeds.</span>
                        {errors.name ? <span className="mt-1 block text-xs text-rose-700">{errors.name[0]}</span> : null}
                    </label>

                    <label className="block">
                        <span className="mb-1 block text-sm font-medium text-slate-700">Workspace slug</span>
                        <input
                            type="text"
                            required
                            value={slug}
                            onChange={handleSlugChange}
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-slate-300 transition focus:border-slate-500 focus:ring"
                            placeholder="acme-studio"
                        />
                        <span className="mt-1 block text-xs text-slate-500">Allowed characters: lowercase letters, numbers, and hyphens.</span>
                        {errors.slug ? <span className="mt-1 block text-xs text-rose-700">{errors.slug[0]}</span> : null}
                    </label>

                    <InlineNotice message={message} />

                    <div className="flex flex-wrap items-center gap-3">
                        <button
                            type="submit"
                            disabled={isSubmitting}
                            className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {isSubmitting ? 'Creating...' : 'Create workspace'}
                        </button>
                        <Link
                            href="/app"
                            className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>

                <aside className="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <section>
                        <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Slug preview</p>
                        <div className="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p className="text-xs text-slate-500">Workspace URL identifier</p>
                            <p className="mt-1 font-mono text-sm font-semibold text-slate-900">{slug || 'your-workspace-slug'}</p>
                        </div>
                    </section>

                    <section>
                        <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Before you create it</p>
                        <ul className="mt-3 space-y-2 text-sm text-slate-700">
                            <li>Use a name your team will recognize immediately.</li>
                            <li>Keep the slug stable to avoid downstream confusion.</li>
                            <li>You can switch into the new workspace right after creation.</li>
                        </ul>
                    </section>
                </aside>
            </div>
        </AppLayout>
    );
}
