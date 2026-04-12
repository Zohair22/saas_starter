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
};

const buildPermissions = (membership) => {
    if (!membership) {
        return emptyPermissions;
    }

    const roleFlags = membership?.role_flags ?? {};
    const canManage = Boolean(roleFlags.is_owner || roleFlags.is_admin);

    return {
        isTenantMember: true,
        canViewMemberships: true,
        canViewBilling: true,
        canManageProjects: canManage,
        canManageMemberships: canManage,
        canManageInvitations: canManage,
        canManageBilling: canManage,
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
                const [meResponse, tenantsResponse] = await Promise.all([
                    window.axios.get('/api/v1/me'),
                    window.axios.get('/api/v1/tenants'),
                ]);

                setUser(meResponse?.data?.data ?? null);
                setTenants(tenantsResponse?.data?.data ?? []);

                const firstTenantId = tenantsResponse?.data?.data?.[0]?.id;
                const activeTenantId = tenantId || (firstTenantId ? String(firstTenantId) : null);

                if (activeTenantId) {
                    setTenantContext(activeTenantId);
                }

                if (!tenantId) {
                    if (firstTenantId) {
                        setTenantId(String(firstTenantId));
                    }
                }

                if (activeTenantId) {
                    try {
                        const membershipsResponse = await window.axios.get('/api/v1/memberships');
                        const membership = membershipsResponse?.data?.meta?.current_membership?.data ?? null;
                        setCurrentMembership(membership);
                        setPermissions(buildPermissions(membership));
                    } catch {
                        setCurrentMembership(null);
                        setPermissions(emptyPermissions);
                    }
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
