import { useEffect } from 'react';

export default function ConfirmDialog({
    open,
    title,
    description,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    onConfirm,
    onCancel,
    isProcessing = false,
}) {
    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const handleEscape = (event) => {
            if (event.key === 'Escape' && !isProcessing) {
                onCancel();
            }
        };

        window.addEventListener('keydown', handleEscape);

        return () => {
            window.removeEventListener('keydown', handleEscape);
        };
    }, [open, onCancel, isProcessing]);

    if (!open) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <button
                type="button"
                aria-label="Close confirmation dialog"
                onClick={isProcessing ? undefined : onCancel}
                className="absolute inset-0 bg-slate-900/45"
            />

            <div
                role="dialog"
                aria-modal="true"
                aria-label={title}
                className="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-5 shadow-xl"
            >
                <h3 className="text-base font-semibold text-slate-900">{title}</h3>
                <p className="mt-2 text-sm text-slate-600">{description}</p>

                <div className="mt-5 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        onClick={onCancel}
                        disabled={isProcessing}
                        className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {cancelLabel}
                    </button>
                    <button
                        type="button"
                        onClick={onConfirm}
                        disabled={isProcessing}
                        className="rounded-md border border-rose-200 bg-rose-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {isProcessing ? 'Processing...' : confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}