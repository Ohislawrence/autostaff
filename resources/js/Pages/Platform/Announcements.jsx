import { usePage, useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function Announcements({ announcements }) {
    const { flash } = usePage().props;
    const { data, setData, post, processing } = useForm({ title: '', body: '', type: 'info', send_email: false, send_in_app: true });
    const typeColors = { info: 'bg-blue-100 text-blue-700', warning: 'bg-yellow-100 text-yellow-700', maintenance: 'bg-orange-100 text-orange-700', success: 'bg-green-100 text-green-700' };

    return (
        <PlatformLayout title="Announcements">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">Platform Announcements</h1>
            <form onSubmit={(e)=>{e.preventDefault();post('/platform/announcements')}} className="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-4 space-y-3">
                <input value={data.title} onChange={e=>setData('title',e.target.value)} className="w-full px-3 py-2 border border-gray-300 rounded text-sm" placeholder="Announcement title" required />
                <textarea value={data.body} onChange={e=>setData('body',e.target.value)} className="w-full px-3 py-2 border border-gray-300 rounded text-sm" placeholder="Announcement body..." rows={3} required />
                <div className="flex items-center gap-4">
                    <select value={data.type} onChange={e=>setData('type',e.target.value)} className="px-3 py-2 border border-gray-300 rounded text-sm"><option value="info">Info</option><option value="warning">Warning</option><option value="maintenance">Maintenance</option><option value="success">Success</option></select>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.send_in_app} onChange={e=>setData('send_in_app',e.target.checked)}/> In-app</label>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.send_email} onChange={e=>setData('send_email',e.target.checked)}/> Email</label>
                    <button type="submit" disabled={processing} className="px-4 py-2 bg-purple-600 text-white rounded text-sm">Publish</button>
                </div>
            </form>
            <div className="space-y-3">{announcements.map(a=>(<div key={a.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-5"><div className="flex items-center justify-between mb-2"><h3 className="font-semibold text-gray-900">{a.title}</h3><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${typeColors[a.type]||'bg-gray-100 text-gray-600'}`}>{a.type}</span></div><p className="text-sm text-gray-600">{a.body}</p><p className="text-xs text-gray-400 mt-2">{new Date(a.published_at||a.created_at).toLocaleString()}</p></div>))}{announcements.length===0&&<p className="text-sm text-gray-400 text-center py-8">No announcements yet.</p>}</div>
        </PlatformLayout>
    );
}