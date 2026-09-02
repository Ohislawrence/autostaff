import { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index({ leads, pipeline, filters }) {
    const { auth, flash } = usePage().props;
    const [viewMode, setViewMode] = useState('list'); // list or kanban

    const stageColors = {
        new: 'bg-gray-100 border-gray-300',
        contacted: 'bg-blue-50 border-blue-200',
        qualified: 'bg-purple-50 border-purple-200',
        proposal: 'bg-orange-50 border-orange-200',
        won: 'bg-green-50 border-green-200',
        lost: 'bg-red-50 border-red-200',
    };

    const stageLabels = {
        new: 'New', contacted: 'Contacted', qualified: 'Qualified',
        proposal: 'Proposal', won: 'Won', lost: 'Lost',
    };

    return (
        <TenantLayout header="Leads">
            <Head title="Leads" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}

            <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-3">
                    <h1 className="text-2xl font-bold text-gray-900">Leads ({leads?.total || 0})</h1>
                    <div className="flex bg-gray-100 rounded-lg p-0.5">
                        <button onClick={() => setViewMode('list')} className={`px-3 py-1 rounded text-xs font-medium ${viewMode === 'list' ? 'bg-white shadow text-gray-900' : 'text-gray-500'}`}>List</button>
                        <button onClick={() => setViewMode('kanban')} className={`px-3 py-1 rounded text-xs font-medium ${viewMode === 'kanban' ? 'bg-white shadow text-gray-900' : 'text-gray-500'}`}>Pipeline</button>
                    </div>
                </div>
                <form className="flex gap-2">
                    <input type="text" name="search" defaultValue={filters?.search} placeholder="Search leads..." className="px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                    <select name="stage" defaultValue={filters?.stage} className="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">All Stages</option>
                        {Object.entries(stageLabels).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                    </select>
                    <button type="submit" className="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">Filter</button>
                </form>
            </div>

            {viewMode === 'kanban' ? (
                /* Pipeline Kanban View */
                <div className="grid grid-cols-6 gap-3 overflow-x-auto pb-4">
                    {Object.entries(stageLabels).map(([stageKey, stageName]) => {
                        const stageLeads = pipeline?.[stageKey] 
                            ? (leads?.data?.filter(l => l.stage === stageKey) || [])
                            : [];
                        const count = pipeline?.[stageKey] || 0;
                        
                        return (
                            <div key={stageKey} className="bg-gray-50 rounded-xl p-3 min-w-[180px]">
                                <div className="flex items-center justify-between mb-2 px-1">
                                    <span className="text-xs font-bold text-gray-500 uppercase">{stageName}</span>
                                    <span className="text-xs font-bold text-gray-400">{count}</span>
                                </div>
                                <div className="space-y-2">
                                    {stageLeads.map(lead => (
                                        <Link key={lead.id} href={`/leads/${lead.id}`}
                                            className={`block bg-white border rounded-lg p-3 hover:shadow-md transition-shadow ${stageColors[lead.stage] || 'border-gray-200'}`}>
                                            <p className="font-medium text-gray-900 text-sm">
                                                {lead.customer?.first_name} {lead.customer?.last_name}
                                            </p>
                                            {lead.product_interest && (
                                                <p className="text-xs text-gray-500 mt-0.5">{lead.product_interest}</p>
                                            )}
                                            {lead.estimated_value > 0 && (
                                                <p className="text-xs font-medium text-green-600 mt-1">
                                                    ${parseFloat(lead.estimated_value).toLocaleString()}
                                                </p>
                                            )}
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                    {(!leads?.data || leads.data.length === 0) && (
                        <div className="col-span-6 text-center py-12 text-gray-400">
                            <p className="text-3xl mb-3">🎯</p>
                            <p>No leads yet. Leads will appear here when your AI Employee generates them.</p>
                        </div>
                    )}
                </div>
            ) : (
                /* List View */
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="text-left px-4 py-3 font-medium text-gray-500">Customer</th>
                                <th className="text-left px-4 py-3 font-medium text-gray-500">Stage</th>
                                <th className="text-left px-4 py-3 font-medium text-gray-500">Product</th>
                                <th className="text-left px-4 py-3 font-medium text-gray-500">Value</th>
                                <th className="text-left px-4 py-3 font-medium text-gray-500">Source</th>
                                <th className="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {leads?.data?.map(lead => (
                                <tr key={lead.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3">
                                        <Link href={`/leads/${lead.id}`} className="font-medium text-gray-900 hover:text-primary-600">
                                            {lead.customer?.first_name} {lead.customer?.last_name}
                                        </Link>
                                        <p className="text-xs text-gray-400">{lead.customer?.email}</p>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${stageColors[lead.stage]?.replace('border', 'bg').replace('-200', '-100 text-gray-700') || 'bg-gray-100 text-gray-600'}`}>
                                            {stageLabels[lead.stage] || lead.stage}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-xs text-gray-500">{lead.product_interest || '—'}</td>
                                    <td className="px-4 py-3 text-xs font-medium text-gray-900">
                                        {lead.estimated_value > 0 ? `$${parseFloat(lead.estimated_value).toLocaleString()}` : '—'}
                                    </td>
                                    <td className="px-4 py-3 text-xs text-gray-500">
                                        {lead.ai_employee ? '🤖 AI' : '👤 Manual'}
                                    </td>
                                    <td className="px-4 py-3 text-xs text-gray-400">
                                        {new Date(lead.created_at).toLocaleDateString()}
                                    </td>
                                </tr>
                            ))}
                            {(!leads?.data || leads.data.length === 0) && (
                                <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-400">No leads found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            )}
        </TenantLayout>
    );
}