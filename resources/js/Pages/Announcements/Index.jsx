import { Head, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index() {
    const { platformAnnouncements, flash } = usePage().props;

    const sorted = [...(platformAnnouncements || [])].sort(
        (a, b) => new Date(b.published_at) - new Date(a.published_at)
    );

    const typeStyles = {
        info: { border: 'border-l-blue-500 bg-blue-50', badge: 'bg-blue-100 text-blue-700' },
        warning: { border: 'border-l-amber-500 bg-amber-50', badge: 'bg-amber-100 text-amber-700' },
        success: { border: 'border-l-green-500 bg-green-50', badge: 'bg-green-100 text-green-700' },
        danger: { border: 'border-l-red-500 bg-red-50', badge: 'bg-red-100 text-red-700' },
    };

    const unreadCount = sorted.filter(a => !a.read_at).length;

    return (
        <TenantLayout header="All Announcements">
            <Head title="Announcements" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}

            <div className="flex items-center justify-between mb-6">
                <div>
                    <p className="text-sm text-gray-500">
                        Platform-wide updates from the admin team.
                        {unreadCount > 0 && <span className="ml-2 px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-medium">{unreadCount} unread</span>}
                    </p>
                </div>
                {unreadCount > 0 && (
                    <form method="POST" action="/announcements/mark-all-read">
                        <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''} />
                        <button type="submit" className="text-xs px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition font-medium">
                            Mark all as read
                        </button>
                    </form>
                )}
            </div>

            <div className="space-y-4">
                {sorted.length === 0 && (
                    <div className="text-center py-16">
                        <div className="text-4xl mb-3">📢</div>
                        <p className="text-sm text-gray-400">No announcements yet.</p>
                        <p className="text-xs text-gray-300 mt-1">Check back later for updates from the platform admin.</p>
                    </div>
                )}

                {sorted.map(a => {
                    const styles = typeStyles[a.type] || typeStyles.info;
                    const isRead = !!a.read_at;
                    return (
                        <div key={a.id} className={`bg-white rounded-xl shadow-sm border border-gray-200 border-l-4 overflow-hidden ${isRead ? 'opacity-75' : styles.border}`}>
                            <div className="p-5">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2 mb-1.5">
                                            {!isRead && <span className="w-2 h-2 bg-primary-500 rounded-full flex-shrink-0" />}
                                            <h3 className={`font-semibold text-gray-900`}>{a.title}</h3>
                                            <span className={`px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0 ${styles.badge}`}>
                                                {a.type}
                                            </span>
                                        </div>
                                        {a.body && <p className="text-sm text-gray-600 mt-1 whitespace-pre-line">{a.body}</p>}
                                        <div className="flex items-center gap-4 mt-3 text-xs text-gray-400">
                                            <span>📅 {new Date(a.published_at).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' })}</span>
                                            {isRead && <span className="text-green-600">✓ Read</span>}
                                        </div>
                                    </div>
                                    {!isRead && (
                                        <form method="POST" action={`/announcements/mark-read/${a.id}`}>
                                            <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''} />
                                            <button type="submit" className="text-xs px-3 py-1.5 bg-primary-50 text-primary-700 rounded-lg hover:bg-primary-100 transition font-medium flex-shrink-0">
                                                Mark read
                                            </button>
                                        </form>
                                    )}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </TenantLayout>
    );
}