import { useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

const statusColors = {
    draft: 'bg-gray-100 text-gray-600',
    active: 'bg-green-100 text-green-700',
    paused: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-blue-100 text-blue-700',
};

export default function ProspectingCampaigns({ campaigns, defaultSender }) {
    const [editingId, setEditingId] = useState(null);
    const [showForm, setShowForm] = useState(false);

    const emptyForm = {
        name: '', description: '', offer: '', tone: 'professional',
        sender_name: defaultSender.name || '', sender_email: defaultSender.email || '',
        daily_limit: 25, auto_outreach: false,
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
        if (editingId) put(`/platform/prospecting/campaigns/${editingId}`, { onSuccess: () => setShowForm(false) });
        else post('/platform/prospecting/campaigns', { onSuccess: () => setShowForm(false) });
    };

    const field = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none';
    const label = 'block text-xs font-medium text-gray-600 mb-1';

    return (
        <PlatformLayout title="Prospecting Campaigns">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">🎯 Prospecting Campaigns</h1>
                <button onClick={startCreate} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">+ New Campaign</button>
            </div>

            {showForm && (
                <form onSubmit={submit} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 className="font-semibold text-gray-900 mb-4">{editingId ? 'Edit Campaign' : 'New Campaign'}</h3>
                    <div className="grid grid-cols-2 gap-3 mb-3">
                        <div><label className={label}>Name *</label><input className={field} value={data.name} onChange={(e) => setData('name', e.target.value)} required /></div>
                        <div><label className={label}>Tone</label>
                            <select className={field} value={data.tone} onChange={(e) => setData('tone', e.target.value)}>
                                <option value="professional">Professional</option><option value="friendly">Friendly</option><option value="persuasive">Persuasive</option><option value="concise">Concise</option>
                            </select>
                        </div>
                        <div className="col-span-2"><label className={label}>Description</label><input className={field} value={data.description} onChange={(e) => setData('description', e.target.value)} /></div>
                        <div className="col-span-2"><label className={label}>Offer / Value Proposition (what you pitch)</label><textarea className={field} rows={3} value={data.offer} onChange={(e) => setData('offer', e.target.value)} /></div>
                        <div><label className={label}>Sender Name</label><input className={field} value={data.sender_name} onChange={(e) => setData('sender_name', e.target.value)} /></div>
                        <div><label className={label}>Sender Email</label><input className={field} value={data.sender_email} onChange={(e) => setData('sender_email', e.target.value)} /></div>
                        <div><label className={label}>Daily Hunt Limit</label><input type="number" min="1" max="500" className={field} value={data.daily_limit} onChange={(e) => setData('daily_limit', e.target.value)} /></div>
                        <div className="flex items-end pb-1"><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.auto_outreach} onChange={(e) => setData('auto_outreach', e.target.checked)} /> Auto-outreach after qualify</label></div>
                    </div>
                    <div className="grid grid-cols-2 gap-3 pt-4 border-t border-gray-100">
                        <div><label className={label}>Industries (comma separated)</label><input className={field} placeholder="SaaS, Fintech" value={data.icp_industry} onChange={(e) => setData('icp_industry', e.target.value)} /></div>
                        <div><label className={label}>Company Size</label>
                            <select className={field} value={data.icp_company_size} onChange={(e) => setData('icp_company_size', e.target.value)}>
                                <option value="">Any</option><option value="1-10">1-10</option><option value="11-50">11-50</option><option value="51-200">51-200</option><option value="201-1000">201-1000</option><option value="1000+">1000+</option>
                            </select>
                        </div>
                        <div><label className={label}>Geographies</label><input className={field} placeholder="USA, UK, Remote" value={data.icp_geography} onChange={(e) => setData('icp_geography', e.target.value)} /></div>
                        <div><label className={label}>Job Titles</label><input className={field} placeholder="CEO, Founder, VP Sales" value={data.icp_job_titles} onChange={(e) => setData('icp_job_titles', e.target.value)} /></div>
                        <div><label className={label}>Keywords</label><input className={field} placeholder="hiring, CRM, growth" value={data.icp_keywords} onChange={(e) => setData('icp_keywords', e.target.value)} /></div>
                        <div><label className={label}>Exclusions</label><input className={field} placeholder="competitors, agencies" value={data.icp_exclusions} onChange={(e) => setData('icp_exclusions', e.target.value)} /></div>
                        <div><label className={label}>Budget Range</label><input className={field} placeholder="$1k-$5k/mo" value={data.icp_budget} onChange={(e) => setData('icp_budget', e.target.value)} /></div>
                        <div><label className={label}>Pain Points</label><input className={field} placeholder="slow support, churn" value={data.icp_pain_points} onChange={(e) => setData('icp_pain_points', e.target.value)} /></div>
                    </div>
                    <div className="grid grid-cols-2 gap-3 pt-4 border-t border-gray-100">
                        <div className="col-span-2"><label className={label}>Physical Postal Address (CAN-SPAM)</label><input className={field} value={data.postal_address} onChange={(e) => setData('postal_address', e.target.value)} /></div>
                        <div><label className={label}>Sending Domain</label><input className={field} placeholder="outreach.yourdomain.com" value={data.from_domain} onChange={(e) => setData('from_domain', e.target.value)} /></div>
                        <div><label className={label}>Compliance Regions</label><input className={field} placeholder="us, uk" value={data.compliance_regions} onChange={(e) => setData('compliance_regions', e.target.value)} /></div>
                        <div><label className={label}>Blocked Sources</label><input className={field} placeholder="csv, manual" value={data.blocked_sources} onChange={(e) => setData('blocked_sources', e.target.value)} /></div>
                        <div><label className={label}>Blocked Regions</label><input className={field} placeholder="e.g. Quebec" value={data.blocked_regions} onChange={(e) => setData('blocked_regions', e.target.value)} /></div>
                        <div><label className={label}>Max Emails / Hour</label><input type="number" min="1" className={field} value={data.max_per_hour} onChange={(e) => setData('max_per_hour', e.target.value)} /></div>
                        <label className="flex items-center gap-2 text-sm col-span-2 pt-1"><input type="checkbox" checked={data.allow_ai_generated} onChange={(e) => setData('allow_ai_generated', e.target.checked)} /> Allow AI-generated prospects</label>
                        <label className="flex items-center gap-2 text-sm col-span-2"><input type="checkbox" checked={data.require_approval_ai_contacts} onChange={(e) => setData('require_approval_ai_contacts', e.target.checked)} /> Require human approval for AI-generated contacts</label>
                    </div>
                    <div className="flex gap-2 mt-4">
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">{editingId ? 'Save Changes' : 'Create Campaign'}</button>
                        <button type="button" onClick={() => setShowForm(false)} className="px-4 py-2 border border-gray-200 rounded-lg text-sm">Cancel</button>
                    </div>
                </form>
            )}

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Campaign</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Prospects</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Qualified</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Contacted</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Replies</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Actions</th>
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
                                        <button onClick={() => router.post(`/platform/prospecting/campaigns/${c.id}/hunt`)} className="px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs font-medium hover:bg-purple-200">Hunt</button>
                                        <button onClick={() => router.post(`/platform/prospecting/campaigns/${c.id}/qualify`)} className="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium hover:bg-blue-200">Qualify</button>
                                        <button onClick={() => router.post(`/platform/prospecting/campaigns/${c.id}/outreach`)} className="px-2 py-1 bg-teal-100 text-teal-700 rounded text-xs font-medium hover:bg-teal-200">Outreach</button>
                                        <button onClick={() => router.post(`/platform/prospecting/campaigns/${c.id}/toggle`)} className="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-medium hover:bg-yellow-200">{c.status === 'active' ? 'Pause' : 'Activate'}</button>
                                        <Link href={`/platform/prospecting/prospects?campaign=${c.id}`} className="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium hover:bg-gray-200">Prospects</Link>
                                        <button onClick={() => startEdit(c)} className="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium hover:bg-gray-200">Edit</button>
                                        <button onClick={() => { if (confirm('Delete this campaign and all its prospects?')) router.delete(`/platform/prospecting/campaigns/${c.id}`); }} className="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium hover:bg-red-200">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {campaigns.length === 0 && <tr><td colSpan={7} className="px-4 py-8 text-center text-gray-400">No campaigns yet. Create your first campaign to start hunting.</td></tr>}
                    </tbody>
                </table>
            </div>
        </PlatformLayout>
    );
}
