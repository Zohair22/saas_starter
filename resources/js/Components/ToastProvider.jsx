import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

const ToastContext = createContext(null);

const makeToast = (type, message) => ({
    id: `${Date.now()}-${Math.random()}`,
    type,
    message,
});

export function ToastProvider({ children }) {
    const [toasts, setToasts] = useState([]);

    const dismissToast = useCallback((id) => {
        setToasts((current) => current.filter((toast) => toast.id !== id));
    }, []);

    const pushToast = useCallback((type, message) => {
        const toast = makeToast(type, message);

        setToasts((current) => [...current, toast]);

        window.setTimeout(() => {
            dismissToast(toast.id);
        }, 4000);
    }, [dismissToast]);

    const success = useCallback((message) => pushToast('success', message), [pushToast]);
    const error = useCallback((message) => pushToast('error', message), [pushToast]);

    useEffect(() => {
        const handleGlobalToast = (event) => {
            pushToast(event.detail.type, event.detail.message);
        };

        window.addEventListener('app:toast', handleGlobalToast);

        return () => {
            window.removeEventListener('app:toast', handleGlobalToast);
        };
    }, [pushToast]);

    const contextValue = useMemo(
        () => ({ success, error }),
        [success, error],
    );

    return (
        <ToastContext.Provider value={contextValue}>
            {children}

            <div className="pointer-events-none fixed right-4 top-4 z-50 flex w-full max-w-sm flex-col gap-2">
                {toasts.map((toast) => (
                    <div
                        key={toast.id}
                        className={`pointer-events-auto rounded-lg border px-3 py-2 text-sm shadow-md ${
                            toast.type === 'success'
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                                : 'border-rose-200 bg-rose-50 text-rose-800'
                        }`}
                    >
                        <div className="flex items-start justify-between gap-2">
                            <span>{toast.message}</span>
                            <button
                                type="button"
                                onClick={() => dismissToast(toast.id)}
                                className="text-xs font-semibold uppercase tracking-wide opacity-70 hover:opacity-100"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                ))}
            </div>
        </ToastContext.Provider>
    );
}

export function useToastContext() {
    const context = useContext(ToastContext);

    if (!context) {
        throw new Error('useToastContext must be used within ToastProvider.');
    }

    return context;
}
