export function CardSkeleton() {
    return <div className="h-24 animate-pulse rounded-xl border border-slate-200 bg-slate-100" />;
}

export function TableSkeleton({ rows = 5 }) {
    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="h-12 animate-pulse border-b border-slate-200 bg-slate-100" />
            <div className="space-y-2 p-3">
                {Array.from({ length: rows }).map((_, index) => (
                    <div key={index} className="h-10 animate-pulse rounded-md bg-slate-100" />
                ))}
            </div>
        </div>
    );
}
