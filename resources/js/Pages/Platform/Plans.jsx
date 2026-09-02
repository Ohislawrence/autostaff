import React, { useState } from 'react';
import { usePage, useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function Plans({ plans }) {
    const { flash } = usePage().props;
    const { data, setData, put, processing } = useForm({ name: '', price: 0, usd_price: '', currency: 'NGN', max_ai_employees: 1, max_messages_per_month: 100, max_tool_calls_per_month: 50, max_knowledge_sources: 5, is_active: true });
    const [editing, setEditing] = useState(null);

    return (
        <PlatformLayout title="Subscription Plans">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">Subscription Plans</h1>
            <div className="grid grid-cols-4 gap-4">
                {plans.map(p => (
                    <div key={p.id} className={`bg-white rounded-xl shadow-sm border p-6 ${p.is_active ? 'border-gray-200' : 'border-red-200 opacity-60'}`}>
                        <h3 className="font-semibold text-gray-900">{p.name}</h3>
                        <p className="text-xs text-gray-500">{p.description}</p>
                        <p className="text-2xl font-bold mt-2">₦{Number(p.price).toLocaleString()}<span className="text-sm font-normal text-gray-400">/mo</span></p>
                        {p.usd_price != null && Number(p.usd_price) > 0 && <p className="text-xs text-gray-400">${Number(p.usd_price).toLocaleString()} /mo (international)</p>}
                        <ul className="mt-3 space-y-1 text-xs text-gray-500">
                            <li>AI Employees: {p.max_ai_employees}</li>
                            <li>Messages: {p.max_messages_per_month?.toLocaleString()}</li>
                            <li>Tool Calls: {p.max_tool_calls_per_month?.toLocaleString()}</li>
                            <li>Knowledge: {p.max_knowledge_sources}</li>
                        </ul>
                        <button onClick={() => { setEditing(p.id); setData({ name: p.name, price: p.price, usd_price: p.usd_price ?? '', currency: p.currency || 'NGN', max_ai_employees: p.max_ai_employees, max_messages_per_month: p.max_messages_per_month, max_tool_calls_per_month: p.max_tool_calls_per_month, max_knowledge_sources: p.max_knowledge_sources, is_active: p.is_active }); }} className="mt-3 text-xs text-purple-600">Edit</button>
                    </div>
                ))}
            </div>
            {editing && (
                <form onSubmit={(e) => { e.preventDefault(); put(`/platform/plans/${editing}`, { onSuccess: () => setEditing(null) }); }} className="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="font-semibold mb-3">Edit Plan</h3>
                    <div className="grid grid-cols-3 gap-3">
                        <input value={data.name} onChange={e => setData('name', e.target.value)} className="px-3 py-2 border rounded text-sm" required />
                        <input type="number" value={data.price} onChange={e => setData('price', e.target.value)} className="px-3 py-2 border rounded text-sm" step="0.01" placeholder="NGN price" />
                        <input type="number" value={data.usd_price} onChange={e => setData('usd_price', e.target.value)} className="px-3 py-2 border rounded text-sm" step="0.01" placeholder="USD price" />
                        <select value={data.currency} onChange={e => setData('currency', e.target.value)} className="px-3 py-2 border rounded text-sm">
                            <option value="NGN">NGN (₦)</option>
                            <option value="USD">USD ($)</option>
                        </select>
                        <input type="number" value={data.max_ai_employees} onChange={e => setData('max_ai_employees', e.target.value)} className="px-3 py-2 border rounded text-sm" />
                    </div>
                    <div className="flex gap-2 mt-3"><button type="submit" disabled={processing} className="px-4 py-2 bg-purple-600 text-white rounded text-sm">Save</button><button type="button" onClick={() => setEditing(null)} className="px-4 py-2 border rounded text-sm">Cancel</button></div>
                </form>
            )}
        </PlatformLayout>
    );
}