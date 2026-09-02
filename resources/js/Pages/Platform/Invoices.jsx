import { useState } from 'react';
import { router } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-700',
    paid: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
    cancelled: 'bg-gray-100 text-gray-600',
};

export default function Invoices({ invoices, organizations, filters }) {
    const [f, setF] = useState({
        search: filters.search || '',
        status: filters.status || '',
        organization: filters.organization || '',
    });

    const apply = (e) => {
        e.preventDefault();
        const params = {};
        if (f.search) params.search = f.search;
        if (f.status) params.status = f.status;
        if (f.organization) params.organization = f.organization;
        router.get('/platform/invoices', params, { preserveState: true });
    };

    const field = 'px-3 py-2 border border-gray-300 rounded-lg text-sm';

    return (
        <PlatformLayout title="Invoices">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">🧾 Invoices</h1>

            <form onSubmit={apply} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4 flex gap-3">
                <input className={field} placeholder="Invoice # or company…" value={f.search} onChange={e => setF({ ...f, search: e.target.value })} />
                <select className={field} value={f.status} onChange={e => setF({ ...f, status: e.target.value })}>
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="failed">Failed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select className={field} value={f.organization} onChange={e => setF({ ...f, organization: e.target.value })}>
                    <option value="">All tenants</option>
                    {organizations.map(o => <option key={o.id} value={o.id}>{o.name}</option>)}
                </select>
                <button type="submit" className="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium">Filter</button>
            </form>

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Invoice</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Tenant</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Total</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {(invoices.data || []).map(inv => (
                            <tr key={inv.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3 font-medium text-gray-900">{inv.invoice_number}</td>
                                <td className="px-4 py-3 text-gray-600">{inv.organization?.name || '—'}</td>
                                <td className="px-4 py-3 text-gray-700">{inv.currency === 'NGN' ? '₦' : '$'}{Number(inv.total).toLocaleString()}</td>
                                <td className="px-4 py-3"><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[inv.status] || 'bg-gray-100 text-gray-600'}`}>{inv.status}</span></td>
                                <td className="px-4 py-3 text-xs text-gray-400">{new Date(inv.created_at).toLocaleDateString()}</td>
                                <td className="px-4 py-3">
                                    <div className="flex gap-2">
                                        <a href={`/platform/invoices/${inv.id}`} className="text-blue-600 text-xs font-medium hover:underline">View</a>
                                        {inv.status !== 'paid' && (
                                            <button onClick={() => { if (confirm(`Mark ${inv.invoice_number} as paid?`)) router.post(`/platform/invoices/${inv.id}/mark-paid`); }} className="text-green-600 text-xs font-medium hover:underline">Mark Paid</button>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {(invoices.data || []).length === 0 && <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-400">No invoices found.</td></tr>}
                    </tbody>
                </table>
            </div>
        </PlatformLayout>
    );
}
