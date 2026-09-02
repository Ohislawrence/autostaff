import { usePage } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function Integrations({ integrations }) {
    const { auth } = usePage().props;
    const statusColors = { connected: 'bg-green-100 text-green-700', not_configured: 'bg-gray-100 text-gray-600' };

    return (
        <PlatformLayout title="Integrations">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">Integrations</h1>
            <div className="grid grid-cols-3 gap-4">
                {integrations.map(i => (
                    <div key={i.key} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div className="flex items-center justify-between mb-3">
                            <div className="flex items-center gap-2"><span className="text-xl">{i.icon}</span><h3 className="font-semibold text-gray-900">{i.name}</h3></div>
                            <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[i.status] || 'bg-gray-100 text-gray-600'}`}>{i.status.replace('_', ' ')}</span>
                        </div>
                        <p className="text-xs text-gray-500">Configure via <code className="bg-gray-100 px-1 rounded">.env</code></p>
                    </div>
                ))}
            </div>
        </PlatformLayout>
    );
}