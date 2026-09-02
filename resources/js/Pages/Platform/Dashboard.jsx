import { usePage } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function Dashboard({ stats, health }) {
    const { auth } = usePage().props;
    const formatNumber = (n) => n?.toLocaleString() || '0';
    const formatCurrency = (n) => `₦${(parseFloat(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
    const healthColor = (s) => s === 'healthy' ? 'bg-green-500' : s === 'degraded' ? 'bg-yellow-500' : 'bg-red-500';

    return (
        <PlatformLayout title="Platform Dashboard">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">Platform Overview</h1>
            <div className="grid grid-cols-4 gap-3 mb-6">
                {[
                    { label: 'Organizations', value: formatNumber(stats.total_organizations), sub: `${stats.active_organizations} active`, color: 'text-blue-600' },
                    { label: 'Active Users', value: formatNumber(stats.active_users), sub: `${formatNumber(stats.total_users)} total`, color: 'text-green-600' },
                    { label: 'MRR', value: formatCurrency(stats.mrr), sub: `ARR: ${formatCurrency(stats.arr)}`, color: 'text-teal-600' },
                    { label: 'AI Conversations', value: formatNumber(stats.ai_conversations), sub: `${formatNumber(stats.ai_conversations_this_month)} this month`, color: 'text-purple-600' },
                    { label: 'AI Cost', value: formatCurrency(stats.ai_cost), sub: `${formatCurrency(stats.ai_cost_this_month)} this month`, color: 'text-red-600' },
                    { label: 'Messages', value: formatNumber(stats.ai_messages), sub: `${formatNumber(stats.ai_messages_this_month)} this month`, color: 'text-indigo-600' },
                    { label: 'Subscriptions', value: formatNumber(stats.total_subscriptions), sub: `${stats.churned_this_month} churned this month`, color: 'text-orange-600' },
                    { label: 'Failed AI', value: formatNumber(stats.failed_ai_runs), sub: `${formatNumber(stats.failed_jobs)} failed jobs`, color: 'text-rose-600' },
                ].map(s => (
                    <div key={s.label} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <p className={`text-xl font-bold ${s.color}`}>{s.value}</p>
                        <p className="text-xs text-gray-500">{s.label}</p>
                        <p className="text-xs text-gray-400 mt-0.5">{s.sub}</p>
                    </div>
                ))}
            </div>
            <div className="grid grid-cols-2 gap-6 mb-6">
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">Revenue (6 months)</h3>
                    <div className="flex items-end gap-2 h-28">
                        {stats.monthly_revenue?.map(d => (
                            <div key={d.month} className="flex-1 flex flex-col items-center">
                                <div className="w-full bg-green-100 rounded-t flex-1 relative" style={{ minHeight: 4 }}><div className="bg-green-500 w-full rounded-t absolute bottom-0" style={{ height: `${Math.max((d.revenue / Math.max(...stats.monthly_revenue.map(x => x.revenue || 0), 1)) * 100, 4)}%` }} /></div>
                                <span className="text-xs text-gray-400 mt-1">{d.month}</span>
                            </div>
                        ))}
                    </div>
                </div>
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">System Health</h3>
                    <div className="space-y-3">
                        {Object.entries(health).map(([name, h]) => (
                            <div key={name} className="flex items-center justify-between">
                                <span className="text-sm text-gray-600 capitalize">{name.replace('_', ' ')}</span>
                                <div className="flex items-center gap-2"><span className={`w-2 h-2 rounded-full ${healthColor(h.status)}`} /><span className="text-xs font-medium capitalize">{h.status}</span></div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </PlatformLayout>
    );
}