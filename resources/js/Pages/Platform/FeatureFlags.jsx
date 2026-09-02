import { usePage, useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function FeatureFlags({ flags, plans }) {
    const { put } = useForm();
    return (
        <PlatformLayout title="Feature Flags">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">Feature Flags</h1>
            <p className="text-sm text-gray-500 mb-6">Control which features are available across plans and organizations.</p>
            <div className="space-y-3">{flags.map(f => (
                <div key={f.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center justify-between">
                    <div><h3 className="font-semibold text-gray-900">{f.name}</h3><p className="text-xs text-gray-400 font-mono">{f.key}</p></div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2"><span className="text-xs text-gray-500">Plans:</span>{plans.map(p => (<span key={p.id} className={`px-2 py-0.5 rounded text-xs ${f.plan_availability ? (JSON.parse(f.plan_availability || '[]').includes(p.slug) ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-400') : 'bg-purple-100 text-purple-700'}`}>{p.name.substring(0,4)}</span>))}</div>
                        <button onClick={() => put(`/platform/features/${f.id}`, { data: { is_enabled: !f.is_enabled } })} className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${f.is_enabled ? 'bg-purple-600' : 'bg-gray-300'}`}><span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${f.is_enabled ? 'translate-x-6' : 'translate-x-1'}`} /></button>
                    </div>
                </div>
            ))}</div>
        </PlatformLayout>
    );
}