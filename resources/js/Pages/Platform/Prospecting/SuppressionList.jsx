import { useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function SuppressionList({ entries }) {
    const remove = useForm({ email: '' });
    const reasonColors = {
        unsubscribe: 'bg-orange-100 text-orange-700',
        bounce: 'bg-red-100 text-red-700',
        complaint: 'bg-red-100 text-red-700',
        dnc: 'bg-gray-100 text-gray-600',
        invalid: 'bg-gray-100 text-gray-600',
    };

    return (
        <PlatformLayout title="Suppression List">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">🚫 Do-Not-Contact List</h1>
            <p className="text-sm text-gray-500 mb-6">Emails on this list are never contacted — by any campaign or AI employee.</p>

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
                <form onSubmit={(e) => { e.preventDefault(); remove.post('/platform/prospecting/suppression/remove'); }} className="flex gap-3">
                    <input className="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Remove an email from the list" value={remove.data.email} onChange={(e) => remove.setData('email', e.target.value)} />
                    <button type="submit" disabled={remove.processing} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Remove</button>
                </form>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Email</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Reason</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Source</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Added</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {entries.map((e) => (
                            <tr key={e.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3 text-gray-800">{e.email}</td>
                                <td className="px-4 py-3"><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${reasonColors[e.reason] || 'bg-gray-100 text-gray-600'}`}>{e.reason}</span></td>
                                <td className="px-4 py-3 text-xs text-gray-500 capitalize">{e.source?.replace('_', ' ')}</td>
                                <td className="px-4 py-3 text-xs text-gray-400">{new Date(e.created_at).toLocaleDateString()}</td>
                            </tr>
                        ))}
                        {entries.length === 0 && <tr><td colSpan={4} className="px-4 py-8 text-center text-gray-400">No suppressed emails.</td></tr>}
                    </tbody>
                </table>
            </div>
        </PlatformLayout>
    );
}
