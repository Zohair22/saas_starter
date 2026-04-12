import { useEffect, useMemo, useState } from 'react';
import InlineNotice from '../../Components/InlineNotice';
import { CardSkeleton, TableSkeleton } from '../../Components/LoadingSkeleton';
import AppLayout from '../../Layouts/AppLayout';
import useAppSession from '../../hooks/useAppSession';

function MetricCard({ label, value, helper, loading }) {
    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">{label}</p>
            <p className={`mt-2 text-3xl font-bold text-slate-900 ${loading ? 'animate-pulse text-slate-200' : ''}`}>
                {loading ? '-' : value}
            </p>
            {helper ? <p className="mt-1 text-xs text-slate-500">{helper}</p> : null}
        </article>
    );
}

export default function LandingAnalyticsIndex() {
    const session = useAppSession();
    const { isLoading } = session;

    const [days, setDays] = useState(30);
    const [isPageLoading, setIsPageLoading] = useState(true);
    const [message, setMessage] = useState('');
    const [report, setReport] = useState({
        range_days: 30,
        totals: { events: 0, unique_ctas: 0 },
        by_variant: [],
        by_cta: [],
        daily: [],
    });

    useEffect(() => {
        if (isLoading) {
            return;
        }

        const loadReport = async () => {
            setIsPageLoading(true);
            setMessage('');

            try {
                const response = await window.axios.get('/track/landing-report', {
                    params: { days },
                });
                setReport(response?.data ?? report);
            } catch (error) {
                const errorMessage = error?.response?.data?.message || 'Unable to load landing analytics report.';
                setMessage(errorMessage);
            } finally {
                setIsPageLoading(false);
            }
        };

        loadReport();
    }, [days, isLoading]);

    const maxDailyEvents = useMemo(() => {
        const values = report.daily.map((item) => Number(item.events) || 0);

        return Math.max(...values, 1);
    }, [report.daily]);

    return (
        <AppLayout title="Landing Analytics" session={session}>
            <section className="mb-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-lg font-semibold text-slate-900">A/B Conversion Report</h2>
                        <p className="mt-1 text-sm text-slate-600">
                            Landing CTA performance by experiment variant and click volume.
                        </p>
                    </div>

                    <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                        Range
                        <select
                            value={days}
                            onChange={(event) => setDays(Number(event.target.value))}
                            className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none"
                        >
                            <option value={7}>Last 7 days</option>
                            <option value={14}>Last 14 days</option>
                            <option value={30}>Last 30 days</option>
                            <option value={60}>Last 60 days</option>
                            <option value={90}>Last 90 days</option>
                        </select>
                    </label>
                </div>
            </section>

            {message ? <InlineNotice type="error" message={message} /> : null}

            <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <MetricCard
                    label="Tracked events"
                    value={report.totals.events}
                    helper={`Window: ${report.range_days} days`}
                    loading={isPageLoading}
                />
                <MetricCard
                    label="Unique CTAs"
                    value={report.totals.unique_ctas}
                    loading={isPageLoading}
                />
                <MetricCard
                    label="Top variant"
                    value={report.by_variant[0]?.ab_variant ?? 'n/a'}
                    helper={report.by_variant[0] ? `${report.by_variant[0].events} events` : undefined}
                    loading={isPageLoading}
                />
            </section>

            <section className="mt-5 grid gap-4 lg:grid-cols-2">
                <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 className="text-sm font-semibold tracking-wide text-slate-500 uppercase">Events by Variant</h3>
                    {isPageLoading ? (
                        <TableSkeleton rows={4} />
                    ) : report.by_variant.length === 0 ? (
                        <p className="mt-4 text-sm text-slate-500">No tracked events yet.</p>
                    ) : (
                        <div className="mt-3 overflow-hidden rounded-lg border border-slate-200">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-4 py-2 text-left font-medium text-slate-600">Variant</th>
                                        <th className="px-4 py-2 text-right font-medium text-slate-600">Events</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {report.by_variant.map((row) => (
                                        <tr key={row.ab_variant}>
                                            <td className="px-4 py-2 font-medium text-slate-800 uppercase">{row.ab_variant}</td>
                                            <td className="px-4 py-2 text-right text-slate-700">{row.events}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </article>

                <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 className="text-sm font-semibold tracking-wide text-slate-500 uppercase">Top CTA Clicks</h3>
                    {isPageLoading ? (
                        <TableSkeleton rows={6} />
                    ) : report.by_cta.length === 0 ? (
                        <p className="mt-4 text-sm text-slate-500">No CTA clicks have been tracked yet.</p>
                    ) : (
                        <div className="mt-3 overflow-hidden rounded-lg border border-slate-200">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-4 py-2 text-left font-medium text-slate-600">CTA</th>
                                        <th className="px-4 py-2 text-left font-medium text-slate-600">Variant</th>
                                        <th className="px-4 py-2 text-right font-medium text-slate-600">Events</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {report.by_cta.slice(0, 8).map((row) => (
                                        <tr key={`${row.cta_id}-${row.ab_variant}`}>
                                            <td className="px-4 py-2 font-medium text-slate-800">{row.cta_id}</td>
                                            <td className="px-4 py-2 uppercase text-slate-600">{row.ab_variant}</td>
                                            <td className="px-4 py-2 text-right text-slate-700">{row.events}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </article>
            </section>

            <section className="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 className="text-sm font-semibold tracking-wide text-slate-500 uppercase">Daily Event Trend</h3>
                {isPageLoading ? (
                    <CardSkeleton />
                ) : report.daily.length === 0 ? (
                    <p className="mt-4 text-sm text-slate-500">No daily trend data yet.</p>
                ) : (
                    <div className="mt-4 flex h-40 items-end gap-2 overflow-x-auto pb-2">
                        {report.daily.map((point) => {
                            const height = Math.max((Number(point.events) / maxDailyEvents) * 100, 4);

                            return (
                                <div key={point.date} className="flex min-w-10 flex-col items-center gap-1">
                                    <div className="w-8 rounded-t bg-amber-400/90" style={{ height: `${height}%` }} title={`${point.date}: ${point.events}`} />
                                    <span className="text-[10px] text-slate-500">{point.date.slice(5)}</span>
                                </div>
                            );
                        })}
                    </div>
                )}
            </section>
        </AppLayout>
    );
}
