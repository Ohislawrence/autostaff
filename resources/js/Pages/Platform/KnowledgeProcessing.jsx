import { Link, usePage } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function KnowledgeProcessing({ sources, stats, filters }) {
    const { flash } = usePage().props;

    return (
        <PlatformLayout title="Knowledge Processing">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">Knowledge Processing Monitor</h1>
            <div className="grid grid-cols-5 gap-3 mb-6">
                {[
                    { label: 'Total', value: stats.total, color: 'text-blue-600' },
                    { label: 'Completed', value: stats.completed, color: 'text-green-600' },
                    { label: 'Processing', value: stats.processing, color: 'text-yellow-600' },
                    { label: 'Failed', value: stats.failed, color: 'text-red-600' },
                    { label: 'Pending', value: stats.pending, color: 'text-gray-600' },
                ].map(s => (
                    <div key={s.label} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <p className={`text-xl font-bold ${s.color}`}>{s.value}</p>
                        <p className="text-xs text-gray-500">{s.label}</p>
                    </div>
                ))}
            </div>
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50"><tr><th className="text-left px-4 py-3 font-medium text-gray-500">Document</th><th className="text-left px-4 py-3 font-medium text-gray-500">Organization</th><th className="text-left px-4 py-3 font-medium text-gray-500">Type</th><th className="text-left px-4 py-3 font-medium text-gray-500">Status</th><th className="text-left px-4 py-3 font-medium text-gray-500">Date</th></tr></thead>
                    <tbody className="divide-y divide-gray-100">
                        {sources?.data?.map(s => (
                            <tr key={s.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3 font-medium text-gray-900">{s.title}</td>
                                <td className="px-4 py-3 text-gray-500">{s.organization?.name}</td>
                                <td className="px-4 py-3 text-xs uppercase text-gray-500">{s.type}</td>
                                <td className="px-4 py-3"><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${s.status === 'completed' ? 'bg-green-100 text-green-700' : s.status === 'failed' ? 'bg-red-100 text-red-700' : s.status === 'processing' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600'}`}>{s.status}</span></td>
                                <td className="px-4 py-3 text-xs text-gray-400">{new Date(s.created_at).toLocaleDateString()}</td>
                            </tr>
                        ))}
                        {(!sources?.data || sources.data.length === 0) && <tr><td colSpan={5} className="px-4 py-8 text-center text-gray-400">No knowledge processing activity.</td></tr>}
                    </tbody>
                </table>
            </div>
        </PlatformLayout>
    );
}