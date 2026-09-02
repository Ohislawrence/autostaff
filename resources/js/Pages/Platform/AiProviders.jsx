import { usePage } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function AiProviders({ providers }) {
    return (
        <PlatformLayout title="AI Providers">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">AI Providers</h1>
            <div className="grid grid-cols-3 gap-4">
                {providers.map(p => (
                    <div key={p.key} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div className="flex items-center justify-between mb-3"><h3 className="font-semibold text-gray-900">{p.name}</h3><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${p.status==='active'?'bg-green-100 text-green-700':'bg-gray-100 text-gray-600'}`}>{p.status}</span></div>
                        {p.is_default&&<span className="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs font-medium mb-2 inline-block">Default</span>}
                        <div className="mt-3 space-y-1 text-xs text-gray-500"><p>Models: {p.models.join(', ')}</p>{p.config?.base_url&&<p>Endpoint: {p.config.base_url}</p>}</div>
                        <p className="text-xs text-gray-400 mt-3">Configure via <code className="bg-gray-100 px-1 rounded">.env</code></p>
                    </div>
                ))}
            </div>
        </PlatformLayout>
    );
}