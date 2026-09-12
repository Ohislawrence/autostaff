import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

const statusColors = {
    draft: 'bg-gray-100 text-gray-600',
    active: 'bg-green-100 text-green-700',
    paused: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-blue-100 text-blue-700',
};

export default function ProspectingCampaigns({ campaigns, personas, defaultSender, searchConfigured, employee }) {
    const [editingId, setEditingId] = useState(null);
    const [showForm, setShowForm] = useState(false);

    const emptyForm = {
        name: '', description: '', offer: '', tone: 'professional',
        sender_name: defaultSender.name || '', sender_email: defaultSender.email || '',
        daily_limit: 25, auto_outreach: false,
        buyer_persona_id: '',
        icp_industry: '', icp_company_size: '', icp_geography: '', icp_job_titles: '',
        icp_keywords: '', icp_exclusions: '', icp_budget: '', icp_pain_points: '',
        postal_address: '', from_domain: '', compliance_regions: 'us, uk',
        blocked_sources: '', blocked_regions: '', allow_ai_generated: true,
        max_per_hour: 50, require_approval_ai_contacts: false,
    };

    const { data, setData, post, put, processing, reset } = useForm(emptyForm);

    const toForm = (c) => {
        const icp = c.icp || {};
        return {
            name: c.name || '', description: c.description || '', offer: c.offer || '', tone: c.tone || 'professional',
            sender_name: c.sender_name || defaultSender.name || '', sender_email: c.sender_email || defaultSender.email || '',
            daily_limit: c.daily_limit || 25, auto_outreach: !!c.auto_outreach,
            buyer_persona_id: c.buyer_persona_id || '',
            icp_industry: (icp.industry || []).join(', '), icp_company_size: icp.company_size || '',
            icp_geography: (icp.geography || []).join(', '), icp_job_titles: (icp.job_titles || []).join(', '),
            icp_keywords: (icp.keywords || []).join(', '), icp_exclusions: (icp.exclusions || []).join(', '),
            icp_budget: icp.budget || '', icp_pain_points: icp.pain_points || '',
            postal_address: c.postal_address || '', from_domain: c.from_domain || '',
            compliance_regions: (c.compliance_regions || ['us', 'uk']).join(', '),
            blocked_sources: (c.sourcing_rules?.blocked_sources || []).join(', '),
            blocked_regions: (c.sourcing_rules?.blocked_regions || []).join(', '),
            allow_ai_generated: c.sourcing_rules?.allow_ai_generated !== false,
            max_per_hour: c.max_per_hour || 50,
            require_approval_ai_contacts: !!c.require_approval_ai_contacts,
        };
    };

    const startCreate = () => { setEditingId(null); reset(); setData(emptyForm); setShowForm(true); };
    const startEdit = (c) => { setEditingId(c.id); setData(toForm(c)); setShowForm(true); };

    const submit = (e) => {
        e.preventDefault();
        if (editingId) put(`/prospecting/campaigns/${editingId}`, { onSuccess: () => setShowForm(false) });
        else post('/prospecting/campaigns', { onSuccess: () => setShowForm(false) });
    };

    const field = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none';
    const label = 'block text-xs font-medium text-gray-600 mb-1';

    return (
        <TenantLayout header="Prospecting Campaigns">
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">🎯 Prospecting Campaigns</h1>
                    <p className="text-sm text-gray-500 mt-1">Define who you want to reach, then let Nomdal hunt, qualify, and outreach.</p>
                </div>
                <button onClick={startCreate} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">+ New Campaign</button>
            </div>

            {employee && (
                <div className="mb-6 p-4 bg-indigo-50 border border-indigo-200 text-indigo-800 rounded-xl text-sm flex items-center justify-between">
                    <span>Showing campaigns for <strong>{employee.name}</strong></span>
                    <Link href="/prospecting/campaigns" className="shrink-0 ml-4 text-blue-600 font-medium hover:underline">Show all</Link>
                </div>
            )}

            {!searchConfigured && (
                <div className="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-sm flex items-center justify-between">
                    <span>⚠️ Web search isn't connected — hunts will use AI-generated suggestions.</span>
                    <Link href="/prospecting/settings" className="shrink-0 ml-4 text-blue-600 font-medium hover:underline">Connect</Link>
                </div>
            )}

            {showForm && (
                <form onSubmit={submit} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6 grid grid-cols-2 gap-4">
                    <div className="col-span-2"><label className={label}>Campaign Name *</label><input className={field} value={data.name} onChange={(e) => setData('name', e.target.value)} required /></div>
                    <div className="col-span-2"><label className={label}>Offer / Value Prop</label><input className={field} placeholder="e.g. Professional websites for SMEs" value={data.offer} onChange={(e) => setData('offer', e.target.value)} /></div>
                    <div className="col-span-2">
                        <label className={label}>Buyer persona (optional)</label>
                        <select className={field} value={data.buyer_persona_id} onChange={(e) => setData('buyer_persona_id', e.target.value)}>
                            <option value="">None — use ICP only</option>
                            {(personas || []).map((p) => <option key={p.id} value={p.id}>{p.avatar || '🧑‍💼'} {p.name}</option>)}
                        </select>
                    </div>
                    <div className="col-span-2"><label className={label}>Description</label><textarea className={field} rows={2} value={data.description} onChange={(e) => setData('description', e.target.value)} /></div>

                    <div><label className={label}>Tone</label><select className={field} value={data.tone} onChange={(e) => setData('tone', e.target.value)}>{['professional', 'friendly', 'persuasive', 'concise'].map((t) => <option key={t} value={t}>{t}</option>)}</select></div>
                    <div><label className={label}>Daily Limit</label><input type="number" className={field} value={data.daily_limit} onChange={(e) => setData('daily_limit', e.target.value)} /></div>

                    <div><label className={label}>Sender Name</label><input className={field} value={data.sender_name} onChange={(e) => setData('sender_name', e.target.value)} /></div>
                    <div><label className={label}>Sender Email</label><input className={field} value={data.sender_email} onChange={(e) => setData('sender_email', e.target.value)} /></div>

                    <div className="col-span-2 border-t border-gray-100 pt-4">
                        <h4 className="font-semibold text-gray-800 text-sm mb-3">Ideal Customer Profile</h4>
                    </div>
                    <div><label className={label}>Industry (comma separated)</label><input className={field} placeholder="SMEs, retail, clinics" value={data.icp_industry} onChange={(e) => setData('icp_industry', e.target.value)} /></div>
                    <div><label className={label}>Company Size</label><input className={field} placeholder="5–50 employees" value={data.icp_company_size} onChange={(e) => setData('icp_company_size', e.target.value)} /></div>
                    <div><label className={label}>Geography</label><input className={field} placeholder="Nigeria, Lagos" value={data.icp_geography} onChange={(e) => setData('icp_geography', e.target.value)} /></div>
                    <div><label className={label}>Job Titles</label><input className={field} placeholder="Founder, CEO" value={data.icp_job_titles} onChange={(e) => setData('icp_job_titles', e.target.value)} /></div>
                    <div><label className={label}>Keywords</label><input className={field} placeholder="website, booking" value={data.icp_keywords} onChange={(e) => setData('icp_keywords', e.target.value)} /></div>
                    <div><label className={label}>Budget</label><input className={field} placeholder="₦150k–₦500k" value={data.icp_budget} onChange={(e) => setData('icp_budget', e.target.value)} /></div>
                    <div className="col-span-2"><label className={label}>Pain Points</label><input className={field} placeholder="outdated website, missed leads" value={data.icp_pain_points} onChange={(e) => setData('icp_pain_points', e.target.value)} /></div>

                    <div className="col-span-2 border-t border-gray-100 pt-4 flex items-center gap-2">
                        <input type="checkbox" id="auto" checked={data.auto_outreach} onChange={(e) => setData('auto_outreach', e.target.checked)} />
                        <label htmlFor="auto" className="text-sm text-gray-700">Auto-send outreach after qualification</label>
                    </div>

                    <div className="col-span-2 flex gap-3 pt-2">
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50">{editingId ? 'Save Changes' : 'Create Campaign'}</button>
                        <button type="button" onClick={() => setShowForm(false)} className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium">Cancel</button>
                    </div>
                </form>
            )}

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50 text-left">
                        <tr>
                            <th className="px-4 py-3 font-medium text-gray-500">Campaign</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Status</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Prospects</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Qualified</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Contacted</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Replies</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {campaigns.map((c) => (
                            <tr key={c.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3">
                                    <p className="font-medium text-gray-900">{c.name}</p>
                                    <p className="text-xs text-gray-400">{(c.icp?.industry || []).join(', ') || 'No ICP set'}</p>
                                </td>
                                <td className="px-4 py-3"><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[c.status] || 'bg-gray-100 text-gray-600'}`}>{c.status}</span></td>
                                <td className="px-4 py-3 text-gray-600">{c.prospects_count}</td>
                                <td className="px-4 py-3 text-green-600">{c.qualified_count}</td>
                                <td className="px-4 py-3 text-teal-600">{c.contacted_count}</td>
                                <td className="px-4 py-3 text-orange-600">{c.replied_count}</td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-1.5">
                                        <button onClick={() => router.post(`/prospecting/campaigns/${c.id}/hunt`)} className="px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs font-medium hover:bg-purple-200">Hunt</button>
                                        <button onClick={() => router.post(`/prospecting/campaigns/${c.id}/qualify`)} className="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium hover:bg-blue-200">Qualify</button>
                                        <button onClick={() => router.post(`/prospecting/campaigns/${c.id}/research`)} className="px-2 py-1 bg-indigo-100 text-indigo-700 rounded text-xs font-medium hover:bg-indigo-200">Research</button>
                                        <button onClick={() => router.post(`/prospecting/campaigns/${c.id}/outreach`)} className="px-2 py-1 bg-teal-100 text-teal-700 rounded text-xs font-medium hover:bg-teal-200">Outreach</button>
                                        <button onClick={() => router.post(`/prospecting/campaigns/${c.id}/followup`)} className="px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs font-medium hover:bg-orange-200">Follow up</button>
                                        <button onClick={() => router.post(`/prospecting/campaigns/${c.id}/toggle`)} className="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-medium hover:bg-yellow-200">{c.status === 'active' ? 'Pause' : 'Activate'}</button>
                                        <Link href={`/prospecting/prospects?campaign=${c.id}`} className="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium hover:bg-gray-200">Prospects</Link>
                                        <button onClick={() => startEdit(c)} className="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium hover:bg-gray-200">Edit</button>
                                        <button onClick={() => { if (confirm('Delete this campaign and all its prospects?')) router.delete(`/prospecting/campaigns/${c.id}`); }} className="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium hover:bg-red-200">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {campaigns.length === 0 && <tr><td colSpan={7} className="px-4 py-8 text-center text-gray-400">No campaigns yet. Create your first campaign to start hunting.</td></tr>}
                    </tbody>
                </table>
            </div>
        </TenantLayout>
    );
}
