import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import PlatformLayout from '@/Layouts/PlatformLayout';

const TARGET_PLATFORMS = ['wordpress', 'shopify', 'generic'];

const blank = {
    name: '',
    slug: '',
    short_description: '',
    description: '',
    icon: '',
    author: '',
    homepage_url: '',
    category: '',
    target_platform: 'wordpress',
    min_api_version: '',
    max_api_version: '',
    is_featured: false,
    sort_order: 0,
    version: '',
    changelog: '',
    package: null,
};

export default function Plugins({ plugins = [] }) {
    const [creating, setCreating] = useState(false);
    const [editing, setEditing] = useState(null);
    const [uploading, setUploading] = useState(null);

    const createForm = useForm(blank);
    const editForm = useForm(blank);
    const versionForm = useForm({ version: '', changelog: '', package: null });

    const startEdit = (p) => {
        setEditing(p.id);
        setUploading(null);
        editForm.setData({
            name: p.name,
            slug: p.slug || '',
            short_description: p.short_description || '',
            description: p.description || '',
            icon: p.icon || '',
            author: p.author || '',
            homepage_url: p.homepage_url || '',
            category: p.category || '',
            target_platform: p.target_platform || 'wordpress',
            min_api_version: p.min_api_version || '',
            max_api_version: p.max_api_version || '',
            is_featured: !!p.is_featured,
            sort_order: p.sort_order || 0,
        });
    };

    const submitCreate = (e) => {
        e.preventDefault();
        createForm.post('/platform/plugins', {
            onSuccess: () => { createForm.reset(); setCreating(false); },
        });
    };

    const submitEdit = (e) => {
        e.preventDefault();
        editForm.put(`/platform/plugins/${editing}`, { onSuccess: () => setEditing(null) });
    };

    const submitVersion = (e) => {
        e.preventDefault();
        versionForm.post(`/platform/plugins/${uploading}/versions`, {
            onSuccess: () => { versionForm.reset(); setUploading(null); },
        });
    };

    return (
        <PlatformLayout title="Plugins">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">Plugin Catalog</h1>
                <button
                    onClick={() => { setCreating(!creating); setEditing(null); }}
                    className="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700"
                >
                    {creating ? 'Cancel' : '+ New Plugin'}
                </button>
            </div>
            {creating && (
                <form onSubmit={submitCreate} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <h2 className="font-semibold text-gray-900 mb-4">New Plugin</h2>
                    <div className="grid grid-cols-3 gap-3">
                        <input value={createForm.data.name} onChange={(e) => createForm.setData('name', e.target.value)} placeholder="Name" className="px-3 py-2 border rounded text-sm" required />
                        <input value={createForm.data.slug} onChange={(e) => createForm.setData('slug', e.target.value)} placeholder="Slug (auto if blank)" className="px-3 py-2 border rounded text-sm" />
                        <input value={createForm.data.category} onChange={(e) => createForm.setData('category', e.target.value)} placeholder="Category" className="px-3 py-2 border rounded text-sm" />
                        <input value={createForm.data.author} onChange={(e) => createForm.setData('author', e.target.value)} placeholder="Author" className="px-3 py-2 border rounded text-sm" />
                        <input value={createForm.data.icon} onChange={(e) => createForm.setData('icon', e.target.value)} placeholder="Icon (emoji)" className="px-3 py-2 border rounded text-sm" />
                        <select value={createForm.data.target_platform} onChange={(e) => createForm.setData('target_platform', e.target.value)} className="px-3 py-2 border rounded text-sm">
                            {TARGET_PLATFORMS.map((t) => <option key={t} value={t}>{t}</option>)}
                        </select>
                        <input value={createForm.data.homepage_url} onChange={(e) => createForm.setData('homepage_url', e.target.value)} placeholder="Homepage URL" className="px-3 py-2 border rounded text-sm" />
                        <input value={createForm.data.min_api_version} onChange={(e) => createForm.setData('min_api_version', e.target.value)} placeholder="Min API version" className="px-3 py-2 border rounded text-sm" />
                        <input value={createForm.data.max_api_version} onChange={(e) => createForm.setData('max_api_version', e.target.value)} placeholder="Max API version" className="px-3 py-2 border rounded text-sm" />
                    </div>
                    <input value={createForm.data.short_description} onChange={(e) => createForm.setData('short_description', e.target.value)} placeholder="Short description" className="mt-3 w-full px-3 py-2 border rounded text-sm" />
                    <textarea value={createForm.data.description} onChange={(e) => createForm.setData('description', e.target.value)} placeholder="Full description" rows={2} className="mt-3 w-full px-3 py-2 border rounded text-sm" />
                    <div className="mt-4 border-t pt-4">
                        <p className="text-xs font-semibold text-gray-500 mb-2">First version package (optional — you can also add versions later)</p>
                        <div className="grid grid-cols-3 gap-3">
                            <input value={createForm.data.version} onChange={(e) => createForm.setData('version', e.target.value)} placeholder="Version (e.g. 1.0.0)" className="px-3 py-2 border rounded text-sm" />
                            <input value={createForm.data.changelog} onChange={(e) => createForm.setData('changelog', e.target.value)} placeholder="Changelog" className="px-3 py-2 border rounded text-sm" />
                            <input type="file" accept=".zip" onChange={(e) => createForm.setData('package', e.target.files[0])} className="text-sm" />
                        </div>
                    </div>
                    <div className="flex items-center gap-6 mt-3">
                        <label className="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" checked={createForm.data.is_featured} onChange={(e) => createForm.setData('is_featured', e.target.checked)} className="rounded" /> Featured
                        </label>
                        <label className="flex items-center gap-2 text-sm text-gray-700">
                            Sort order
                            <input type="number" value={createForm.data.sort_order} onChange={(e) => createForm.setData('sort_order', e.target.value)} className="w-20 px-2 py-1 border rounded text-sm" />
                        </label>
                    </div>
                    <div className="flex gap-2 mt-4">
                        <button type="submit" disabled={createForm.processing} className="px-4 py-2 bg-purple-600 text-white rounded text-sm">Create</button>
                        <button type="button" onClick={() => setCreating(false)} className="px-4 py-2 border rounded text-sm">Cancel</button>
                    </div>
                </form>
            )}
            <div className="grid grid-cols-1 gap-4">
                {plugins.length === 0 ? (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center text-sm text-gray-500">
                        No plugins yet. Create your first plugin above.
                    </div>
                ) : plugins.map((p) => (
                    <div key={p.id} className={`bg-white rounded-xl shadow-sm border p-6 ${p.is_published ? 'border-gray-200' : 'border-amber-200'}`}>
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <div className="flex items-center gap-2 flex-wrap">
                                    <span className="text-2xl">{p.icon || '🧩'}</span>
                                    <h3 className="font-semibold text-gray-900">{p.name}</h3>
                                    <span className="text-xs text-gray-400 font-mono">{p.slug}</span>
                                    {p.is_featured && <span className="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">Featured</span>}
                                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${p.is_published ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'}`}>
                                        {p.is_published ? 'Published' : 'Draft'}
                                    </span>
                                </div>
                                {p.short_description && <p className="text-xs text-gray-500 mt-1">{p.short_description}</p>}
                                <div className="flex gap-3 text-xs text-gray-400 mt-2">
                                    <span>{p.target_platform}</span>
                                    {p.category && <span>{p.category}</span>}
                                    {p.author && <span>by {p.author}</span>}
                                    <span>{p.versions?.length || 0} version{(p.versions?.length || 0) === 1 ? '' : 's'}</span>
                                </div>
                            </div>
                            <div className="flex items-center gap-3 shrink-0 text-xs">
                                <button onClick={() => startEdit(p)} className="text-purple-600 hover:text-purple-700 font-medium">Edit</button>
                                <button onClick={() => router.post(`/platform/plugins/${p.id}/toggle`)} className={`font-medium ${p.is_published ? 'text-amber-600' : 'text-green-600'}`}>
                                    {p.is_published ? 'Unpublish' : 'Publish'}
                                </button>
                                <button onClick={() => setUploading(uploading === p.id ? null : p.id)} className="text-blue-600 font-medium">Upload version</button>
                                <button onClick={() => { if (confirm('Delete this plugin?')) router.delete(`/platform/plugins/${p.id}`); }} className="text-red-600 font-medium">Delete</button>
                            </div>
                        </div>
                        {editing === p.id && (
                            <form onSubmit={submitEdit} className="mt-4 grid grid-cols-3 gap-3 border-t pt-4">
                                <input value={editForm.data.name} onChange={(e) => editForm.setData('name', e.target.value)} placeholder="Name" className="px-3 py-2 border rounded text-sm" required />
                                <input value={editForm.data.slug} onChange={(e) => editForm.setData('slug', e.target.value)} placeholder="Slug" className="px-3 py-2 border rounded text-sm" />
                                <input value={editForm.data.category} onChange={(e) => editForm.setData('category', e.target.value)} placeholder="Category" className="px-3 py-2 border rounded text-sm" />
                                <input value={editForm.data.author} onChange={(e) => editForm.setData('author', e.target.value)} placeholder="Author" className="px-3 py-2 border rounded text-sm" />
                                <input value={editForm.data.icon} onChange={(e) => editForm.setData('icon', e.target.value)} placeholder="Icon" className="px-3 py-2 border rounded text-sm" />
                                <select value={editForm.data.target_platform} onChange={(e) => editForm.setData('target_platform', e.target.value)} className="px-3 py-2 border rounded text-sm">
                                    {TARGET_PLATFORMS.map((t) => <option key={t} value={t}>{t}</option>)}
                                </select>
                                <input value={editForm.data.homepage_url} onChange={(e) => editForm.setData('homepage_url', e.target.value)} placeholder="Homepage URL" className="px-3 py-2 border rounded text-sm" />
                                <input value={editForm.data.min_api_version} onChange={(e) => editForm.setData('min_api_version', e.target.value)} placeholder="Min API version" className="px-3 py-2 border rounded text-sm" />
                                <input value={editForm.data.max_api_version} onChange={(e) => editForm.setData('max_api_version', e.target.value)} placeholder="Max API version" className="px-3 py-2 border rounded text-sm" />
                                <input value={editForm.data.short_description} onChange={(e) => editForm.setData('short_description', e.target.value)} placeholder="Short description" className="col-span-3 px-3 py-2 border rounded text-sm" />
                                <textarea value={editForm.data.description} onChange={(e) => editForm.setData('description', e.target.value)} placeholder="Full description" rows={2} className="col-span-3 px-3 py-2 border rounded text-sm" />
                                <div className="col-span-3 flex items-center gap-6">
                                    <label className="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" checked={editForm.data.is_featured} onChange={(e) => editForm.setData('is_featured', e.target.checked)} className="rounded" /> Featured
                                    </label>
                                    <div className="flex gap-2">
                                        <button type="submit" disabled={editForm.processing} className="px-3 py-1.5 bg-purple-600 text-white rounded text-xs">Save</button>
                                        <button type="button" onClick={() => setEditing(null)} className="px-3 py-1.5 border rounded text-xs">Cancel</button>
                                    </div>
                                </div>
                            </form>
                        )}
                        {uploading === p.id && (
                            <form onSubmit={submitVersion} className="mt-4 grid grid-cols-3 gap-3 border-t pt-4">
                                <input value={versionForm.data.version} onChange={(e) => versionForm.setData('version', e.target.value)} placeholder="Version (e.g. 1.0.0)" className="px-3 py-2 border rounded text-sm" required />
                                <input value={versionForm.data.changelog} onChange={(e) => versionForm.setData('changelog', e.target.value)} placeholder="Changelog" className="px-3 py-2 border rounded text-sm" />
                                <input type="file" accept=".zip" onChange={(e) => versionForm.setData('package', e.target.files[0])} className="text-sm" required />
                                <div className="col-span-3 flex gap-2">
                                    <button type="submit" disabled={versionForm.processing} className="px-3 py-1.5 bg-blue-600 text-white rounded text-xs">Upload</button>
                                    <button type="button" onClick={() => setUploading(null)} className="px-3 py-1.5 border rounded text-xs">Cancel</button>
                                </div>
                            </form>
                        )}

                        {p.versions?.length > 0 && (
                            <div className="mt-4 border-t pt-3">
                                <p className="text-xs font-semibold text-gray-500 mb-2">Versions</p>
                                <div className="space-y-2">
                                    {p.versions.map((v) => (
                                        <div key={v.id} className="flex items-center justify-between text-sm">
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <span className="font-mono text-xs text-gray-700">{v.version}</span>
                                                {v.is_current && <span className="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded text-[10px] font-medium">current</span>}
                                                <span className={`px-1.5 py-0.5 rounded text-[10px] font-medium ${v.status === 'published' ? 'bg-green-100 text-green-700' : v.status === 'deprecated' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'}`}>{v.status}</span>
                                                <span className="text-xs text-gray-400">{(v.file_size / 1024).toFixed(1)} KB · {v.sha256_checksum?.slice(0, 10)}…</span>
                                            </div>
                                            <div className="flex gap-2">
                                                {v.status !== 'published' && <button onClick={() => router.post(`/platform/plugin-versions/${v.id}/publish`)} className="text-xs text-green-600 font-medium">Publish</button>}
                                                {v.status === 'published' && <button onClick={() => router.post(`/platform/plugin-versions/${v.id}/deprecate`)} className="text-xs text-amber-600 font-medium">Deprecate</button>}
                                                <button onClick={() => { if (confirm('Delete this version?')) router.delete(`/platform/plugin-versions/${v.id}`); }} className="text-xs text-red-600 font-medium">Delete</button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </PlatformLayout>
    );
}
