export default function InlineNotice({ type = 'error', message, className = '' }) {
    if (!message) {
        return null;
    }

    const styles =
        type === 'success'
            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
            : 'border-rose-200 bg-rose-50 text-rose-700';

    return <p className={`rounded-md border px-3 py-2 text-sm ${styles} ${className}`}>{message}</p>;
}
