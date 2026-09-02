import { Link, usePage, useForm } from '@inertiajs/react';
import { useState } from 'react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function Tenants({ tenants, plans, filters }) {
    const { flash } = usePage().props;
    const { post } = useForm();
    const [showCreate, setShowCreate] = useState(false);
    const createForm = useForm({
        name: '', slug: '', email: '', phone: '', industry: '', country: '', plan_id: '',
    });

    const handleCreate = (e) => {
        e.preventDefault();
        createForm.post('/platform/tenants', { onSuccess: () => { setShowCreate(false); createForm.reset(); } });
    };

    return (
        <PlatformLayout title="Organizations">
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">Organizations ({tenants.total})</h1>
                <div className="flex gap-2">
                    <form className="flex gap-2"><input type="text" name="search" defaultValue={filters.search} placeholder="Search..." className="px-3 py-2 border border-gray-300 rounded-lg text-sm" /><button type="submit" className="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm">Search</button></form>
                    <button onClick={() => setShowCreate(!showCreate)} className="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium">+ Create</button>
                </div>
            </div>

            {showCreate && (
                <div className="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 className="font-semibold text-gray-900 mb-4">Create Organization</h2>
                    <form onSubmit={handleCreate} className="grid grid-cols-3 gap-4">
                        <div><label className="block text-xs font-medium text-gray-600 mb-1">Name *</label><input type="text" required value={createForm.data.name} onChange={e => createForm.setData('name', e.target.value)} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Business name" /></div>
                        <div><label className="block text-xs font-medium text-gray-600 mb-1">Slug *</label><input type="text" required value={createForm.data.slug} onChange={e => createForm.setData('slug', e.target.value)} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="url-slug" /></div>
                        <div><label className="block text-xs font-medium text-gray-600 mb-1">Email</label><input type="email" value={createForm.data.email} onChange={e => createForm.setData('email', e.target.value)} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="contact@..." /></div>
                        <div><label className="block text-xs font-medium text-gray-600 mb-1">Phone</label><input type="text" value={createForm.data.phone} onChange={e => createForm.setData('phone', e.target.value)} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" /></div>
                        <div><label className="block text-xs font-medium text-gray-600 mb-1">Industry</label><input type="text" value={createForm.data.industry} onChange={e => createForm.setData('industry', e.target.value)} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" /></div>
                        <div><label className="block text-xs font-medium text-gray-600 mb-1">Country</label><input type="text" value={createForm.data.country} onChange={e => createForm.setData('country', e.target.value)} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="e.g. Nigeria" /></div>
                        <div><label className="block text-xs font-medium text-gray-600 mb-1">Plan</label><select value={createForm.data.plan_id} onChange={e => createForm.setData('plan_id', e.target.value)} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="">No plan</option>{plans?.map(p => <option key={p.id} value={p.id}>{p.name} (₦${p.price}/mo)</option>)}</select></div>
                        <div className="flex items-end gap-2">
                            <button type="submit" disabled={createForm.processing} className="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium">{createForm.processing ? 'Creating...' : 'Create'}</button>
                            <button type="button" onClick={() => setShowCreate(false)} className="px-4 py-2 border border-gray-300 rounded-lg text-sm">Cancel</button>
                        </div>
                    </form>
                </div>
            )}
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"><table className="w-full text-sm"><thead className="bg-gray-50"><tr><th className="text-left px-4 py-3 font-medium text-gray-500">Organization</th><th className="text-left px-4 py-3 font-medium text-gray-500">Plan</th><th className="text-left px-4 py-3 font-medium text-gray-500">Users</th><th className="text-left px-4 py-3 font-medium text-gray-500">AI</th><th className="text-left px-4 py-3 font-medium text-gray-500">Convs</th><th className="text-left px-4 py-3 font-medium text-gray-500">Leads</th><th className="text-left px-4 py-3 font-medium text-gray-500">Status</th><th className="text-left px-4 py-3 font-medium text-gray-500">Actions</th></tr></thead><tbody className="divide-y divide-gray-100">{tenants.data?.map(t => {const p = t.subscriptions?.[0]?.plan;return(<tr key={t.id} className="hover:bg-gray-50"><td className="px-4 py-3"><Link href={`/platform/tenants/${t.id}`} className="font-medium text-gray-900 hover:text-purple-600">{t.name}</Link></td><td className="px-4 py-3 text-xs"><span className="px-2 py-0.5 bg-gray-100 rounded-full">{p?.name||'—'}</span></td><td className="px-4 py-3 text-gray-500">{t.users_count||0}</td><td className="px-4 py-3 text-gray-500">{t.ai_employees_count||0}</td><td className="px-4 py-3 text-gray-500">{t.conversations_count||0}</td><td className="px-4 py-3 text-gray-500">{t.leads_count||0}</td><td className="px-4 py-3"><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${t.is_active?'bg-green-100 text-green-700':'bg-red-100 text-red-700'}`}>{t.is_active?'Active':'Suspended'}</span></td><td className="px-4 py-3"><button onClick={()=>post(`/platform/tenants/${t.id}/toggle`)} className={`text-xs px-2 py-1 rounded ${t.is_active?'text-red-600 bg-red-50':'text-green-600 bg-green-50'}`}>{t.is_active?'Suspend':'Activate'}</button></td></tr>)})}{tenants.data?.length===0&&<tr><td colSpan={8} className="px-4 py-8 text-center text-gray-400">No organizations.</td></tr>}</tbody></table></div>
        </PlatformLayout>
    );
}