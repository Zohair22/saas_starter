const TOKEN_KEY = 'auth_token';
const TENANT_KEY = 'tenant_id';

export const getAuthToken = () => window.localStorage.getItem(TOKEN_KEY);

export const getTenantId = () => window.localStorage.getItem(TENANT_KEY);

export const getAuthHeaders = () => {
    const token = getAuthToken();
    const tenantId = getTenantId();

    const headers = {};

    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    if (tenantId) {
        headers['X-Tenant-ID'] = tenantId;
    }

    return headers;
};

export const setSession = ({ token, tenantId = null }) => {
    if (token) {
        window.localStorage.setItem(TOKEN_KEY, token);
        window.axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    }

    if (tenantId) {
        window.localStorage.setItem(TENANT_KEY, String(tenantId));
        window.axios.defaults.headers.common['X-Tenant-ID'] = String(tenantId);
    }
};

export const setTenantContext = (tenantId) => {
    if (!tenantId) {
        return;
    }

    window.localStorage.setItem(TENANT_KEY, String(tenantId));
    window.axios.defaults.headers.common['X-Tenant-ID'] = String(tenantId);
};

export const localClearSession = () => {
    window.localStorage.removeItem(TOKEN_KEY);
    window.localStorage.removeItem(TENANT_KEY);

    delete window.axios.defaults.headers.common.Authorization;
    delete window.axios.defaults.headers.common['X-Tenant-ID'];
};

export const clearSession = async () => {
    try {
        if (getAuthToken()) {
            await window.axios.post('/api/v1/logout');
        }
    } catch {
        // Ignore logout API errors on local session cleanup.
    }

    localClearSession();
};
