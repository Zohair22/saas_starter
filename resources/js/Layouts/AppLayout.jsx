import { Link } from '@inertiajs/react';
import { clearSession } from '../session';

export default function AppLayout({ title, children, session = {} }) {
    const { tenantId, tenants = [], switchTenant, user, permissions = {} } = session;
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
    const currentTenant = tenants.find((tenant) => String(tenant.id) === String(tenantId));
    const isWriteLocked = Boolean(currentTenant?.lifecycle?.is_write_locked);
    const billingStatus = currentTenant?.billing_status ?? null;
    const hasTenantContext = Boolean(tenantId || tenants.length > 0);
    const canViewBilling = Boolean(permissions.canViewBilling) || hasTenantContext;
    const canViewMemberships = Boolean(permissions.canViewMemberships) || hasTenantContext;
    const canViewApp = Boolean(permissions.isTenantMember) || hasTenantContext;
    const canManageTenantSettings = Boolean(permissions.canManageTenantSettings);
    const isTenantOwner = Boolean(permissions.isTenantOwner);
    const isSuperAdmin = Boolean(user?.is_super_admin);
    const canCreateTenant = Boolean(user);
    const isPermissionMetadataMissing = hasTenantContext
        && !permissions.isTenantMember
        && !permissions.canViewBilling
        && !permissions.canViewMemberships;

    const navItems = [
        { label: 'Dashboard', href: '/app' },
        { label: 'Projects', href: '/app/projects' },
        canViewBilling ? { label: 'Billing', href: '/app/billing' } : null,
        canViewMemberships ? { label: 'Members', href: '/app/memberships' } : null,
        canViewApp ? { label: 'Onboarding', href: '/app/onboarding' } : null,
        canViewApp ? { label: 'Notifications', href: '/app/notifications' } : null,
        canViewApp ? { label: 'Logs', href: '/app/logs' } : null,
        canViewApp ? { label: 'Analytics', href: '/app/analytics' } : null,
        canViewApp ? { label: 'Profile', href: '/app/settings' } : null,
        canManageTenantSettings || isTenantOwner ? { label: 'Tenant', href: '/app/tenant-settings' } : null,
        isSuperAdmin ? { label: 'Admin', href: '/app/admin' } : null,
    ].filter(Boolean);

    const isActive = (href) => {
        if (href === '/app') {
            return currentPath === '/app';
        }

        return currentPath.startsWith(href);
    };

    const handleLogout = async () => {
        await clearSession();
        window.location.href = '/login';
    };

    const handleTenantChange = (event) => {
        const nextTenantId = event.target.value;

        if (!nextTenantId || nextTenantId === tenantId) {
            return;
        }

        switchTenant(nextTenantId);
        window.location.reload();
    };

    return (
        <div className="min-h-screen bg-[radial-gradient(circle_at_top_right,#fef3c7_0,#f8fafc_35%,#f1f5f9_100%)] text-slate-900">
            <header className="sticky top-0 z-20 border-b border-slate-200/80 bg-white/85 backdrop-blur">
                <div className="mx-auto max-w-6xl px-4 py-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex items-center justify-between gap-3">
                            <Link href="/app" className="flex items-center gap-2 text-lg font-semibold tracking-tight text-slate-900">
                                <span className="h-2.5 w-2.5 rounded-full bg-amber-500" />
                                SaaS Starter
                            </Link>

                            <div className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600 lg:hidden">
                                {user?.name ?? user?.email ?? 'Workspace user'}
                            </div>
                        </div>

                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between lg:justify-end">
                            {tenants.length > 0 ? (
                                <label className="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600">
                                    <span>Tenant</span>
                                    <select
                                        value={tenantId ?? ''}
                                        onChange={handleTenantChange}
                                        className="min-w-[170px] rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-700 outline-none"
                                    >
                                        {tenants.map((tenant) => (
                                            <option key={tenant.id} value={String(tenant.id)}>
                                                {tenant.name}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                            ) : null}

                            {canCreateTenant ? (
                                <Link
                                    href="/app/tenants/create"
                                    className="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                >
                                    + New workspace
                                </Link>
                            ) : null}

                            <div className="hidden rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600 lg:block">
                                {user?.name ?? user?.email ?? 'Workspace user'}
                            </div>

                            <button
                                type="button"
                                onClick={handleLogout}
                                className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Logout
                            </button>

                            {isPermissionMetadataMissing ? (
                                <div className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">
                                    Limited access mode
                                </div>
                            ) : null}
                        </div>
                    </div>

                    <nav className="mt-3 flex flex-wrap gap-2">
                        {navItems.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`rounded-lg px-3 py-2 text-sm font-medium transition ${
                                    isActive(item.href)
                                        ? 'bg-slate-900 text-white shadow-sm'
                                        : 'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50'
                                }`}
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>
                </div>
            </header>

            <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                {isWriteLocked ? (
                    <div className="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 shadow-sm">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p className="text-xs font-semibold tracking-wide text-rose-700 uppercase">Workspace is read-only</p>
                                <p className="mt-1 text-sm text-rose-900">
                                    Billing status is {billingStatus || 'restricted'}. Writes are blocked until billing is recovered.
                                </p>
                            </div>
                            {canViewBilling ? (
                                <Link
                                    href="/app/billing"
                                    className="inline-flex items-center justify-center rounded-lg bg-rose-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-rose-800"
                                >
                                    Recover billing
                                </Link>
                            ) : (
                                <span className="text-xs font-medium text-rose-700">Contact your owner/admin to recover billing.</span>
                            )}
                        </div>
                    </div>
                ) : null}

                <div className="mb-6 rounded-2xl border border-slate-200 bg-white/80 px-5 py-4 shadow-sm backdrop-blur">
                    <h1 className="text-2xl font-semibold tracking-tight text-slate-900">{title}</h1>
                </div>

                {children}
            </main>
        </div>
    );
}
