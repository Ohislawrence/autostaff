import { usePage } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function Tickets({ tickets, filters }) {
    const { flash } = usePage().props;
    const statusColors = { open: 'bg-blue-100 text-blue-700', pending: 'bg-yellow-100 text-yellow-700', escalated: 'bg-orange-100 text-orange-700', resolved: 'bg-green-100 text-green-700', closed: 'bg-gray-100 text-gray-600' };

    return (
        <PlatformLayout title="Support Tickets">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">Support Tickets</h1>
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50"><tr><th className="text-left px-4 py-3 font-medium text-gray-500">Subject</th><th className="text-left px-4 py-3 font-medium text-gray-500">Status</th><th className="text-left px-4 py-3 font-medium text-gray-500">Priority</th><th className="text-left px-4 py-3 font-medium text-gray-500">Date</th></tr></thead>
                    <tbody className="divide-y divide-gray-100">
                        {tickets?.data?.map(t => (
                            <tr key={t.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3 font-medium text-gray-900">{t.subject}</td>
                                <td className="px-4 py-3"><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[t.status] || 'bg-gray-100 text-gray-600'}`}>{t.status}</span></td>
                                <td className="px-4 py-3 text-xs text-gray-500 capitalize">{t.priority}</td>
                                <td className="px-4 py-3 text-xs text-gray-400">{new Date(t.created_at).toLocaleDateString()}</td>
                            </tr>
                        ))}
                        {(!tickets?.data || tickets.data.length === 0) && <tr><td colSpan={4} className="px-4 py-8 text-center text-gray-400">No tickets yet.</td></tr>}
                    </tbody>
                </table>
            </div>
        </PlatformLayout>
    );
}