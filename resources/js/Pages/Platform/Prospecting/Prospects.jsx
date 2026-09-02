import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

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
    const [showAdd, setShowAdd] = useState(false);
    const [showImport, setShowImport] = useState(false);

    const [f, setF] = useState({
        search: filters.search || '',
        campaign: filters.campaign || '',
        status: filters.status || '',
        min_score: filters.min_score || '',
    });

    const addForm = useForm({
        campaign_id: filters.campaign || campaigns[0]?.id || '',
        name: '', email: '', title: '', company: '', company_size: '',
        industry: '', location: '', website: '', linkedin_url: '',
    });

    const csvForm = useForm({ campaign_id: filters.campaign || campaigns[0]?.id || '', csv: null });

    const applyFilters = (e) => {
        e.preventDefault();
        const params = {};
        if (f.search) params.search = f.search;
        if (f.campaign) params.campaign = f.campaign;
        if (f.status) params.status = f.status;
        if (f.min_score) params.min_score = f.min_score;
        router.get('/platform/prospecting/prospects', params, { preserveState: true });
    };

    const addProspect = (e) => {
        e.preventDefault();
        addForm.post(`/platform/prospecting/campaigns/${addForm.data.campaign_id}/prospects`, { onSuccess: () => { setShowAdd(false); addForm.reset(); } });
    };

    const importCsv = (e) => {
        e.preventDefault();
        csvForm.post(`/platform/prospecting/campaigns/${csvForm.data.campaign_id}/import`, { onSuccess: () => { setShowImport(false); csvForm.reset(); } });
    };

    const field = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none';
    const label = 'block text-xs font-medium text-gray-600 mb-1';

    return (
        <PlatformLayout title="Prospecting Prospects">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">🎯 Prospects</h1>
                <div className="flex gap-2">
                    <button onClick={() => setShowImport(!showImport)} className="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium">Import CSV</button>
                    <button onClick={() => setShowAdd(!showAdd)} className="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">+ Add Prospect</button>
                </div>
            </div>

            <form onSubmit={applyFilters} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4 grid grid-cols-5 gap-3">
                <input className={field} placeholder="Search name, email, company…" value={f.search} onChange={(e) => setF({ ...f, search: e.target.value })} />
                <select className={field} value={f.campaign} onChange={(e) => setF({ ...f, campaign: e.target.value })}>
                    <option value="">All campaigns</option>
                    {campaigns.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                </select>
                <select className={field} value={f.status} onChange={(e) => setF({ ...f, status: e.target.value })}>
                    <option value="">All statuses</option>
                    {statuses.map((s) => <option key={s} value={s}>{s}</option>)}
                </select>
                <input type="number" min="1" max="10" className={field} placeholder="Min score" value={f.min_score} onChange={(e) => setF({ ...f, min_score: e.target.value })} />
                <button type="submit" className="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium">Filter</button>
            </form>

            {showAdd && (
                <form onSubmit={addProspect} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-4">
                    <h3 className="font-semibold text-gray-900 mb-3">Add Prospect Manually</h3>
                    <div className="grid grid-cols-3 gap-3">
                        <div><label className={label}>Campaign *</label><select className={field} value={addForm.data.campaign_id} onChange={(e) => addForm.setData('campaign_id', e.target.value)} required>{campaigns.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}</select></div>
                        <div><label className={label}>Name</label><input className={field} value={addForm.data.name} onChange={(e) => addForm.setData('name', e.target.value)} /></div>
                        <div><label className={label}>Email</label><input className={field} value={addForm.data.email} onChange={(e) => addForm.setData('email', e.target.value)} /></div>
                        <div><label className={label}>Title</label><input className={field} value={addForm.data.title} onChange={(e) => addForm.setData('title', e.target.value)} /></div>
                        <div><label className={label}>Company</label><input className={field} value={addForm.data.company} onChange={(e) => addForm.setData('company', e.target.value)} /></div>
                        <div><label className={label}>Company Size</label><input className={field} value={addForm.data.company_size} onChange={(e) => addForm.setData('company_size', e.target.value)} /></div>
                        <div><label className={label}>Industry</label><input className={field} value={addForm.data.industry} onChange={(e) => addForm.setData('industry', e.target.value)} /></div>
                        <div><label className={label}>Location</label><input className={field} value={addForm.data.location} onChange={(e) => addForm.setData('location', e.target.value)} /></div>
                        <div><label className={label}>Website</label><input className={field} value={addForm.data.website} onChange={(e) => addForm.setData('website', e.target.value)} /></div>
                        <div className="col-span-2"><label className={label}>LinkedIn URL</label><input className={field} value={addForm.data.linkedin_url} onChange={(e) => addForm.setData('linkedin_url', e.target.value)} /></div>
                    </div>
                    <div className="flex gap-2 mt-3">
                        <button type="submit" disabled={addForm.processing} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Add</button>
                        <button type="button" onClick={() => setShowAdd(false)} className="px-4 py-2 border border-gray-200 rounded-lg text-sm">Cancel</button>
                    </div>
                </form>
            )}

            {showImport && (
                <form onSubmit={importCsv} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-4">
                    <h3 className="font-semibold text-gray-900 mb-3">Import Prospects from CSV</h3>
                    <p className="text-xs text-gray-500 mb-3">Columns: name, email, title, company, company_size, industry, location, website, linkedin_url (header row required).</p>
                    <div className="grid grid-cols-2 gap-3">
                        <div><label className={label}>Campaign *</label><select className={field} value={csvForm.data.campaign_id} onChange={(e) => csvForm.setData('campaign_id', e.target.value)} required>{campaigns.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}</select></div>
                        <div><label className={label}>CSV File *</label><input type="file" accept=".csv,.txt" className={field} onChange={(e) => csvForm.setData('csv', e.target.files[0])} required /></div>
                    </div>
                    <div className="flex gap-2 mt-3">
                        <button type="submit" disabled={csvForm.processing} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Import</button>
                        <button type="button" onClick={() => setShowImport(false)} className="px-4 py-2 border border-gray-200 rounded-lg text-sm">Cancel</button>
                    </div>
                </form>
            )}

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Prospect</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Company / Title</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Campaign</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Score</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Source</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Validation</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Actions</th>
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
                                    <p className="text-xs text-gray-400">{p.title || ''}</p>
                                </td>
                                <td className="px-4 py-3 text-xs text-gray-500">{p.campaign?.name}</td>
                                <td className="px-4 py-3">
                                    <span className={`inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold ${p.score >= 7 ? 'bg-green-100 text-green-700' : p.score >= 4 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600'}`}>{p.score || '–'}</span>
                                </td>
                                <td className="px-4 py-3"><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[p.status] || 'bg-gray-100 text-gray-600'}`}>{p.status}</span></td>
                                <td className="px-4 py-3 text-xs text-gray-500 capitalize">{p.source?.replace('_', ' ') || '—'}</td>
                                <td className="px-4 py-3"><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${validationColors[p.validation_status] || 'bg-gray-100 text-gray-600'}`}>{p.validation_status || 'pending'}</span></td>
                                <td className="px-4 py-3"><div className="flex gap-2"><Link href={`/platform/prospecting/prospects/${p.id}`} className="text-blue-600 text-xs font-medium hover:underline">View</Link><button onClick={() => { if (confirm('Suppress this prospect (add to do-not-contact)?')) router.post(`/platform/prospecting/prospects/${p.id}/suppress`); }} className="text-red-600 text-xs font-medium hover:underline">Suppress</button></div></td>
                            </tr>
                        ))}
                        {(prospects.data || []).length === 0 && <tr><td colSpan={8} className="px-4 py-8 text-center text-gray-400">No prospects found.</td></tr>}
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
        </PlatformLayout>
    );
}
