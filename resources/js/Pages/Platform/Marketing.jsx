import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

const typeColors = {
    content: 'bg-emerald-100 text-emerald-700', seo: 'bg-lime-100 text-lime-700',
    ads: 'bg-pink-100 text-pink-700', partner: 'bg-purple-100 text-purple-700',
    outbound: 'bg-blue-100 text-blue-700', referral: 'bg-teal-100 text-teal-700',
    social: 'bg-amber-100 text-amber-700', email: 'bg-cyan-100 text-cyan-700',
    other: 'bg-gray-100 text-gray-600',
};

const fmt = (n) => Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 2 });
const money = (n) => `₦${fmt(n)}`;

export default function Marketing({ marketing, channelTypes, metricNames }) {
    const { channels, totals } = marketing;
    const [showForm, setShowForm] = useState(false);
    const [editingId, setEditingId] = useState(null);

    const empty = { name: '', type: 'content', goal: '', budget: 0, status: 'active', start_at: '', end_at: '', notes: '' };
    const { data, setData, post, put, processing, reset } = useForm(empty);

    const startCreate = () => { setEditingId(null); reset(); setData(empty); setShowForm(true); };
    const startEdit = (c) => {
        setEditingId(c.id);
        setData({
            name: c.name || '', type: c.type || 'content', goal: c.goal || '', budget: c.budget || 0,
            status: c.status || 'active', start_at: c.start_at ? c.start_at.slice(0, 10) : '',
            end_at: c.end_at ? c.end_at.slice(0, 10) : '', notes: c.notes || '',
        });
        setShowForm(true);
    };

    const submit = (e) => {
        e.preventDefault();
        if (editingId) put(`/platform/marketing/channels/${editingId}`, { onSuccess: () => setShowForm(false) });
        else post('/platform/marketing/channels', { onSuccess: () => setShowForm(false) });
    };

    const field = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none';
    const label = 'block text-xs font-medium text-gray-600 mb-1';

    return (
        <PlatformLayout title="Marketing">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">📣 Marketing</h1>
                <button onClick={startCreate} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Add Channel</button>
            </div>

            <div className="grid grid-cols-4 gap-3 mb-6">
                {[
                    { label: 'Total Spend', value: money(totals.spend), color: 'text-red-600' },
                    { label: 'Total Leads', value: fmt(totals.leads), color: 'text-blue-600' },
                    { label: 'Total Signups', value: fmt(totals.signups), color: 'text-green-600' },
                    { label: 'Attributed MRR', value: money(totals.mrr), color: 'text-teal-600' },
                ].map((c) => (
                    <div key={c.label} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <p className={`text-xl font-bold ${c.color}`}>{c.value}</p>
                        <p className="text-xs text-gray-500">{c.label}</p>
                    </div>
                ))}
            </div>

            {showForm && (
                <form onSubmit={submit} className="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6 grid grid-cols-2 gap-4">
                    <div>
                        <label className={label}>Name</label>
                        <input className={field} value={data.name} onChange={(e) => setData('name', e.target.value)} />
                    </div>
                    <div>
                        <label className={label}>Type</label>
                        <select className={field} value={data.type} onChange={(e) => setData('type', e.target.value)}>
                            {channelTypes.map((t) => <option key={t} value={t}>{t}</option>)}
                        </select>
                    </div>
                    <div className="col-span-2">
                        <label className={label}>Goal</label>
                        <input className={field} value={data.goal} onChange={(e) => setData('goal', e.target.value)} />
                    </div>
                    <div>
                        <label className={label}>Budget (₦)</label>
                        <input type="number" step="0.01" min="0" className={field} value={data.budget} onChange={(e) => setData('budget', e.target.value)} />
                    </div>
                    <div>
                        <label className={label}>Status</label>
                        <select className={field} value={data.status} onChange={(e) => setData('status', e.target.value)}>
                            <option value="active">active</option>
                            <option value="paused">paused</option>
                            <option value="completed">completed</option>
                        </select>
                    </div>
                    <div>
                        <label className={label}>Start</label>
                        <input type="date" className={field} value={data.start_at} onChange={(e) => setData('start_at', e.target.value)} />
                    </div>
                    <div>
                        <label className={label}>End</label>
                        <input type="date" className={field} value={data.end_at} onChange={(e) => setData('end_at', e.target.value)} />
                    </div>
                    <div className="col-span-2">
                        <label className={label}>Notes</label>
                        <textarea className={field} rows={2} value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                    </div>
                    <div className="col-span-2 flex gap-2 justify-end">
                        <button type="button" onClick={() => setShowForm(false)} className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium">Cancel</button>
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium disabled:opacity-50">{editingId ? 'Save' : 'Add Channel'}</button>
                    </div>
                </form>
            )}

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50 text-left">
                        <tr>
                            <th className="px-4 py-3 font-medium text-gray-500">Channel</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Type</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Budget</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Spend</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Leads</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Signups</th>
                            <th className="px-4 py-3 font-medium text-gray-500">MRR</th>
                            <th className="px-4 py-3 font-medium text-gray-500">CAC</th>
                            <th className="px-4 py-3 font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {channels.map((c) => (
                            <tr key={c.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3">
                                    <p className="font-medium text-gray-900">{c.name}</p>
                                    <p className="text-xs text-gray-400">{c.goal}</p>
                                </td>
                                <td className="px-4 py-3"><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${typeColors[c.type] || typeColors.other}`}>{c.type}</span></td>
                                <td className="px-4 py-3 text-gray-600">{money(c.budget)}</td>
                                <td className="px-4 py-3 text-red-600">{money(c.spend)}</td>
                                <td className="px-4 py-3 text-blue-600">{fmt(c.leads)}</td>
                                <td className="px-4 py-3 text-green-600">{fmt(c.signups)}</td>
                                <td className="px-4 py-3 text-teal-600">{money(c.mrr)}</td>
                                <td className="px-4 py-3 text-gray-500">{c.cac != null ? money(c.cac) : '—'}</td>
                                <td className="px-4 py-3">
                                    <div className="flex gap-1.5">
                                        <MetricForm channel={c} metricNames={metricNames} />
                                        <button onClick={() => startEdit(c)} className="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium hover:bg-gray-200">Edit</button>
                                        <button onClick={() => { if (confirm('Delete this channel?')) router.delete(`/platform/marketing/channels/${c.id}`); }} className="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium hover:bg-red-200">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {channels.length === 0 && <tr><td colSpan={9} className="px-4 py-8 text-center text-gray-400">No channels yet. Add your first marketing channel.</td></tr>}
                    </tbody>
                </table>
            </div>
        </PlatformLayout>
    );
}

function MetricForm({ channel, metricNames }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing } = useForm({ metric: 'spend', value: 0, recorded_on: new Date().toISOString().slice(0, 10) });

    const submit = (e) => {
        e.preventDefault();
        post(`/platform/marketing/channels/${channel.id}/metrics`, { onSuccess: () => setOpen(false) });
    };

    if (!open) return <button onClick={() => setOpen(true)} className="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium hover:bg-blue-200">+ Metric</button>;

    return (
        <form onSubmit={submit} className="inline-flex items-center gap-1">
            <select className="border border-gray-300 rounded px-1 py-0.5 text-xs" value={data.metric} onChange={(e) => setData('metric', e.target.value)}>
                {metricNames.map((m) => <option key={m} value={m}>{m}</option>)}
            </select>
            <input type="number" step="0.01" className="border border-gray-300 rounded px-1 py-0.5 text-xs w-20" value={data.value} onChange={(e) => setData('value', e.target.value)} />
            <input type="date" className="border border-gray-300 rounded px-1 py-0.5 text-xs" value={data.recorded_on} onChange={(e) => setData('recorded_on', e.target.value)} />
            <button type="submit" disabled={processing} className="px-2 py-0.5 bg-green-600 text-white rounded text-xs font-medium">Save</button>
        </form>
    );
}
