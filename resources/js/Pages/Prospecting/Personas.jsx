import { useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

const LIST_FIELDS = [
    { key: 'role_titles', label: 'Role / job titles' },
    { key: 'demographics', label: 'Demographics (seniority, company size)' },
    { key: 'goals', label: 'Goals' },
    { key: 'pains', label: 'Pain points' },
    { key: 'objections', label: 'Objections' },
    { key: 'buying_triggers', label: 'Buying triggers' },
    { key: 'messaging_hooks', label: 'Messaging hooks' },
    { key: 'value_props', label: 'Value props to lead with' },
    { key: 'channels', label: 'Where they hang out' },
    { key: 'keywords', label: 'Search keywords' },
];

export default function Personas({ personas, templates }) {
    const { flash } = usePage().props;

    const emptyForm = {
        name: '', avatar: '🧑‍💼',
        role_titles: '', demographics: '', goals: '', pains: '', objections: '',
        buying_triggers: '', messaging_hooks: '', value_props: '', channels: '',
        current_solution: '', keywords: '',
    };

    const [editingId, setEditingId] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, put, processing, reset } = useForm(emptyForm);
    const generate = useForm({ offer: '', industry: '' });

    const toForm = (p) => ({
        name: p.name || '', avatar: p.avatar || '🧑‍💼',
        role_titles: (p.role_titles || []).join(', '),
        demographics: (p.demographics || []).join(', '),
        goals: (p.goals || []).join(', '),
        pains: (p.pains || []).join(', '),
        objections: (p.objections || []).join(', '),
        buying_triggers: (p.buying_triggers || []).join(', '),
        messaging_hooks: (p.messaging_hooks || []).join(', '),
        value_props: (p.value_props || []).join(', '),
        channels: (p.channels || []).join(', '),
        current_solution: p.current_solution || '',
        keywords: (p.keywords || []).join(', '),
    });

    const startCreate = () => { setEditingId(null); reset(); setData(emptyForm); setShowForm(true); };
    const startEdit = (p) => { setEditingId(p.id); setData(toForm(p)); setShowForm(true); };

    const submit = (e) => {
        e.preventDefault();
        if (editingId) put(`/prospecting/personas/${editingId}`, { onSuccess: () => setShowForm(false) });
        else post('/prospecting/personas', { onSuccess: () => setShowForm(false) });
    };

    const field = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none';
    const label = 'block text-xs font-medium text-gray-600 mb-1';
    const area = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none resize-none';

    return (
        <TenantLayout header="Buyer Personas">
            <Head title="Buyer Personas" />

            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-xl text-sm">{flash.success}</div>}
            {flash?.error && <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-xl text-sm">{flash.error}</div>}

            <div className="flex items-center justify-between mb-2">
                <h1 className="text-2xl font-bold text-gray-900">🧑‍💼 Buyer Personas</h1>
                <Link href="/prospecting" className="text-sm text-blue-600 hover:underline">← Back to prospecting</Link>
            </div>
            <p className="text-sm text-gray-500 mb-6">
                A persona is the human decision-maker you're targeting. Personas sharpen hunt queries, qualification scoring, and outreach copy.
            </p>

            <div className="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
                <h3 className="font-semibold text-blue-900 mb-1">✨ Generate a persona with AI</h3>
                <p className="text-sm text-blue-700 mb-3">Describe what you sell and Nomdal will draft a complete buyer persona for you.</p>
                <form onSubmit={(e) => { e.preventDefault(); generate.post('/prospecting/personas/generate', { onSuccess: () => generate.reset() }); }} className="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <input className={field} placeholder="What are you selling? (required)" value={generate.data.offer} onChange={(e) => generate.setData('offer', e.target.value)} />
                    <input className={field} placeholder="Industry / market (optional)" value={generate.data.industry} onChange={(e) => generate.setData('industry', e.target.value)} />
                    <button type="submit" disabled={generate.processing || !generate.data.offer.trim()} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50">{generate.processing ? 'Generating…' : 'Generate persona'}</button>
                </form>
            </div>

            {(templates || []).length > 0 && (
                <div className="mb-8">
                    <h2 className="font-semibold text-gray-900 mb-3">📚 Template library</h2>
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-3">
                        {templates.map((t) => (
                            <div key={t.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-start justify-between gap-3">
                                <div className="flex items-center gap-3 min-w-0">
                                    <span className="text-2xl">{t.avatar || '🧑‍💼'}</span>
                                    <div className="min-w-0">
                                        <p className="font-semibold text-gray-900 text-sm">{t.name}</p>
                                        <p className="text-xs text-gray-500 mt-0.5 truncate">{(t.pains || []).slice(0, 2).join('; ')}</p>
                                    </div>
                                </div>
                                <button onClick={() => router.post('/prospecting/personas/use-template', { persona_id: t.id })} className="shrink-0 px-2.5 py-1.5 bg-blue-600 text-white rounded text-xs font-medium hover:bg-blue-700">Use</button>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
                {personas.map((p) => (
                    <div key={p.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <div className="flex items-start justify-between">
                            <div className="flex items-center gap-3">
                                <span className="text-2xl">{p.avatar || '🧑‍💼'}</span>
                                <div>
                                    <p className="font-semibold text-gray-900">{p.name}</p>
                                    <p className="text-xs text-gray-400">{(p.role_titles || []).join(', ') || 'No roles set'} · {p.campaigns_count || 0} campaign{(p.campaigns_count || 0) === 1 ? '' : 's'}</p>
                                </div>
                            </div>
                            <div className="flex gap-1.5">
                                <button onClick={() => startEdit(p)} className="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium hover:bg-gray-200">Edit</button>
                                <button onClick={() => { if (confirm(`Delete persona "${p.name}"?`)) router.delete(`/prospecting/personas/${p.id}`); }} className="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium hover:bg-red-200">Delete</button>
                            </div>
                        </div>
                        {(p.pains || []).length > 0 && (
                            <p className="text-sm text-gray-600 mt-3"><span className="font-medium text-gray-700">Pains:</span> {(p.pains || []).join('; ')}</p>
                        )}
                        {(p.goals || []).length > 0 && (
                            <p className="text-sm text-gray-600 mt-1"><span className="font-medium text-gray-700">Goals:</span> {(p.goals || []).join('; ')}</p>
                        )}
                    </div>
                ))}
                {personas.length === 0 && (
                    <div className="col-span-2 text-center py-10 text-gray-400 text-sm">
                        No personas yet. Generate one with AI or create it manually below.
                    </div>
                )}
            </div>

            <div className="max-w-3xl">
                <div className="flex items-center justify-between mb-3">
                    <h2 className="font-semibold text-gray-900">{editingId ? 'Edit persona' : 'Create a persona'}</h2>
                    {showForm && <button onClick={() => setShowForm(false)} className="text-sm text-gray-500 hover:underline">Cancel</button>}
                </div>

                {!showForm && (
                    <button onClick={startCreate} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">+ New persona</button>
                )}

                {showForm && (
                    <form onSubmit={submit} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label className={label}>Name *</label><input className={field} placeholder="e.g. Dental Clinic Owner" value={data.name} onChange={(e) => setData('name', e.target.value)} /></div>
                            <div><label className={label}>Avatar emoji</label><input className={field} value={data.avatar} onChange={(e) => setData('avatar', e.target.value)} /></div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {LIST_FIELDS.map((f) => (
                                <div key={f.key}>
                                    <label className={label}>{f.label}</label>
                                    <textarea className={area} rows={2} placeholder="Separate with commas" value={data[f.key]} onChange={(e) => setData(f.key, e.target.value)} />
                                </div>
                            ))}
                        </div>

                        <div>
                            <label className={label}>Current solution / status quo</label>
                            <textarea className={area} rows={2} placeholder="What they use today (competitor, spreadsheet, manual process)" value={data.current_solution} onChange={(e) => setData('current_solution', e.target.value)} />
                        </div>

                        <div className="flex items-center gap-3 pt-2">
                            <button type="submit" disabled={processing || !data.name.trim()} className="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50">
                                {editingId ? 'Save changes' : 'Create persona'}
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </TenantLayout>
    );
}

