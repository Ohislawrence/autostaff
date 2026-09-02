import { usePage, useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function FailedJobs({ jobs, queues, filters, total }) {
    const { flash } = usePage().props;
    const { post } = useForm();

    return (
        <PlatformLayout title="Failed Jobs">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">Failed Jobs ({total})</h1>
                <div className="flex gap-2">
                    <form className="flex gap-2">
                        <select name="queue" defaultValue={filters.queue} className="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">All Queues</option>
                            {queues.map(q => <option key={q} value={q}>{q}</option>)}
                        </select>
                        <button type="submit" className="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm">Filter</button>
                    </form>
                    <button onClick={() => { if (confirm('Clear all failed jobs?')) post('/platform/failed-jobs/clear'); }} className="px-4 py-2 bg-red-600 text-white rounded-lg text-sm">Clear All</button>
                </div>
            </div>
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50"><tr><th className="text-left px-4 py-3 font-medium text-gray-500">Job</th><th className="text-left px-4 py-3 font-medium text-gray-500">Queue</th><th className="text-left px-4 py-3 font-medium text-gray-500">Failed At</th><th className="text-left px-4 py-3 font-medium text-gray-500">Actions</th></tr></thead>
                    <tbody className="divide-y divide-gray-100">
                        {jobs?.data?.map(j => (
                            <tr key={j.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3"><p className="font-medium text-gray-900">{j.payload ? JSON.parse(j.payload).displayName || 'Unknown Job' : 'Unknown Job'}</p><p className="text-xs text-red-500 mt-0.5 truncate max-w-xs">{j.exception?.substring(0, 100)}</p></td>
                                <td className="px-4 py-3 text-xs"><span className="px-2 py-0.5 bg-gray-100 rounded-full">{j.queue}</span></td>
                                <td className="px-4 py-3 text-xs text-gray-400">{new Date(j.failed_at).toLocaleString()}</td>
                                <td className="px-4 py-3"><button onClick={() => post(`/platform/failed-jobs/${j.id}/retry`)} className="text-xs px-2 py-1 bg-green-50 text-green-600 rounded">Retry</button></td>
                            </tr>
                        ))}
                        {(!jobs?.data || jobs.data.length === 0) && <tr><td colSpan={4} className="px-4 py-8 text-center text-gray-400">No failed jobs.</td></tr>}
                    </tbody>
                </table>
            </div>
        </PlatformLayout>
    );
}