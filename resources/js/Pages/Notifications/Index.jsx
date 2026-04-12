import { useEffect, useState } from 'react';
import InlineNotice from '../../Components/InlineNotice';
import AppLayout from '../../Layouts/AppLayout';
import useAppSession from '../../hooks/useAppSession';

const formatNotificationDate = (value) => {
    if (!value) {
        return 'Just now';
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return 'Just now';
    }

    return parsed.toLocaleString();
};

const buildRealtimeNotification = (incoming) => ({
    id: incoming.id ?? `${incoming.type}-${Date.now()}`,
    type: incoming.type ?? 'WorkspaceEventNotification',
    data: incoming.data ?? incoming,
    read_at: incoming.read_at ?? null,
    created_at: incoming.created_at ?? new Date().toISOString(),
});

export default function NotificationsPage() {
    const session = useAppSession();
    const { isLoading, user } = session;

    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [isRealtimeActive, setIsRealtimeActive] = useState(false);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');

    const loadNotifications = async () => {
        try {
            const response = await window.axios.get('/api/v1/notifications');
            setNotifications(response?.data?.data ?? []);
            setUnreadCount(response?.data?.unread_count ?? 0);
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to load notifications.');
        }
    };

    useEffect(() => {
        if (!isLoading) {
            loadNotifications();
        }
    }, [isLoading]);

    useEffect(() => {
        if (isLoading || !user?.id || !window.Echo) {
            return;
        }

        const channelName = `App.Models.User.${user.id}`;
        const channel = window.Echo.private(channelName);

        channel.notification((incoming) => {
            const normalized = buildRealtimeNotification(incoming);

            setNotifications((current) => {
                if (current.some((item) => item.id === normalized.id)) {
                    return current;
                }

                return [normalized, ...current];
            });
            setUnreadCount((current) => current + 1);
            setMessage('New notification received in real time.');
        });

        setIsRealtimeActive(true);

        return () => {
            setIsRealtimeActive(false);
            window.Echo.leaveChannel(`private-${channelName}`);
        };
    }, [isLoading, user?.id]);

    const markRead = async (id) => {
        try {
            await window.axios.patch(`/api/v1/notifications/${id}/read`);
            setMessage('Notification marked as read.');
            await loadNotifications();
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to mark notification as read.');
        }
    };

    const markAllRead = async () => {
        try {
            await window.axios.patch('/api/v1/notifications/read-all');
            setMessage('All notifications marked as read.');
            await loadNotifications();
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to mark all notifications as read.');
        }
    };

    const clearRead = async () => {
        try {
            await window.axios.delete('/api/v1/notifications/read');
            setMessage('Read notifications cleared.');
            await loadNotifications();
        } catch (requestError) {
            setError(requestError?.response?.data?.message ?? 'Unable to clear read notifications.');
        }
    };

    if (isLoading) {
        return <div className="p-6 text-sm text-slate-600">Loading session...</div>;
    }

    return (
        <AppLayout title="Notifications" session={session}>
            <div className="space-y-4">
                <InlineNotice message={message} error={error} />

                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="text-base font-semibold text-slate-900">Inbox</h2>
                            <div className="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-600">
                                <span>Unread: {unreadCount}</span>
                                <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${isRealtimeActive ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>
                                    {isRealtimeActive ? 'Live updates on' : 'Live updates off'}
                                </span>
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <button type="button" onClick={markAllRead} className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Mark all read</button>
                            <button type="button" onClick={clearRead} className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear read</button>
                        </div>
                    </div>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="space-y-3">
                        {notifications.map((notification) => (
                            <article key={notification.id} className={`rounded-xl border p-4 ${notification.read_at ? 'border-slate-200 bg-white' : 'border-amber-200 bg-amber-50'}`}>
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm font-semibold text-slate-900">{notification?.data?.title ?? notification.type}</p>
                                        {notification?.data?.body ? (
                                            <p className="mt-1 text-sm text-slate-700">{notification.data.body}</p>
                                        ) : null}
                                        <p className="mt-1 text-xs text-slate-600">{formatNotificationDate(notification.created_at)}</p>
                                        <pre className="mt-2 whitespace-pre-wrap text-xs text-slate-700">{JSON.stringify(notification.data, null, 2)}</pre>
                                    </div>
                                    {!notification.read_at ? (
                                        <button type="button" onClick={() => markRead(notification.id)} className="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                                            Mark read
                                        </button>
                                    ) : null}
                                </div>
                            </article>
                        ))}
                        {notifications.length === 0 ? (
                            <p className="text-sm text-slate-500">No notifications yet.</p>
                        ) : null}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
