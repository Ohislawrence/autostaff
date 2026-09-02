import { usePage } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function AiRunDetail({ run }) {
    return (
        <PlatformLayout title={`AI Run #${run.id}`}>
            <h1 className="text-2xl font-bold text-gray-900 mb-6">AI Run #{run.id}</h1>
            <div className="grid grid-cols-3 gap-6">
                <div className="col-span-2 space-y-4">
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 className="font-semibold mb-3">Request</h3>
                        <div className="space-y-2 text-sm">
                            <div><span className="text-gray-500">Model:</span> <span className="font-medium">{run.model || 'deepseek-chat'}</span></div>
                            <div><span className="text-gray-500">Latency:</span> <span className="font-medium">{run.latency_ms || 'N/A'}ms</span></div>
                            <div><span className="text-gray-500">Input Tokens:</span> <span className="font-medium">{run.input_tokens || 0}</span></div>
                            <div><span className="text-gray-500">Output Tokens:</span> <span className="font-medium">{run.output_tokens || 0}</span></div>
                            <div><span className="text-gray-500">Cost:</span> <span className="font-medium">₦${parseFloat(run.estimated_cost || 0).toFixed(6)}</span></div>
                        </div>
                    </div>
                    {run.system_prompt && (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 className="font-semibold mb-3">System Prompt</h3>
                            <pre className="text-xs text-gray-600 whitespace-pre-wrap bg-gray-50 p-3 rounded max-h-48 overflow-y-auto">{run.system_prompt}</pre>
                        </div>
                    )}
                    {run.assistant_response && (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 className="font-semibold mb-3">AI Response</h3>
                            <pre className="text-xs text-gray-600 whitespace-pre-wrap bg-gray-50 p-3 rounded max-h-48 overflow-y-auto">{run.assistant_response}</pre>
                        </div>
                    )}
                    {run.tools_called && (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 className="font-semibold mb-3">Tools Called</h3>
                            <pre className="text-xs text-gray-600 whitespace-pre-wrap bg-gray-50 p-3 rounded max-h-48 overflow-y-auto">{JSON.stringify(JSON.parse(run.tools_called), null, 2)}</pre>
                        </div>
                    )}
                </div>
                <div className="space-y-4">
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 className="font-semibold mb-3">Context</h3>
                        <div className="space-y-2 text-sm">
                            <div><span className="text-gray-500">Status:</span> <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${run.status === 'success' ? 'bg-green-100 text-green-700' : run.status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'}`}>{run.status}</span></div>
                            <div><span className="text-gray-500">Organization:</span> <span className="font-medium">{run.conversation?.organization?.name || '—'}</span></div>
                            <div><span className="text-gray-500">AI Employee:</span> <span className="font-medium">{run.ai_employee?.name || '—'}</span></div>
                            <div><span className="text-gray-500">Conversation:</span> <span className="font-medium">#{run.conversation_id}</span></div>
                            <div><span className="text-gray-500">Date:</span> <span className="font-medium">{new Date(run.created_at).toLocaleString()}</span></div>
                            {run.correlation_id && <div><span className="text-gray-500">Trace:</span> <span className="font-mono text-xs">{run.correlation_id}</span></div>}
                        </div>
                    </div>
                </div>
            </div>
        </PlatformLayout>
    );
}