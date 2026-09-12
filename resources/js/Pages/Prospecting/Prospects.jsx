import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

const statusColors = {
    new: 'bg-gray-100 text-gray-600',
    qualified: 'bg-green-100 text-green-700',
    disqualified: 'bg-red-100 text-red-700',
    contacted: 'bg-teal-100 text-teal-700',
    replied: 'bg-orange-100 text-orange-700',
    converted: 'bg-blue-100 text-blue-700',
    bounced: 'bg-red-100 text-red-600',
    unsubscribed: 'bg-gray-100 text-gray-500',
};

const validationColors = {
    valid: 'bg-green-100 text-green-700',
    risky: 'bg-yellow-100 text-yellow-700',
    role: 'bg-blue-100 text-blue-700',
    disposable: 'bg-red-100 text-red-700',
    invalid: 'bg-red-100 text-red-700',
    pending: 'bg-gray-100 text-gray-600',
};

export default function ProspectingProspects({ prospects, campaigns, statuses, filters }) {
    const [f, setF] = useState({
        search: filters.search || '',
        campaign: filters.campaign || '',
        status: filters.status || '',
        min_score: filters.min_score || '',
    });

    const applyFilters = (e) => {
        e.preventDefault();
        const params = {};
        if (f.search) params.search = f.search;
        if (f.campaign) params.campaign = f.campaign;
        if (f.status) params.status = f.status;
        if (f.min_score) params.min_score = f.min_score;
        router.get('/prospecting/prospects', params, { preserveState: true });
    };

    const field = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none';

    return (
        <TenantLayout header="Prospects">
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">🎯 Prospects</h1>
                    <p className="text-sm text-gray-500 mt-1">Ranked by fit score — reach out to the strongest matches first.</p>
                </div>
                <Link href="/prospecting/campaigns" className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Manage Campaigns</Link>
            </div>

            <form onSubmit={applyFilters} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4 grid grid-cols-2 md:grid-cols-4 gap-3">
                <input className={field} placeholder="Search name, email, company…" value={f.search} onChange={(e) => setF({ ...f, search: e.target.value })} />
                <select className={field} value={f.campaign} onChange={(e) => setF({ ...f, campaign: e.target.value })}>
                    <option value="">All campaigns</option>
                    {campaigns.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                </select>
                <select className={field} value={f.status} onChange={(e) => setF({ ...f, status: e.target.value })}>
                    <option value="">All statuses</option>
                    {statuses.map((s) => <option key={s} value={s}>{s}</option>)}
                </select>
                <div className="flex gap-2">
                    <input type="number" className={field} placeholder="Min score" value={f.min_score} onChange={(e) => setF({ ...f, min_score: e.target.value })} />
                    <button type="submit" className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium">Filter</button>
                </div>
            </form>

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50 text-left">
                        <tr>
                            <th className="px-4 py-3 font-medium text-gray-500">Prospect</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Company</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Score</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Status</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Validation</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {(prospects.data || []).map((p) => (
                            <tr key={p.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3">
                                    <p className="font-medium text-gray-900">{p.name || '—'}</p>
                                    <p className="text-xs text-gray-400">{p.email || 'no email'}</p>
                                </td>
                                <td className="px-4 py-3">
                                    <p className="text-gray-700">{p.company || '—'}</p>
                                    <p className="text-xs text-gray-400">{p.location || ''}</p>
                                </td>
                                <td className="px-4 py-3">
                                    <span className={`inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold ${p.score >= 7 ? 'bg-green-100 text-green-700' : p.score >= 4 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600'}`}>{p.score || '–'}</span>
                                </td>
                                <td className="px-4 py-3"><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[p.status] || 'bg-gray-100 text-gray-600'}`}>{p.status}</span></td>
                                <td className="px-4 py-3"><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${validationColors[p.validation_status] || 'bg-gray-100 text-gray-600'}`}>{p.validation_status || 'pending'}</span></td>
                                <td className="px-4 py-3"><div className="flex gap-2"><Link href={`/prospecting/prospects/${p.id}`} className="text-blue-600 text-xs font-medium hover:underline">View</Link><button onClick={() => { if (confirm('Suppress this prospect (add to do-not-contact)?')) router.post(`/prospecting/prospects/${p.id}/suppress`); }} className="text-red-600 text-xs font-medium hover:underline">Suppress</button></div></td>
                            </tr>
                        ))}
                        {(prospects.data || []).length === 0 && <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-400">No prospects found. Run a hunt from your campaign to discover prospects.</td></tr>}
                    </tbody>
                </table>
                {(prospects.links?.length > 3) && (
                    <div className="px-4 py-3 border-t border-gray-100 flex gap-2 text-xs">
                        {prospects.links.map((link, i) => (
                            <button key={i} onClick={() => link.url && router.get(link.url, {}, { preserveState: true })} disabled={!link.url} dangerouslySetInnerHTML={{ __html: link.label }} className={`px-3 py-1.5 rounded ${link.active ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'} ${!link.url ? 'opacity-40 cursor-not-allowed' : ''}`} />
                        ))}
                    </div>
                )}
            </div>
        </TenantLayout>
    );
}
