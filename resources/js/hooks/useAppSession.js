import { useEffect, useState } from 'react';
import { getAuthToken, getTenantId, setTenantContext } from '../session';

const emptyPermissions = {
    isTenantMember: false,
    canViewMemberships: false,
    canViewBilling: false,
    canManageProjects: false,
    canManageMemberships: false,
    canManageInvitations: false,
    canManageBilling: false,
    canManageTenantSettings: false,
    isTenantOwner: false,
};

const normalizeCapabilities = (capabilities) => {
    if (!capabilities || typeof capabilities !== 'object') {
        return emptyPermissions;
    }

    return {
        isTenantMember: Boolean(capabilities.is_tenant_member),
        canViewMemberships: Boolean(capabilities.can_view_memberships),
        canViewBilling: Boolean(capabilities.can_view_billing),
        canManageProjects: Boolean(capabilities.can_manage_projects),
        canManageMemberships: Boolean(capabilities.can_manage_memberships),
        canManageInvitations: Boolean(capabilities.can_manage_invitations),
        canManageBilling: Boolean(capabilities.can_manage_billing),
        canManageTenantSettings: Boolean(capabilities.can_manage_tenant_settings),
        isTenantOwner: Boolean(capabilities.is_tenant_owner),
    };
};

export default function useAppSession() {
    const [isLoading, setIsLoading] = useState(true);
    const [user, setUser] = useState(null);
    const [tenants, setTenants] = useState([]);
    const [tenantId, setTenantId] = useState(getTenantId());
    const [currentMembership, setCurrentMembership] = useState(null);
    const [permissions, setPermissions] = useState(emptyPermissions);

    useEffect(() => {
        const initializeSession = async () => {
            const token = getAuthToken();

            if (!token) {
                window.location.href = '/login';
                return;
            }

            try {
                const bootstrapResponse = await window.axios.get('/api/v1/session/bootstrap');
                const bootstrappedUser = bootstrapResponse?.data?.user ?? null;
                const bootstrappedTenants = bootstrapResponse?.data?.tenants ?? [];
                const bootstrappedMembership = bootstrapResponse?.data?.context?.current_membership ?? null;
                const bootstrappedCapabilities = normalizeCapabilities(bootstrapResponse?.data?.context?.capabilities);

                setUser(bootstrappedUser);
                setTenants(bootstrappedTenants);

                const firstTenantId = bootstrappedTenants?.[0]?.id;
                const resolvedBootstrapTenantId = bootstrapResponse?.data?.active_tenant_id;
                const activeTenantId = resolvedBootstrapTenantId
                    ? String(resolvedBootstrapTenantId)
                    : (tenantId || (firstTenantId ? String(firstTenantId) : null));

                if (activeTenantId) {
                    setTenantContext(activeTenantId);
                }

                if (!tenantId) {
                    if (firstTenantId) {
                        setTenantId(String(firstTenantId));
                    }
                }

                if (activeTenantId) {
                    setCurrentMembership(bootstrappedMembership);
                    setPermissions(bootstrappedCapabilities);
                } else {
                    setCurrentMembership(null);
                    setPermissions(emptyPermissions);
                }
            } catch {
                window.location.href = '/login';
                return;
            }

            setIsLoading(false);
        };

        initializeSession();
    }, [tenantId]);

    const switchTenant = (nextTenantId) => {
        if (!nextTenantId) {
            return;
        }

        setTenantContext(nextTenantId);
        setTenantId(String(nextTenantId));
    };

    return {
        isLoading,
        user,
        tenants,
        tenantId,
        currentMembership,
        permissions,
        switchTenant,
    };
}
