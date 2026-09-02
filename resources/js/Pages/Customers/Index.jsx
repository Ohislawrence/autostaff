import { Head, Link, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index({ customers, stats, filters }) {
    const { flash } = usePage().props;

    const stageBadge = (stage) => {
        const map = {
            new: 'bg-blue-100 text-blue-700',
            contacted: 'bg-yellow-100 text-yellow-700',
            qualified: 'bg-green-100 text-green-700',
            proposal: 'bg-purple-100 text-purple-700',
            won: 'bg-emerald-100 text-emerald-700',
            lost: 'bg-red-100 text-red-700',
        };
        return map[stage] || 'bg-gray-100 text-gray-600';
    };

    const scoreColor = (score) => {
        if (score >= 80) return 'text-emerald-600';
        if (score >= 50) return 'text-yellow-600';
        return 'text-gray-400';
    };

    return (
        <TenantLayout header="Customers">
            <Head title="Customers" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}

            {/* Stats */}
            <div className="grid grid-cols-3 gap-4 mb-6">
                {[
                    { label: 'Total', value: stats.total },
                    { label: 'With Leads', value: stats.with_leads },
                    { label: 'Qualified', value: stats.qualified },
                ].map(s => (
                    <div key={s.label} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <p className="text-2xl font-bold text-gray-900">{s.value}</p>
                        <p className="text-xs text-gray-500">{s.label}</p>
                    </div>
                ))}
            </div>

            {/* Filter */}
            <form className="flex gap-2 mb-4">
                <input type="text" name="search" defaultValue={filters.search} placeholder="Search customers..."
                    className="px-3 py-2 border border-gray-300 rounded-lg text-sm flex-1" />
                <select name="stage" defaultValue={filters.stage} className="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All Stages</option>
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="qualified">Qualified</option>
                </select>
                <button type="submit" className="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">Filter</button>
            </form>

            {/* Table */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Customer</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Contact</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Stage</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Score</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Activity</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {customers.data?.map(customer => (
                            <tr key={customer.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3">
                                    <Link href={`/customers/${customer.id}`} className="font-medium text-gray-900 hover:text-primary-600">
                                        {customer.first_name} {customer.last_name}
                                    </Link>
                                    {customer.company && <p className="text-xs text-gray-400">{customer.company}</p>}
                                </td>
                                <td className="px-4 py-3 text-gray-500">
                                    {customer.email && <p className="text-xs">{customer.email}</p>}
                                    {customer.phone && <p className="text-xs">{customer.phone}</p>}
                                </td>
                                <td className="px-4 py-3">
                                    {customer.lead_stage ? (
                                        <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${stageBadge(customer.lead_stage)}`}>
                                            {customer.lead_stage}
                                        </span>
                                    ) : (
                                        <span className="text-xs text-gray-400">—</span>
                                    )}
                                </td>
                                <td className={`px-4 py-3 font-medium ${scoreColor(customer.lead_score)}`}>
                                    {customer.lead_score}
                                </td>
                                <td className="px-4 py-3 text-xs text-gray-400">
                                    {customer.conversations_count > 0 && <span>💬 {customer.conversations_count} </span>}
                                    {customer.orders_count > 0 && <span>📦 {customer.orders_count} </span>}
                                </td>
                            </tr>
                        ))}
                        {customers.data?.length === 0 && (
                            <tr><td colSpan={5} className="px-4 py-8 text-center text-gray-400">No customers found.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {customers.links && (
                <div className="flex items-center justify-between mt-4 text-sm">
                    <p className="text-gray-500">Showing {customers.from || 0} to {customers.to || 0} of {customers.total} customers</p>
                    <div className="flex gap-1" dangerouslySetInnerHTML={{ __html: customers.links?.map(l => l.url ? `<a href="${l.url}" class="px-3 py-1 rounded ${l.active ? 'bg-primary-600 text-white' : 'bg-white border border-gray-200 text-gray-600'}">${l.label}</a>` : '').join('') }} />
                </div>
            )}
        </TenantLayout>
    );
}