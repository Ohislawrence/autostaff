import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

const fmt = (n) => Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 2 });
const fmtValue = (v, unit) => unit === 'currency' ? `₦${fmt(v)}` : unit === 'percent' ? `${fmt(v)}%` : fmt(v);

const statusColors = { 'on-track': 'bg-green-100 text-green-700', 'behind': 'bg-red-100 text-red-700' };
const barColors = { 'on-track': 'bg-green-500', 'behind': 'bg-red-400' };

export default function Goals({ goals, metricKeys }) {
    const [showForm, setShowForm] = useState(false);
    const [editingId, setEditingId] = useState(null);

    const empty = { title: '', metric_key: 'mrr', target: 0, unit: 'count', period: 'month', start_at: '', end_at: '', color: 'text-blue-600' };
    const { data, setData, post, put, processing, reset } = useForm(empty);

    const startCreate = () => { setEditingId(null); reset(); setData(empty); setShowForm(true); };
    const startEdit = (g) => {
        setEditingId(g.id);
        setData({
            title: g.title, metric_key: g.metric_key, target: g.target, unit: g.unit || 'count',
            period: g.period || 'month', start_at: g.start_at ? g.start_at.slice(0, 10) : '',
            end_at: g.end_at ? g.end_at.slice(0, 10) : '', color: g.color || 'text-blue-600',
        });
        setShowForm(true);
    };

    const submit = (e) => {
        e.preventDefault();
        if (editingId) put(`/platform/goals/${editingId}`, { onSuccess: () => setShowForm(false) });
        else post('/platform/goals', { onSuccess: () => setShowForm(false) });
    };

    const field = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none';
    const label = 'block text-xs font-medium text-gray-600 mb-1';

    return (
        <PlatformLayout title="Goals">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">🎯 Goals & Progress</h1>
                <button onClick={startCreate} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Add Goal</button>
            </div>

            {showForm && (
                <form onSubmit={submit} className="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6 grid grid-cols-2 gap-4">
                    <div className="col-span-2">
                        <label className={label}>Title</label>
                        <input className={field} value={data.title} onChange={(e) => setData('title', e.target.value)} />
                    </div>
                    <div>
                        <label className={label}>Metric</label>
                        <select className={field} value={data.metric_key} onChange={(e) => setData('metric_key', e.target.value)}>
                            {Object.entries(metricKeys).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className={label}>Target</label>
                        <input type="number" step="0.01" min="0" className={field} value={data.target} onChange={(e) => setData('target', e.target.value)} />
                    </div>
                    <div>
                        <label className={label}>Unit</label>
                        <select className={field} value={data.unit} onChange={(e) => setData('unit', e.target.value)}>
                            <option value="count">count</option>
                            <option value="currency">currency</option>
                            <option value="percent">percent</option>
                        </select>
                    </div>
                    <div>
                        <label className={label}>Period</label>
                        <select className={field} value={data.period} onChange={(e) => setData('period', e.target.value)}>
                            {['month', 'quarter', 'year', 'custom'].map((p) => <option key={p} value={p}>{p}</option>)}
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
                    <div className="col-span-2 flex gap-2 justify-end">
                        <button type="button" onClick={() => setShowForm(false)} className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium">Cancel</button>
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium disabled:opacity-50">{editingId ? 'Save' : 'Add Goal'}</button>
                    </div>
                </form>
            )}

            <div className="grid grid-cols-2 gap-4">
                {goals.map((g) => (
                    <div key={g.id} className="bg-white rounded-xl border border-gray-200 p-5">
                        <div className="flex items-start justify-between">
                            <div>
                                <h3 className="font-semibold text-gray-900">{g.title}</h3>
                                <p className="text-xs text-gray-400 mt-0.5">{g.metric_label}</p>
                            </div>
                            <div className="flex items-center gap-1.5">
                                <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[g.progress_status] || 'bg-gray-100 text-gray-600'}`}>{g.progress_status}</span>
                                <button onClick={() => startEdit(g)} className="text-xs text-blue-500 hover:text-blue-700">Edit</button>
                                <button onClick={() => { if (confirm('Delete this goal?')) router.delete(`/platform/goals/${g.id}`); }} className="text-xs text-red-400 hover:text-red-600">Delete</button>
                            </div>
                        </div>
                        <div className="mt-3">
                            <p className={`text-xl font-bold ${g.color}`}>
                                {fmtValue(g.current, g.unit)} <span className="text-sm font-medium text-gray-400">/ {fmtValue(g.target, g.unit)}</span>
                            </p>
                            <div className="h-2.5 bg-gray-100 rounded-full overflow-hidden mt-2">
                                <div className={`h-full rounded-full ${barColors[g.progress_status] || 'bg-blue-500'}`} style={{ width: `${g.percent}%` }} />
                            </div>
                            <p className="text-xs text-gray-400 mt-1 text-right">{g.percent}%</p>
                        </div>
                    </div>
                ))}
                {goals.length === 0 && <p className="col-span-2 text-center text-gray-400 py-10">No goals yet. Add your first growth goal.</p>}
            </div>
        </PlatformLayout>
    );
}
