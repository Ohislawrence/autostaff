import { Link } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function ProspectingIndex({ stats, recentReplies, recentCampaigns, searchConfigured }) {
    const cards = [
        { label: 'Campaigns', value: stats.campaigns, sub: `${stats.active_campaigns} active`, color: 'text-blue-600' },
        { label: 'Prospects', value: stats.prospects, sub: 'discovered', color: 'text-purple-600' },
        { label: 'Qualified', value: stats.qualified, sub: 'ready to outreach', color: 'text-green-600' },
        { label: 'Contacted', value: stats.contacted, sub: 'emails sent', color: 'text-teal-600' },
        { label: 'Replies', value: stats.replied, sub: 'prospects replied', color: 'text-orange-600' },
        { label: 'Avg Score', value: stats.avg_score, sub: 'out of 10', color: 'text-indigo-600' },
    ];

    const statusColors = {
        draft: 'bg-gray-100 text-gray-600',
        active: 'bg-green-100 text-green-700',
        paused: 'bg-yellow-100 text-yellow-700',
        completed: 'bg-blue-100 text-blue-700',
    };

    return (
        <TenantLayout header="Prospecting">
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">🎯 Find Your First Customers</h1>
                    <p className="text-sm text-gray-500 mt-1">Nomdal finds prospects, qualifies them, and follows up automatically.</p>
                </div>
                <div className="flex gap-2">
                    <Link href="/prospecting/settings" className="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">🔎 Search settings</Link>
                    <Link href="/prospecting/personas" className="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">🧑‍💼 Personas</Link>
                    <Link href="/prospecting/prospects" className="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">View Prospects</Link>
                    <Link href="/prospecting/campaigns" className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Manage Campaigns</Link>
                </div>
            </div>

            {!searchConfigured && (
                <div className="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-sm flex items-center justify-between">
                    <span>⚠️ Web search isn't connected — Nomdal is using AI-generated suggestions. Connect a search provider for live results.</span>
                    <Link href="/prospecting/settings" className="shrink-0 ml-4 text-blue-600 font-medium hover:underline">Connect</Link>
                </div>
            )}

            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                {cards.map((c) => (
                    <div key={c.label} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <p className={`text-xl font-bold ${c.color}`}>{c.value}</p>
                        <p className="text-xs text-gray-500">{c.label}</p>
                        <p className="text-xs text-gray-400 mt-0.5">{c.sub}</p>
                    </div>
                ))}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">💬 Latest Replies</h3>
                    <div className="space-y-3">
                        {recentReplies.map((p) => (
                            <div key={p.id} className="flex items-center justify-between text-sm">
                                <div>
                                    <p className="font-medium text-gray-900">{p.name || p.email}</p>
                                    <p className="text-xs text-gray-500">{p.company} · {p.campaign?.name}</p>
                                </div>
                                <div className="text-right">
                                    <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">{p.score}/10</span>
                                    <p className="text-xs text-gray-400 mt-1">{new Date(p.replied_at).toLocaleString()}</p>
                                </div>
                            </div>
                        ))}
                        {recentReplies.length === 0 && <p className="text-sm text-gray-400">No replies yet.</p>}
                    </div>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">📋 Recent Campaigns</h3>
                    <div className="space-y-3">
                        {recentCampaigns.map((c) => (
                            <div key={c.id} className="flex items-center justify-between text-sm">
                                <div>
                                    <p className="font-medium text-gray-900">{c.name}</p>
                                    <p className="text-xs text-gray-500">{c.prospects_count} prospects</p>
                                </div>
                                <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[c.status] || 'bg-gray-100 text-gray-600'}`}>{c.status}</span>
                            </div>
                        ))}
                        {recentCampaigns.length === 0 && <p className="text-sm text-gray-400">No campaigns yet.</p>}
                    </div>
                </div>
            </div>
        </TenantLayout>
    );
}
