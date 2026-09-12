import { Link } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function ProspectingSuppression({ entries }) {
    return (
        <TenantLayout header="Suppression List">
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">🚫 Do-Not-Contact List</h1>
                    <p className="text-sm text-gray-500 mt-1">People who have unsubscribed or been suppressed from outreach.</p>
                </div>
                <Link href="/prospecting" className="text-sm text-blue-600 hover:underline">← Back to prospecting</Link>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                {entries.length === 0 ? (
                    <p className="p-8 text-center text-gray-400">No suppressed contacts yet.</p>
                ) : (
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium text-gray-500">Email</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Reason</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Source</th>
                                <th className="px-4 py-3 font-medium text-gray-500">Added</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {entries.map((e) => (
                                <tr key={e.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 text-gray-800">{e.email}</td>
                                    <td className="px-4 py-3 text-gray-600 capitalize">{e.reason}</td>
                                    <td className="px-4 py-3 text-gray-600 capitalize">{e.source}</td>
                                    <td className="px-4 py-3 text-gray-500">{new Date(e.created_at).toLocaleDateString()}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </TenantLayout>
    );
}
