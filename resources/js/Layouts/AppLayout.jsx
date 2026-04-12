import { Link } from '@inertiajs/react';
import { useState } from 'react';
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
    const [showMobileControls, setShowMobileControls] = useState(false);
    const isPermissionMetadataMissing = hasTenantContext
        && !permissions.isTenantMember
        && !permissions.canViewBilling
        && !permissions.canViewMemberships;

    const quickNavItems = [
        { label: 'Dashboard', href: '/app' },
        { label: 'Projects', href: '/app/projects' },
    ].filter(Boolean);

    const navGroups = [
        {
            label: 'Operations',
            items: [
                canViewBilling ? { label: 'Billing', href: '/app/billing' } : null,
                canViewMemberships ? { label: 'Members', href: '/app/memberships' } : null,
                canViewApp ? { label: 'Onboarding', href: '/app/onboarding' } : null,
            ].filter(Boolean),
        },
        {
            label: 'Monitoring',
            items: [
                canViewApp ? { label: 'Notifications', href: '/app/notifications' } : null,
                canViewApp ? { label: 'Logs', href: '/app/logs' } : null,
                canViewApp ? { label: 'Analytics', href: '/app/analytics' } : null,
            ].filter(Boolean),
        },
        {
            label: 'Settings',
            items: [
                canViewApp ? { label: 'Profile', href: '/app/settings' } : null,
                canManageTenantSettings || isTenantOwner ? { label: 'Tenant', href: '/app/tenant-settings' } : null,
                isSuperAdmin ? { label: 'Admin', href: '/app/admin' } : null,
            ].filter(Boolean),
        },
    ].filter((group) => group.items.length > 0);

    const isActive = (href) => {
        if (href === '/app') {
            return currentPath === '/app';
        }

        return currentPath.startsWith(href);
    };

    const getGroupActiveHref = (groupItems) => {
        const activeItem = groupItems.find((item) => isActive(item.href));

        return activeItem?.href ?? '';
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
                    <div className="rounded-2xl border border-slate-200/90 bg-white/80 p-3 shadow-sm sm:p-4">
                        <div className="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-start lg:gap-4">
                            <div className="space-y-3">
                                <div className="flex min-w-0 items-center justify-between gap-3">
                                    <div className="flex min-w-0 items-center gap-3">
                                        <Link href="/app" className="flex items-center gap-2 text-lg font-semibold tracking-tight text-slate-900">
                                            <span className="h-2.5 w-2.5 rounded-full bg-amber-500" />
                                            SaaS Starter
                                        </Link>
                                        {currentTenant ? (
                                            <div className="hidden rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 sm:block">
                                                {currentTenant.name}
                                            </div>
                                        ) : null}
                                    </div>

                                    <button
                                        type="button"
                                        onClick={() => setShowMobileControls((visible) => !visible)}
                                        className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 md:hidden"
                                        aria-expanded={showMobileControls}
                                        aria-controls="workspace-controls"
                                    >
                                        {showMobileControls ? 'Hide workspace' : 'Manage workspace'}
                                    </button>
                                </div>

                                <div
                                    id="workspace-controls"
                                    className={`${showMobileControls ? 'flex' : 'hidden'} items-start gap-2 rounded-xl border border-slate-200 bg-slate-50/80 p-2.5 md:flex md:flex-wrap md:items-center md:justify-start md:gap-2.5`}
                                >
                                    {tenants.length > 0 ? (
                                        <label className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600">
                                            <span>Tenant</span>
                                            <select
                                                value={tenantId ?? ''}
                                                onChange={handleTenantChange}
                                                className="w-full min-w-[170px] max-w-[260px] rounded-md border border-slate-300 bg-white px-2 py-1 text-sm text-slate-700 outline-none"
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

                                    {isPermissionMetadataMissing ? (
                                        <div className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">
                                            Limited access mode
                                        </div>
                                    ) : null}
                                </div>
                            </div>

                            <div className="flex items-center justify-start gap-2 lg:justify-end">
                                <div className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600">
                                    {user?.name ?? user?.email ?? 'Workspace user'}
                                </div>

                                <button
                                    type="button"
                                    onClick={handleLogout}
                                    className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                >
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>

                    <div className="relative mt-3">
                        <div className="pointer-events-none absolute inset-y-0 left-0 z-10 w-6 bg-gradient-to-r from-white/95 to-transparent" />
                        <div className="pointer-events-none absolute inset-y-0 right-0 z-10 w-6 bg-gradient-to-l from-white/95 to-transparent" />
                        <div className="overflow-x-auto pb-1">
                            <nav className="flex min-w-max items-center gap-2 rounded-2xl border border-slate-200 bg-white/80 p-2" aria-label="Primary navigation">
                                {quickNavItems.map((item) => (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        className={`rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition ${
                                            isActive(item.href)
                                                ? 'bg-slate-900 text-white shadow-sm'
                                                : 'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50'
                                        }`}
                                    >
                                        {item.label}
                                    </Link>
                                ))}

                                {navGroups.map((group) => {
                                    const activeGroupHref = getGroupActiveHref(group.items);

                                    return (
                                        <div
                                            key={group.label}
                                            className={`inline-flex items-center rounded-lg border px-2 py-1.5 text-xs font-semibold whitespace-nowrap transition ${
                                                activeGroupHref
                                                    ? 'border-slate-900 bg-slate-900/5 text-slate-900'
                                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                                            }`}
                                        >
                                            <select
                                                value={activeGroupHref}
                                                onChange={(event) => {
                                                    if (!event.target.value) {
                                                        return;
                                                    }

                                                    window.location.href = event.target.value;
                                                }}
                                                aria-label={group.label}
                                                className="min-w-[170px] rounded-md border border-slate-300 bg-white px-2 py-1 text-sm font-medium text-slate-700 outline-none"
                                            >
                                                <option value="" disabled>{group.label}</option>
                                                {group.items.map((item) => (
                                                    <option key={item.href} value={item.href}>{item.label}</option>
                                                ))}
                                            </select>
                                        </div>
                                    );
                                })}
                            </nav>
                        </div>
                    </div>
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


                {children}
            </main>
        </div>
    );
}
