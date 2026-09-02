import { usePage } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function Health({ health, queueStats }) {
    const healthColor = (s) => s === 'healthy' ? 'bg-green-500' : s === 'degraded' ? 'bg-yellow-500' : s === 'unavailable' ? 'bg-gray-400' : 'bg-red-500';

    return (
        <PlatformLayout title="System Health">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">System Health</h1>
            <div className="grid grid-cols-2 gap-6">
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">Services</h3>
                    <div className="space-y-3">
                        {Object.entries(health).map(([name, h]) => (
                            <div key={name} className="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                <span className="text-sm text-gray-600 capitalize">{name.replace('_', ' ')}</span>
                                <div className="flex items-center gap-2">
                                    <span className={`w-2 h-2 rounded-full ${healthColor(h.status)}`} />
                                    <span className="text-xs font-medium capitalize">{h.status}</span>
                                    {h.latency_ms && <span className="text-xs text-gray-400">{h.latency_ms}ms</span>}
                                    {h.reason && <span className="text-xs text-gray-400">{h.reason}</span>}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">Queue Overview</h3>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="p-4 bg-gray-50 rounded-lg"><p className="text-2xl font-bold text-gray-900">{queueStats?.pending_jobs || 0}</p><p className="text-xs text-gray-500">Pending Jobs</p></div>
                        <div className="p-4 bg-gray-50 rounded-lg"><p className="text-2xl font-bold text-red-600">{queueStats?.failed_jobs || 0}</p><p className="text-xs text-gray-500">Failed Jobs</p></div>
                    </div>
                    {queueStats?.queues && (
                        <div className="mt-4 space-y-2">
                            {queueStats.queues.map((q, i) => (
                                <div key={i} className="flex items-center justify-between text-sm"><span className="text-gray-600 capitalize">{q.queue || 'default'}</span><span className="font-medium">{q.count}</span></div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </PlatformLayout>
    );
}