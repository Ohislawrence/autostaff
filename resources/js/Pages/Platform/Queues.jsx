import { Link, usePage } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function Queues({ queues }) {
    const statusColor = (s) => s === 'healthy' ? 'bg-green-500' : s === 'busy' ? 'bg-yellow-500' : 'bg-red-500';

    return (
        <PlatformLayout title="Queue Management">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">Queue Management</h1>
            <div className="grid grid-cols-2 gap-4">
                {queues.map(q => (
                    <div key={q.name} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="font-semibold text-gray-900 capitalize">{q.name}</h3>
                            <span className={`w-3 h-3 rounded-full ${statusColor(q.status)}`} />
                        </div>
                        <div className="grid grid-cols-3 gap-3 text-center">
                            <div className="p-3 bg-gray-50 rounded-lg"><p className="text-lg font-bold text-gray-900">{q.pending}</p><p className="text-xs text-gray-500">Pending</p></div>
                            <div className="p-3 bg-gray-50 rounded-lg"><p className="text-lg font-bold text-gray-900">{q.processing}</p><p className="text-xs text-gray-500">Processing</p></div>
                            <div className="p-3 bg-gray-50 rounded-lg"><p className="text-lg font-bold text-red-600">{q.failed}</p><p className="text-xs text-gray-500">Failed</p></div>
                        </div>
                        <Link href={`/platform/failed-jobs?queue=${q.name}`} className="text-xs text-purple-600 hover:text-purple-700 mt-3 inline-block">View failed →</Link>
                    </div>
                ))}
            </div>
        </PlatformLayout>
    );
}