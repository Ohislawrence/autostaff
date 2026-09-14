import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

const priorityColors = {
    low: 'bg-gray-100 text-gray-600', normal: 'bg-blue-100 text-blue-700',
    high: 'bg-amber-100 text-amber-700', urgent: 'bg-red-100 text-red-700',
};
const categoryColors = {
    marketing: 'bg-emerald-100 text-emerald-700', ops: 'bg-purple-100 text-purple-700',
    support: 'bg-cyan-100 text-cyan-700', ai: 'bg-indigo-100 text-indigo-700', other: 'bg-gray-100 text-gray-600',
};

export default function Tasks({ tasks, today }) {
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, reset } = useForm({
        title: '', description: '', category: 'ops', priority: 'normal', recurrence: 'daily', due_date: today,
    });

    const doneToday = tasks.filter((t) => t.done_today).length;
    const openCount = tasks.filter((t) => t.status !== 'completed').length;

    const submit = (e) => {
        e.preventDefault();
        post('/platform/tasks', { onSuccess: () => { setShowForm(false); reset(); } });
    };

    const field = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none';
    const label = 'block text-xs font-medium text-gray-600 mb-1';

    return (
        <PlatformLayout title="Daily Tasks">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">✅ Daily Tasks</h1>
                <button onClick={() => setShowForm(!showForm)} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Add Task</button>
            </div>

            <div className="grid grid-cols-3 gap-3 mb-6">
                {[
                    { label: 'Done Today', value: doneToday, color: 'text-green-600' },
                    { label: 'Open', value: openCount, color: 'text-blue-600' },
                    { label: 'Total Tasks', value: tasks.length, color: 'text-purple-600' },
                ].map((c) => (
                    <div key={c.label} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <p className={`text-xl font-bold ${c.color}`}>{c.value}</p>
                        <p className="text-xs text-gray-500">{c.label}</p>
                    </div>
                ))}
            </div>

            {showForm && (
                <form onSubmit={submit} className="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6 grid grid-cols-2 gap-4">
                    <div className="col-span-2">
                        <label className={label}>Title</label>
                        <input className={field} value={data.title} onChange={(e) => setData('title', e.target.value)} />
                    </div>
                    <div className="col-span-2">
                        <label className={label}>Description</label>
                        <textarea className={field} rows={2} value={data.description} onChange={(e) => setData('description', e.target.value)} />
                    </div>
                    <div>
                        <label className={label}>Category</label>
                        <select className={field} value={data.category} onChange={(e) => setData('category', e.target.value)}>
                            {['marketing', 'ops', 'support', 'ai', 'other'].map((c) => <option key={c} value={c}>{c}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className={label}>Priority</label>
                        <select className={field} value={data.priority} onChange={(e) => setData('priority', e.target.value)}>
                            {['low', 'normal', 'high', 'urgent'].map((p) => <option key={p} value={p}>{p}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className={label}>Recurrence</label>
                        <select className={field} value={data.recurrence} onChange={(e) => setData('recurrence', e.target.value)}>
                            <option value="none">none</option>
                            <option value="daily">daily</option>
                            <option value="weekdays">weekdays</option>
                            <option value="weekly">weekly</option>
                        </select>
                    </div>
                    <div>
                        <label className={label}>Due Date</label>
                        <input type="date" className={field} value={data.due_date} onChange={(e) => setData('due_date', e.target.value)} />
                    </div>
                    <div className="col-span-2 flex gap-2 justify-end">
                        <button type="button" onClick={() => setShowForm(false)} className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium">Cancel</button>
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium disabled:opacity-50">Add Task</button>
                    </div>
                </form>
            )}

            <div className="space-y-2">
                {tasks.map((t) => (
                    <div key={t.id} className={`flex items-center gap-3 bg-white rounded-xl border border-gray-200 p-4 ${t.done_today ? 'opacity-70' : ''}`}>
                        <button
                            onClick={() => router.post(`/platform/tasks/${t.id}/toggle`)}
                            className={`w-6 h-6 rounded-full border-2 flex items-center justify-center text-white text-xs shrink-0 ${t.done_today ? 'bg-green-500 border-green-500' : 'border-gray-300 hover:border-green-400'}`}
                        >
                            {t.done_today ? '✓' : ''}
                        </button>
                        <div className="flex-1 min-w-0">
                            <p className={`font-medium text-gray-900 ${t.done_today ? 'line-through' : ''}`}>{t.title}</p>
                            {t.description && <p className="text-xs text-gray-400 truncate">{t.description}</p>}
                        </div>
                        <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${categoryColors[t.category] || categoryColors.other}`}>{t.category}</span>
                        <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${priorityColors[t.priority] || priorityColors.normal}`}>{t.priority}</span>
                        <span className="text-xs text-gray-400 w-16 text-right">{t.recurrence}</span>
                        {t.streak_count > 0 && <span className="text-xs font-bold text-orange-500">🔥 {t.streak_count}</span>}
                        <button onClick={() => { if (confirm('Delete this task?')) router.delete(`/platform/tasks/${t.id}`); }} className="text-xs text-red-400 hover:text-red-600 font-medium">Delete</button>
                    </div>
                ))}
                {tasks.length === 0 && <p className="text-center text-gray-400 py-10">No tasks yet. Add your first daily task.</p>}
            </div>
        </PlatformLayout>
    );
}
