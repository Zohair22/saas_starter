import { useEffect, useState } from 'react';
import { getAuthToken, getTenantId, setTenantContext } from '../session';

export default function useAppSession() {
    const [isLoading, setIsLoading] = useState(true);
    const [user, setUser] = useState(null);
    const [tenants, setTenants] = useState([]);
    const [tenantId, setTenantId] = useState(getTenantId());

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

                if (!tenantId) {
                    const firstTenant = tenantsResponse?.data?.data?.[0]?.id;

                    if (firstTenant) {
                        setTenantContext(firstTenant);
                        setTenantId(String(firstTenant));
                    }
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
        switchTenant,
    };
}
