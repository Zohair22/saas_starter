import { useEffect, useState } from 'react';

export default function StepUpModal({
    open,
    title = 'Security verification',
    description = 'Confirm this sensitive action with your authenticator code or a recovery code.',
    confirmLabel = 'Verify and continue',
    cancelLabel = 'Cancel',
    error = '',
    isProcessing = false,
    onConfirm,
    onCancel,
}) {
    const [mfaCode, setMfaCode] = useState('');
    const [recoveryCode, setRecoveryCode] = useState('');

    useEffect(() => {
        if (!open) {
            return;
        }

        setMfaCode('');
        setRecoveryCode('');
    }, [open]);

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
    }, [open, isProcessing, onCancel]);

    if (!open) {
        return null;
    }

    const submit = () => {
        onConfirm({
            mfa_code: mfaCode.trim() || undefined,
            mfa_recovery_code: recoveryCode.trim() || undefined,
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <button
                type="button"
                aria-label="Close security verification dialog"
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

                <div className="mt-4 space-y-3">
                    <label className="block text-sm text-slate-700">
                        Authenticator code
                        <input
                            type="text"
                            inputMode="numeric"
                            maxLength={6}
                            value={mfaCode}
                            onChange={(event) => setMfaCode(event.target.value)}
                            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                            disabled={isProcessing}
                        />
                    </label>

                    <label className="block text-sm text-slate-700">
                        Recovery code
                        <input
                            type="text"
                            value={recoveryCode}
                            onChange={(event) => setRecoveryCode(event.target.value)}
                            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                            disabled={isProcessing}
                        />
                    </label>

                    {error ? (
                        <div className="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">
                            {error}
                        </div>
                    ) : null}
                </div>

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
                        onClick={submit}
                        disabled={isProcessing}
                        className="rounded-md border border-indigo-200 bg-indigo-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {isProcessing ? 'Verifying...' : confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}
