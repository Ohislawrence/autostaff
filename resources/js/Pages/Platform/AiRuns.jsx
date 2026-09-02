import { usePage } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function AiRuns({ runs, filters }) {
    const { flash } = usePage().props;

    return (
        <PlatformLayout title="AI Runs">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">AI Runs</h1>
                <form className="flex gap-2">
                    <select name="status" defaultValue={filters.status} className="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">All Status</option>
                        <option value="success">Success</option>
                        <option value="failed">Failed</option>
                        <option value="error">Error</option>
                    </select>
                    <button type="submit" className="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm">Filter</button>
                </form>
            </div>
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50"><tr>
                        <th className="text-left px-4 py-3 font-medium text-gray-500">ID</th>
                        <th className="text-left px-4 py-3 font-medium text-gray-500">Organization</th>
                        <th className="text-left px-4 py-3 font-medium text-gray-500">AI Employee</th>
                        <th className="text-left px-4 py-3 font-medium text-gray-500">Model</th>
                        <th className="text-left px-4 py-3 font-medium text-gray-500">Tokens</th>
                        <th className="text-left px-4 py-3 font-medium text-gray-500">Cost</th>
                        <th className="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                        <th className="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                    </tr></thead>
                    <tbody className="divide-y divide-gray-100">
                        {runs?.data?.map(r => (
                            <tr key={r.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3 text-xs font-mono text-gray-900">#{r.id}</td>
                                <td className="px-4 py-3 text-gray-500 text-xs">{r.organization_id}</td>
                                <td className="px-4 py-3 text-xs text-gray-500">{r.ai_employee?.name || '—'}</td>
                                <td className="px-4 py-3 text-xs text-gray-500">{r.model || 'deepseek-chat'}</td>
                                <td className="px-4 py-3 text-xs text-gray-500">{(r.input_tokens || 0) + (r.output_tokens || 0)}</td>
                                <td className="px-4 py-3 text-xs text-gray-500">₦${parseFloat(r.estimated_cost || 0).toFixed(4)}</td>
                                <td className="px-4 py-3"><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${r.status === 'success' ? 'bg-green-100 text-green-700' : r.status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'}`}>{r.status}</span></td>
                                <td className="px-4 py-3 text-xs text-gray-400">{new Date(r.created_at).toLocaleString()}</td>
                            </tr>
                        ))}
                        {(!runs?.data || runs.data.length === 0) && <tr><td colSpan={8} className="px-4 py-8 text-center text-gray-400">No AI runs yet.</td></tr>}
                    </tbody>
                </table>
            </div>
        </PlatformLayout>
    );
}