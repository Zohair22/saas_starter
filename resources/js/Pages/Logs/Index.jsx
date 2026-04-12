import { Fragment, useEffect, useMemo, useState } from 'react';
import InlineNotice from '../../Components/InlineNotice';
import { CardSkeleton, TableSkeleton } from '../../Components/LoadingSkeleton';
import AppLayout from '../../Layouts/AppLayout';
import useAppSession from '../../hooks/useAppSession';

const TAB_ACTIVITY = 'activity';
const TAB_AUDIT = 'audit';

const formatDateTime = (value) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString();
};

const normalize = (value) => String(value ?? '').toLowerCase();

const isPlainObject = (value) => Object.prototype.toString.call(value) === '[object Object]';

const flattenObject = (value, prefix = '', result = {}) => {
    if (Array.isArray(value)) {
        value.forEach((item, index) => {
            const key = prefix ? `${prefix}[${index}]` : `[${index}]`;

            if (isPlainObject(item) || Array.isArray(item)) {
                flattenObject(item, key, result);
            } else {
                result[key] = item;
            }
        });

        return result;
    }

    if (isPlainObject(value)) {
        Object.entries(value).forEach(([key, nestedValue]) => {
            const nextKey = prefix ? `${prefix}.${key}` : key;

            if (isPlainObject(nestedValue) || Array.isArray(nestedValue)) {
                flattenObject(nestedValue, nextKey, result);
            } else {
                result[nextKey] = nestedValue;
            }
        });

        return result;
    }

    if (prefix) {
        result[prefix] = value;
    }

    return result;
};

const buildAuditDiff = (oldValues, newValues) => {
    const oldFlat = flattenObject(oldValues ?? {});
    const newFlat = flattenObject(newValues ?? {});
    const allKeys = new Set([...Object.keys(oldFlat), ...Object.keys(newFlat)]);

    const added = [];
    const removed = [];
    const changed = [];
    const entries = [];

    allKeys.forEach((key) => {
        const hasOld = Object.prototype.hasOwnProperty.call(oldFlat, key);
        const hasNew = Object.prototype.hasOwnProperty.call(newFlat, key);

        if (!hasOld && hasNew) {
            added.push(key);
            entries.push({
                type: 'added',
                key,
                oldValue: undefined,
                newValue: newFlat[key],
            });
            return;
        }

        if (hasOld && !hasNew) {
            removed.push(key);
            entries.push({
                type: 'removed',
                key,
                oldValue: oldFlat[key],
                newValue: undefined,
            });
            return;
        }

        if (hasOld && hasNew) {
            const oldValue = JSON.stringify(oldFlat[key]);
            const newValue = JSON.stringify(newFlat[key]);

            if (oldValue !== newValue) {
                changed.push(key);
                entries.push({
                    type: 'changed',
                    key,
                    oldValue: oldFlat[key],
                    newValue: newFlat[key],
                });
            }
        }
    });

    return {
        added,
        removed,
        changed,
        entries,
    };
};

const formatJson = (value) => {
    if (value === null || value === undefined) {
        return '{}';
    }

    try {
        return JSON.stringify(value, null, 2);
    } catch {
        return String(value);
    }
};

export default function LogsIndex() {
    const session = useAppSession();
    const { isLoading } = session;

    const [activeTab, setActiveTab] = useState(TAB_ACTIVITY);
    const [isPageLoading, setIsPageLoading] = useState(true);
    const [logs, setLogs] = useState([]);
    const [meta, setMeta] = useState(null);
    const [page, setPage] = useState(1);
    const [query, setQuery] = useState('');
    const [message, setMessage] = useState('');
    const [expandedLogId, setExpandedLogId] = useState(null);
    const [showOnlyChangedKeys, setShowOnlyChangedKeys] = useState(false);
    const [hideUnchangedRows, setHideUnchangedRows] = useState(false);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        const searchParams = new URLSearchParams(window.location.search);
        const tab = searchParams.get('tab');
        const changedOnly = searchParams.get('changedOnly');
        const hideUnchanged = searchParams.get('hideUnchanged');

        if (tab === TAB_AUDIT || tab === TAB_ACTIVITY) {
            setActiveTab(tab);
        }

        if (changedOnly === '1') {
            setShowOnlyChangedKeys(true);
        }

        if (hideUnchanged === '1') {
            setHideUnchangedRows(true);
        }
    }, []);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        const searchParams = new URLSearchParams(window.location.search);
        searchParams.set('tab', activeTab);

        if (activeTab === TAB_AUDIT && showOnlyChangedKeys) {
            searchParams.set('changedOnly', '1');
        } else {
            searchParams.delete('changedOnly');
        }

        if (activeTab === TAB_AUDIT && hideUnchangedRows) {
            searchParams.set('hideUnchanged', '1');
        } else {
            searchParams.delete('hideUnchanged');
        }

        const nextUrl = `${window.location.pathname}?${searchParams.toString()}`;
        window.history.replaceState(null, '', nextUrl);
    }, [activeTab, hideUnchangedRows, showOnlyChangedKeys]);

    useEffect(() => {
        setPage(1);
        setExpandedLogId(null);
        setShowOnlyChangedKeys(false);
        setHideUnchangedRows(false);
    }, [activeTab]);

    useEffect(() => {
        if (isLoading) {
            return;
        }

        const fetchLogs = async () => {
            setIsPageLoading(true);
            setMessage('');

            try {
                const endpoint = activeTab === TAB_AUDIT ? '/api/v1/audit-logs' : '/api/v1/activity-logs';
                const response = await window.axios.get(endpoint, {
                    params: { page },
                });

                setLogs(response?.data?.data ?? []);
                setMeta(response?.data?.meta ?? null);
                setExpandedLogId(null);
            } catch (error) {
                const errorMessage = error?.response?.data?.message || 'Unable to load logs.';
                setMessage(errorMessage);
            } finally {
                setIsPageLoading(false);
            }
        };

        fetchLogs();
    }, [activeTab, isLoading, page]);

    const filteredLogs = useMemo(() => {
        const normalizedQuery = normalize(query).trim();

        const queryFiltered = normalizedQuery
            ? logs.filter((log) => {
                const actor = `${log?.actor?.name ?? ''} ${log?.actor?.email ?? ''}`;
                const searchable = activeTab === TAB_AUDIT
                ? [
                    log?.action,
                    log?.subject_type,
                    log?.subject_id,
                    log?.ip_address,
                    actor,
                    JSON.stringify(log?.old_values ?? {}),
                    JSON.stringify(log?.new_values ?? {}),
                ]
                : [
                    log?.action,
                    log?.subject_type,
                    log?.subject_id,
                    actor,
                    JSON.stringify(log?.metadata ?? {}),
                ];

                return searchable.some((value) => normalize(value).includes(normalizedQuery));
            })
            : logs;

        if (!hideUnchangedRows || activeTab !== TAB_AUDIT) {
            return queryFiltered;
        }

        return queryFiltered.filter((log) => {
            const diff = buildAuditDiff(log?.old_values, log?.new_values);
            return diff.entries.length > 0;
        });
    }, [activeTab, hideUnchangedRows, logs, query]);

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    const canGoPrev = Number(meta?.current_page ?? page) > 1;
    const canGoNext = Number(meta?.current_page ?? page) < Number(meta?.last_page ?? page);
    const isAudit = activeTab === TAB_AUDIT;

    return (
        <AppLayout title="Logs" session={session}>
            {isPageLoading ? (
                <div className="space-y-4">
                    <CardSkeleton />
                    <TableSkeleton rows={8} />
                </div>
            ) : (
                <>
                    <section className="mb-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-base font-semibold text-slate-900">Unified logs workspace</h2>
                                <p className="mt-1 text-sm text-slate-600">
                                    Switch between activity events and audit forensics without leaving this page.
                                </p>
                            </div>
                            <div className="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1">
                                <button
                                    type="button"
                                    onClick={() => setActiveTab(TAB_ACTIVITY)}
                                    className={`rounded-md px-3 py-1.5 text-sm font-medium transition ${
                                        !isAudit ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-white'
                                    }`}
                                >
                                    Activity
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setActiveTab(TAB_AUDIT)}
                                    className={`rounded-md px-3 py-1.5 text-sm font-medium transition ${
                                        isAudit ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-white'
                                    }`}
                                >
                                    Audit
                                </button>
                            </div>
                        </div>
                    </section>

                    <section className="mb-4 grid gap-4 sm:grid-cols-3">
                        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2">
                            <p className="text-sm font-medium text-slate-500">{isAudit ? 'Change History' : 'Event Stream'}</p>
                            <p className="mt-2 text-sm text-slate-600">
                                {isAudit
                                    ? 'Track before/after changes with request context for compliance and incident analysis.'
                                    : 'Track member actions and domain events for operational visibility.'}
                            </p>
                        </article>
                        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-sm font-medium text-slate-500">Records</p>
                            <p className="mt-2 text-2xl font-semibold text-slate-900">{meta?.total ?? logs.length}</p>
                        </article>
                    </section>

                    <section className="mb-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <label className="block">
                            <span className="mb-1 block text-sm font-medium text-slate-700">Filter current page</span>
                            <input
                                type="text"
                                value={query}
                                onChange={(event) => setQuery(event.target.value)}
                                placeholder={isAudit ? 'Search action, actor, subject, ip, values...' : 'Search action, actor, subject, metadata...'}
                                className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                            />
                        </label>

                        {isAudit ? (
                            <div className="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                                <label className="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={showOnlyChangedKeys}
                                        onChange={(event) => setShowOnlyChangedKeys(event.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300"
                                    />
                                    Show only changed keys
                                </label>
                                <label className="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={hideUnchangedRows}
                                        onChange={(event) => setHideUnchangedRows(event.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300"
                                    />
                                    Hide unchanged rows
                                </label>
                            </div>
                        ) : null}
                    </section>

                    <InlineNotice message={message} className="mb-4" />

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">Action</th>
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">Actor</th>
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">Subject</th>
                                    {isAudit ? <th className="px-4 py-3 text-left font-medium text-slate-600">IP</th> : null}
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">Created</th>
                                    <th className="px-4 py-3 text-right font-medium text-slate-600">Details</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {filteredLogs.map((log) => {
                                    const isExpanded = expandedLogId === log.id;
                                    const auditDiff = isAudit ? buildAuditDiff(log.old_values, log.new_values) : null;

                                    return (
                                        <Fragment key={log.id}>
                                            <tr>
                                                <td className="px-4 py-3 font-medium text-slate-900">{log.action}</td>
                                                <td className="px-4 py-3 text-slate-700">{log?.actor?.name ?? log?.actor?.email ?? '-'}</td>
                                                <td className="px-4 py-3 text-slate-600">{`${log.subject_type ?? '-'} #${log.subject_id ?? '-'}`}</td>
                                                {isAudit ? <td className="px-4 py-3 text-slate-600">{log.ip_address || '-'}</td> : null}
                                                <td className="px-4 py-3 text-slate-600">{formatDateTime(log.created_at)}</td>
                                                <td className="px-4 py-3 text-right">
                                                    <button
                                                        type="button"
                                                        onClick={() => setExpandedLogId((current) => (current === log.id ? null : log.id))}
                                                        className="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                                    >
                                                        {isExpanded ? 'Hide' : 'View'}
                                                    </button>
                                                </td>
                                            </tr>
                                            {isExpanded ? (
                                                <tr>
                                                    <td colSpan={isAudit ? 6 : 5} className="bg-slate-50 px-4 py-3">
                                                        {isAudit ? (
                                                            <div className="space-y-3">
                                                                <div className="grid gap-2 sm:grid-cols-3">
                                                                    <div className="rounded-md border border-emerald-200 bg-emerald-50 p-2">
                                                                        <p className="text-[11px] font-semibold tracking-wide text-emerald-700 uppercase">Added</p>
                                                                        <p className="mt-1 text-sm font-semibold text-emerald-800">{auditDiff.added.length}</p>
                                                                    </div>
                                                                    <div className="rounded-md border border-rose-200 bg-rose-50 p-2">
                                                                        <p className="text-[11px] font-semibold tracking-wide text-rose-700 uppercase">Removed</p>
                                                                        <p className="mt-1 text-sm font-semibold text-rose-800">{auditDiff.removed.length}</p>
                                                                    </div>
                                                                    <div className="rounded-md border border-amber-200 bg-amber-50 p-2">
                                                                        <p className="text-[11px] font-semibold tracking-wide text-amber-700 uppercase">Changed</p>
                                                                        <p className="mt-1 text-sm font-semibold text-amber-800">{auditDiff.changed.length}</p>
                                                                    </div>
                                                                </div>

                                                                {(auditDiff.added.length > 0 || auditDiff.removed.length > 0 || auditDiff.changed.length > 0) ? (
                                                                    <div className="rounded-md border border-slate-200 bg-white p-2">
                                                                        <p className="text-[11px] font-semibold tracking-wide text-slate-500 uppercase">Changed keys</p>
                                                                        <div className="mt-1 flex flex-wrap gap-1.5">
                                                                            {auditDiff.added.map((key) => (
                                                                                <span key={`added-${key}`} className="rounded border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">+ {key}</span>
                                                                            ))}
                                                                            {auditDiff.removed.map((key) => (
                                                                                <span key={`removed-${key}`} className="rounded border border-rose-200 bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">- {key}</span>
                                                                            ))}
                                                                            {auditDiff.changed.map((key) => (
                                                                                <span key={`changed-${key}`} className="rounded border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">~ {key}</span>
                                                                            ))}
                                                                        </div>
                                                                    </div>
                                                                ) : null}

                                                                {showOnlyChangedKeys ? (
                                                                    <div className="overflow-hidden rounded-md border border-slate-200 bg-white">
                                                                        <table className="min-w-full divide-y divide-slate-200 text-xs">
                                                                            <thead className="bg-slate-50">
                                                                                <tr>
                                                                                    <th className="px-2 py-1.5 text-left font-semibold text-slate-600">Type</th>
                                                                                    <th className="px-2 py-1.5 text-left font-semibold text-slate-600">Key</th>
                                                                                    <th className="px-2 py-1.5 text-left font-semibold text-slate-600">Old</th>
                                                                                    <th className="px-2 py-1.5 text-left font-semibold text-slate-600">New</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody className="divide-y divide-slate-100">
                                                                                {auditDiff.entries.length > 0 ? auditDiff.entries.map((entry) => (
                                                                                    <tr key={`${log.id}-${entry.type}-${entry.key}`}>
                                                                                        <td className="px-2 py-1.5 text-slate-700">{entry.type}</td>
                                                                                        <td className="px-2 py-1.5 font-medium text-slate-900">{entry.key}</td>
                                                                                        <td className="px-2 py-1.5 text-slate-700">{formatJson(entry.oldValue)}</td>
                                                                                        <td className="px-2 py-1.5 text-slate-700">{formatJson(entry.newValue)}</td>
                                                                                    </tr>
                                                                                )) : (
                                                                                    <tr>
                                                                                        <td colSpan={4} className="px-2 py-3 text-center text-slate-500">No changed keys in this record.</td>
                                                                                    </tr>
                                                                                )}
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                ) : (
                                                                    <div className="grid gap-3 sm:grid-cols-2">
                                                                        <div>
                                                                            <p className="mb-1 text-xs font-semibold tracking-wide text-slate-500 uppercase">Old values</p>
                                                                            <pre className="max-h-56 overflow-auto rounded-md border border-slate-200 bg-white p-2 text-xs text-slate-700">{formatJson(log.old_values)}</pre>
                                                                        </div>
                                                                        <div>
                                                                            <p className="mb-1 text-xs font-semibold tracking-wide text-slate-500 uppercase">New values</p>
                                                                            <pre className="max-h-56 overflow-auto rounded-md border border-slate-200 bg-white p-2 text-xs text-slate-700">{formatJson(log.new_values)}</pre>
                                                                        </div>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <div>
                                                                <p className="mb-1 text-xs font-semibold tracking-wide text-slate-500 uppercase">Metadata</p>
                                                                <pre className="max-h-56 overflow-auto rounded-md border border-slate-200 bg-white p-2 text-xs text-slate-700">{formatJson(log.metadata)}</pre>
                                                            </div>
                                                        )}
                                                    </td>
                                                </tr>
                                            ) : null}
                                        </Fragment>
                                    );
                                })}
                                {filteredLogs.length === 0 ? (
                                    <tr>
                                        <td colSpan={isAudit ? 6 : 5} className="px-4 py-8 text-center text-slate-500">
                                            No logs found for this filter.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </section>

                    <section className="mt-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-slate-600">
                            Page {meta?.current_page ?? page} of {meta?.last_page ?? 1}
                        </p>
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() => setPage((current) => Math.max(1, current - 1))}
                                disabled={!canGoPrev}
                                className="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                Previous
                            </button>
                            <button
                                type="button"
                                onClick={() => setPage((current) => current + 1)}
                                disabled={!canGoNext}
                                className="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                Next
                            </button>
                        </div>
                    </section>
                </>
            )}
        </AppLayout>
    );
}
