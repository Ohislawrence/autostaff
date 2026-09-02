import { Head, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index({ stats }) {
    const { flash, auth } = usePage().props;
    const currency = auth.organization?.currency || 'NGN';
    const currencySymbol = { USD: '$', NGN: '₦', GHS: 'GH₵', KES: 'KSh', ZAR: 'R', EUR: '€', GBP: '£', CAD: 'CA$', AUD: 'A$', INR: '₹', JPY: '¥' }[currency] || (currency + ' ');

    const metricCards = [
        { label: 'Total Conversations', value: stats.total_conversations, color: 'text-blue-600', icon: '💬' },
        { label: 'Total Messages', value: stats.total_messages, color: 'text-indigo-600', icon: '📝' },
        { label: 'Total Leads', value: stats.total_leads, color: 'text-green-600', icon: '🎯' },
        { label: 'Leads Converted', value: `${stats.converted_leads} (${stats.conversion_rate}%)`, color: 'text-emerald-600', icon: '✅' },
        { label: 'Total Orders', value: stats.total_orders, color: 'text-purple-600', icon: '📦' },
        { label: 'Revenue', value: `${currencySymbol}${parseFloat(stats.total_revenue || 0).toLocaleString(undefined, { maximumFractionDigits: 2 })}`, color: 'text-primary-600', icon: '💰' },
        { label: 'AI Runs', value: stats.total_ai_runs, color: 'text-orange-600', icon: '🤖' },
        { label: 'AI Cost', value: `$${stats.total_ai_cost}`, color: 'text-red-600', icon: '⚡' },
        { label: 'Avg Response', value: `${stats.avg_response_time_ms}ms`, color: 'text-cyan-600', icon: '⏱️' },
        { label: 'Tool Executions', value: stats.total_tool_executions, color: 'text-teal-600', icon: '🔧' },
        { label: 'Escalations', value: stats.escalated_conversations, color: 'text-yellow-600', icon: '🚨' },
        { label: 'AI Orders', value: stats.ai_orders, color: 'text-pink-600', icon: '🛒' },
    ];

    const maxConv = Math.max(...(stats.monthly_conversations || []).map(d => d.count), 1);
    const maxCost = Math.max(...(stats.monthly_ai_cost || []).map(d => d.cost), 0.01);

    return (
        <TenantLayout header="Analytics">
            <Head title="Analytics" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}

            {/* Metric Cards */}
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 mb-8">
                {metricCards.map(m => (
                    <div key={m.label} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <div className="flex items-center gap-2 mb-1">
                            <span className="text-lg">{m.icon}</span>
                        </div>
                        <p className={`text-xl font-bold ${m.color}`}>{m.value}</p>
                        <p className="text-xs text-gray-400">{m.label}</p>
                    </div>
                ))}
            </div>

            {/* Charts */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Monthly Conversations */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">Conversations (6 months)</h3>
                    <div className="flex items-end gap-2 h-32">
                        {(stats.monthly_conversations || []).map(d => (
                            <div key={d.month} className="flex-1 flex flex-col items-center gap-1">
                                <span className="text-xs text-gray-600 font-medium">{d.count}</span>
                                <div className="w-full bg-primary-100 rounded-t" style={{ height: `${(d.count / maxConv) * 100}%`, minHeight: 4 }}>
                                    <div className="bg-primary-500 h-full rounded-t" />
                                </div>
                                <span className="text-xs text-gray-400">{d.month}</span>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Monthly AI Cost */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">AI Cost (6 months)</h3>
                    <div className="flex items-end gap-2 h-32">
                        {(stats.monthly_ai_cost || []).map(d => (
                            <div key={d.month} className="flex-1 flex flex-col items-center gap-1">
                                <span className="text-xs text-gray-600 font-medium">${d.cost}</span>
                                <div className="w-full bg-orange-100 rounded-t" style={{ height: `${(d.cost / maxCost) * 100}%`, minHeight: 4 }}>
                                    <div className="bg-orange-500 h-full rounded-t" />
                                </div>
                                <span className="text-xs text-gray-400">{d.month}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Conversation Funnel */}
            <div className="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 className="font-semibold text-gray-900 mb-4">Conversation Funnel</h3>
                <div className="space-y-3">
                    {[
                        { label: 'Total', value: stats.total_conversations, max: stats.total_conversations || 1, color: 'bg-blue-500' },
                        { label: 'AI Handled', value: stats.ai_responses, max: stats.total_conversations || 1, color: 'bg-green-500' },
                        { label: 'Escalated', value: stats.escalated_conversations, max: stats.total_conversations || 1, color: 'bg-yellow-500' },
                        { label: 'Resolved', value: stats.resolved_conversations, max: stats.total_conversations || 1, color: 'bg-emerald-500' },
                    ].map(step => (
                        <div key={step.label} className="flex items-center gap-3">
                            <span className="text-xs text-gray-500 w-20">{step.label}</span>
                            <div className="flex-1 bg-gray-100 rounded-full h-6">
                                <div className={`${step.color} h-6 rounded-full flex items-center justify-end px-2`}
                                    style={{ width: `${Math.min((step.value / step.max) * 100, 100)}%` }}>
                                    <span className="text-xs text-white font-medium">{step.value}</span>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </TenantLayout>
    );
}