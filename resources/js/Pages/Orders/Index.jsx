import { Head, Link, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index({ orders, stats, filters }) {
    const { flash, auth } = usePage().props;
    const currency = auth.organization?.currency || 'NGN';
    const currencySymbol = { USD: '$', NGN: '₦', GHS: 'GH₵', KES: 'KSh', ZAR: 'R', EUR: '€', GBP: '£', CAD: 'CA$', AUD: 'A$', INR: '₹', JPY: '¥' }[currency] || (currency + ' ');

    const statusBadge = (status) => {
        const map = {
            pending: 'bg-yellow-100 text-yellow-700',
            confirmed: 'bg-blue-100 text-blue-700',
            processing: 'bg-purple-100 text-purple-700',
            shipped: 'bg-indigo-100 text-indigo-700',
            delivered: 'bg-green-100 text-green-700',
            cancelled: 'bg-red-100 text-red-700',
            refunded: 'bg-gray-100 text-gray-600',
        };
        return map[status] || 'bg-gray-100 text-gray-600';
    };

    return (
        <TenantLayout header="Orders">
            <Head title="Orders" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}

            {/* Stats */}
            <div className="grid grid-cols-4 gap-4 mb-6">
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <p className="text-2xl font-bold text-gray-900">{stats.total}</p>
                    <p className="text-xs text-gray-500">Total Orders</p>
                </div>
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <p className="text-2xl font-bold text-yellow-600">{stats.pending}</p>
                    <p className="text-xs text-gray-500">Pending</p>
                </div>
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <p className="text-2xl font-bold text-green-600">{stats.completed}</p>
                    <p className="text-xs text-gray-500">Completed</p>
                </div>
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <p className="text-2xl font-bold text-primary-600">{currencySymbol}{parseFloat(stats.revenue || 0).toFixed(2)}</p>
                    <p className="text-xs text-gray-500">Revenue</p>
                </div>
            </div>

            <form className="flex gap-2 mb-4">
                <input type="text" name="search" defaultValue={filters.search} placeholder="Search by order number or customer..."
                    className="px-3 py-2 border border-gray-300 rounded-lg text-sm flex-1" />
                <select name="status" defaultValue={filters.status} className="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All Status</option>
                    {['pending','confirmed','processing','shipped','delivered','cancelled'].map(s => <option key={s} value={s}>{s}</option>)}
                </select>
                <button type="submit" className="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">Filter</button>
            </form>

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Order</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Customer</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Total</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {orders.data?.map(order => (
                            <tr key={order.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3">
                                    <Link href={`/orders/${order.id}`} className="font-medium text-primary-600 hover:text-primary-700">
                                        #{order.order_number}
                                    </Link>
                                </td>
                                <td className="px-4 py-3 text-gray-500">{order.customer?.first_name} {order.customer?.last_name}</td>
                                <td className="px-4 py-3 font-medium text-gray-900">{order.currency} {order.total}</td>
                                <td className="px-4 py-3">
                                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium capitalize ${statusBadge(order.status)}`}>{order.status}</span>
                                </td>
                                <td className="px-4 py-3 text-xs text-gray-400">{new Date(order.created_at).toLocaleDateString()}</td>
                            </tr>
                        ))}
                        {orders.data?.length === 0 && (
                            <tr><td colSpan={5} className="px-4 py-8 text-center text-gray-400">No orders yet.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
        </TenantLayout>
    );
}