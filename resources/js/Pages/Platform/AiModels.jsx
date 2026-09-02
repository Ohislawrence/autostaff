import { usePage } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function AiModels({ models }) {
    return (
        <PlatformLayout title="AI Models">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">AI Models</h1>
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"><table className="w-full text-sm"><thead className="bg-gray-50"><tr><th className="text-left px-4 py-3 font-medium text-gray-500">Model</th><th className="text-left px-4 py-3 font-medium text-gray-500">Provider</th><th className="text-left px-4 py-3 font-medium text-gray-500">Status</th><th className="text-left px-4 py-3 font-medium text-gray-500">Token Limit</th><th className="text-left px-4 py-3 font-medium text-gray-500">Available Plans</th><th className="text-left px-4 py-3 font-medium text-gray-500">Default</th></tr></thead><tbody className="divide-y divide-gray-100">{models.map((m,i)=>(<tr key={i}><td className="px-4 py-3 font-medium text-gray-900">{m.name}</td><td className="px-4 py-3 text-gray-500">{m.provider}</td><td className="px-4 py-3"><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${m.status==='active'?'bg-green-100 text-green-700':'bg-gray-100 text-gray-600'}`}>{m.status}</span></td><td className="px-4 py-3 text-gray-500">{m.token_limit?.toLocaleString()}</td><td className="px-4 py-3 text-xs">{m.plan_availability?.map(p=><span key={p} className="px-1.5 py-0.5 bg-gray-100 rounded mr-1">{p}</span>)}</td><td className="px-4 py-3">{m.is_default&&<span className="text-xs text-purple-600 font-medium">Default</span>}</td></tr>))}</tbody></table></div>
        </PlatformLayout>
    );
}