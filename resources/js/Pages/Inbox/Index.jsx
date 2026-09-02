import { Head, Link, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index({ conversations, counts, filters }) {
    const { auth, flash } = usePage().props;

    const statusColors = {
        open: 'bg-blue-100 text-blue-700',
        ai_handling: 'bg-purple-100 text-purple-700',
        human_required: 'bg-red-100 text-red-700',
        assigned: 'bg-orange-100 text-orange-700',
        waiting_customer: 'bg-yellow-100 text-yellow-700',
        resolved: 'bg-green-100 text-green-700',
        closed: 'bg-gray-100 text-gray-600',
    };

    const statusLabels = {
        open: 'Open',
        ai_handling: 'AI Handling',
        human_required: 'Needs Human',
        assigned: 'Assigned',
        waiting_customer: 'Waiting',
        resolved: 'Resolved',
        closed: 'Closed',
    };

    const filterTabs = [
        { key: '', label: 'All', count: counts.all },
        { key: 'open', label: 'Open', count: counts.open },
        { key: 'human_required', label: 'Needs Human', count: counts.human_required },
        { key: 'waiting_customer', label: 'Waiting', count: counts.waiting },
        { key: 'resolved', label: 'Resolved', count: counts.resolved },
    ];

    return (
        <TenantLayout header="Inbox">
            <Head title="Inbox" />

            {/* Filter Tabs */}
            <div className="flex items-center gap-2 mb-4 overflow-x-auto pb-2">
                {filterTabs.map(tab => {
                    const isActive = (filters.status || '') === tab.key;
                    return (
                        <Link
                            key={tab.key}
                            href={`/inbox${tab.key ? `?status=${tab.key}` : ''}`}
                            className={`px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors ${
                                isActive ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
                            }`}
                        >
                            {tab.label} ({tab.count})
                        </Link>
                    );
                })}
            </div>

            {/* Search */}
            <form className="mb-4">
                <input
                    type="text"
                    name="search"
                    defaultValue={filters.search}
                    placeholder="Search conversations..."
                    className="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-primary-200 focus:border-primary-400"
                />
            </form>

            {/* Conversation List */}
            <div className="space-y-2">
                {conversations?.data?.map(conv => {
                    const customer = conv.customer;
                    const latestMsg = conv.latest_message;
                    const customerName = customer
                        ? `${customer.first_name || ''} ${customer.last_name || ''}`.trim() || customer.email
                        : 'Unknown Customer';

                    return (
                        <Link
                            key={conv.id}
                            href={`/inbox/${conv.id}`}
                            className="block bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow"
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div className="flex items-start gap-3 flex-1 min-w-0">
                                    {/* Customer Avatar */}
                                    <div className={`w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 ${
                                        conv.status === 'human_required' ? 'bg-red-100' :
                                        conv.status === 'assigned' ? 'bg-orange-100' :
                                        'bg-gray-100'
                                    }`}>
                                        <span className={`text-sm font-bold ${
                                            conv.status === 'human_required' ? 'text-red-600' :
                                            conv.status === 'assigned' ? 'text-orange-600' :
                                            'text-gray-600'
                                        }`}>
                                            {customerName[0]?.toUpperCase() || '?'}
                                        </span>
                                    </div>

                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2 mb-0.5">
                                            <span className="font-medium text-gray-900 text-sm">{customerName}</span>
                                            {conv.ai_employee && (
                                                <span className="text-xs text-gray-400">via {conv.ai_employee.name}</span>
                                            )}
                                        </div>
                                        <p className="text-xs text-gray-500 truncate">{conv.subject || 'No subject'}</p>
                                        {latestMsg && (
                                            <p className="text-xs text-gray-400 mt-1 truncate">
                                                {latestMsg.type === 'ai_response' ? '🤖 ' : latestMsg.type === 'incoming' ? '📥 ' : '👤 '}
                                                {latestMsg.content?.substring(0, 120)}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="text-right flex-shrink-0">
                                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[conv.status] || 'bg-gray-100 text-gray-600'}`}>
                                        {statusLabels[conv.status] || conv.status}
                                    </span>
                                    <p className="text-xs text-gray-400 mt-2">{new Date(conv.last_message_at || conv.updated_at).toLocaleDateString()}</p>
                                    <p className="text-xs text-gray-400">{conv.messages_count || 0} msgs</p>
                                </div>
                            </div>
                        </Link>
                    );
                })}

                {(!conversations?.data || conversations.data.length === 0) && (
                    <div className="text-center py-12">
                        <p className="text-3xl mb-3">📭</p>
                        <p className="text-gray-500">No conversations found.</p>
                        <p className="text-sm text-gray-400 mt-1">Conversations will appear here when customers interact with your AI Employees.</p>
                    </div>
                )}
            </div>
        </TenantLayout>
    );
}