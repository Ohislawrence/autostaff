import { Head, Link, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

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

export default function Show({ customer, leadScore }) {
    const { flash } = usePage().props;
    const fullName = `${customer.first_name || ''} ${customer.last_name || ''}`.trim();

    return (
        <TenantLayout header={fullName || 'Customer'}>
            <Head title={fullName || 'Customer'} />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}

            <Link href="/customers" className="text-sm text-gray-500 hover:text-gray-700 mb-4 inline-flex items-center gap-1">
                ← Back to Customers
            </Link>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="space-y-4">
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <h3 className="font-semibold text-gray-900 mb-3">Profile</h3>
                        <div className="space-y-2 text-sm text-gray-600">
                            {customer.email && <p>📧 {customer.email}</p>}
                            {customer.phone && <p>📱 {customer.phone}</p>}
                            {customer.company && <p>🏢 {customer.company}</p>}
                            {customer.source && <p className="text-xs text-gray-400">Source: {customer.source}</p>}
                            {customer.channel && <p className="text-xs text-gray-400">Channel: {customer.channel}</p>}
                            <p className="text-xs text-gray-400">
                                Last contacted: {customer.last_contacted_at ? new Date(customer.last_contacted_at).toLocaleString() : '—'}
                            </p>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <h3 className="font-semibold text-gray-900 mb-3">Lead</h3>
                        <div className="flex items-center gap-2 mb-3">
                            <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${stageBadge(customer.lead_stage)}`}>
                                {customer.lead_stage || 'n/a'}
                            </span>
                            <span className="text-2xl font-bold text-gray-900">{leadScore?.total ?? customer.lead_score ?? 0}</span>
                            <span className="text-xs text-gray-400">lead score</span>
                        </div>
                        {leadScore?.breakdown && Object.keys(leadScore.breakdown).length > 0 && (
                            <ul className="text-xs text-gray-500 space-y-1">
                                {Object.entries(leadScore.breakdown).map(([reason, points]) => (
                                    <li key={reason}>+{points} {reason.replace(/_/g, ' ')}</li>
                                ))}
                            </ul>
                        )}
                        {customer.notes && <p className="mt-3 text-sm text-gray-600">{customer.notes}</p>}
                        {customer.tags?.length > 0 && (
                            <div className="mt-3 flex flex-wrap gap-1">
                                {customer.tags.map((tag, i) => (
                                    <span key={i} className="px-2 py-0.5 bg-gray-100 rounded-full text-xs">{tag}</span>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                <div className="md:col-span-2 space-y-4">
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <h3 className="font-semibold text-gray-900 mb-3">Recent Conversations</h3>
                        {customer.conversations?.length > 0 ? (
                            <div className="space-y-2">
                                {customer.conversations.map((conv) => (
                                    <Link
                                        key={conv.id}
                                        href={`/inbox/${conv.id}`}
                                        className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100"
                                    >
                                        <div>
                                            <p className="text-sm font-medium text-gray-800">{conv.subject || 'Conversation'}</p>
                                            <p className="text-xs text-gray-400">{conv.channel}</p>
                                        </div>
                                        <span className="text-xs text-gray-400 capitalize">{conv.status}</span>
                                    </Link>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-gray-400">No conversations yet.</p>
                        )}
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <h3 className="font-semibold text-gray-900 mb-3">Recent Orders</h3>
                        {customer.orders?.length > 0 ? (
                            <div className="space-y-2">
                                {customer.orders.map((order) => (
                                    <div key={order.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <span className="text-sm text-gray-800">Order #{order.id}</span>
                                        <span className="text-sm font-medium text-gray-900">
                                            {order.currency ? `${order.currency} ` : ''}{order.total}
                                        </span>
                                        <span className="text-xs text-gray-400 capitalize">{order.status}</span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-gray-400">No orders yet.</p>
                        )}
                    </div>

                    {customer.leads?.length > 0 && (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                            <h3 className="font-semibold text-gray-900 mb-3">Leads</h3>
                            <div className="space-y-2">
                                {customer.leads.map((lead) => (
                                    <div key={lead.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <span className="text-sm text-gray-800">{lead.name || lead.title || `Lead #${lead.id}`}</span>
                                        <span className="text-xs text-gray-400 capitalize">{lead.stage || lead.status}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </TenantLayout>
    );
}
