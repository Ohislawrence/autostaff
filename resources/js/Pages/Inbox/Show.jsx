import { useState } from 'react';
import { Head, Link, usePage, useForm } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Show({ conversation }) {
    const { auth, flash } = usePage().props;
    const { data, setData, post, processing } = useForm({ body: '' });
    const { post: resolvePost } = useForm();
    const { post: assignPost } = useForm();

    const customer = conversation.customer;
    const customerName = customer
        ? `${customer.first_name || ''} ${customer.last_name || ''}`.trim() || customer.email
        : 'Unknown Customer';

    const messageTypeStyles = {
        incoming: 'bg-gray-50 border-gray-200',
        ai_response: 'bg-purple-50 border-purple-200',
        human_response: 'bg-blue-50 border-blue-200',
        system_event: 'bg-yellow-50 border-yellow-200 text-center text-xs text-gray-500',
    };

    const messageTypeIcons = {
        incoming: '📥',
        ai_response: '🤖',
        human_response: '👤',
        system_event: '⚙️',
    };

    const handleReply = (e) => {
        e.preventDefault();
        post(`/inbox/${conversation.id}/reply`, {
            onSuccess: () => setData('body', ''),
        });
    };

    return (
        <TenantLayout header="Conversation">
            <Head title={`Conversation — ${customerName}`} />

            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}

            <div className="flex gap-6">
                {/* Main conversation area */}
                <div className="flex-1 min-w-0">
                    {/* Header */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <Link href="/inbox" className="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-block">← Back to Inbox</Link>
                                <h1 className="text-lg font-bold text-gray-900">{conversation.subject || 'No Subject'}</h1>
                                <p className="text-sm text-gray-500 mt-0.5">
                                    {customerName} · {conversation.channel || 'chat'} · {conversation.messages?.length || 0} messages
                                </p>
                            </div>
                            <div className="flex items-center gap-2">
                                <button
                                    onClick={() => assignPost(`/inbox/${conversation.id}/assign`)}
                                    className="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50"
                                >
                                    Assign to Me
                                </button>
                                <button
                                    onClick={() => { if (confirm('Resolve this conversation?')) resolvePost(`/inbox/${conversation.id}/resolve`); }}
                                    className="px-3 py-1.5 bg-green-600 text-white rounded-lg text-sm font-medium"
                                >
                                    Resolve
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Messages */}
                    <div className="space-y-3 mb-4">
                        {conversation.messages?.map(msg => (
                            <div key={msg.id} className={`rounded-xl border p-4 ${messageTypeStyles[msg.type] || 'bg-gray-50 border-gray-200'}`}>
                                <div className="flex items-center gap-2 mb-1">
                                    <span>{messageTypeIcons[msg.type] || '💬'}</span>
                                    <span className="text-xs font-medium text-gray-500 capitalize">{msg.type?.replace('_', ' ')}</span>
                                    {msg.sender_name && <span className="text-xs text-gray-400">by {msg.sender_name}</span>}
                                    <span className="text-xs text-gray-400 ml-auto">{new Date(msg.created_at).toLocaleTimeString()}</span>
                                </div>
                                <p className="text-sm text-gray-700 whitespace-pre-wrap">{msg.content}</p>
                            </div>
                        ))}
                        {(!conversation.messages || conversation.messages.length === 0) && (
                            <p className="text-center text-gray-400 py-8">No messages yet.</p>
                        )}
                    </div>

                    {/* Reply Box */}
                    {conversation.status !== 'resolved' && conversation.status !== 'closed' && (
                        <form onSubmit={handleReply} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                            <textarea
                                value={data.body}
                                onChange={e => setData('body', e.target.value)}
                                className="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm resize-none focus:ring-2 focus:ring-primary-200 focus:border-primary-400"
                                placeholder="Type your reply..."
                                rows={3}
                                required
                            />
                            <div className="flex items-center justify-between mt-3">
                                <p className="text-xs text-gray-400">Replying as {auth.user?.name} ({auth.user?.roles?.[0]})</p>
                                <button
                                    type="submit"
                                    disabled={processing || !data.body.trim()}
                                    className="px-6 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium disabled:opacity-50"
                                >
                                    {processing ? 'Sending...' : 'Send Reply'}
                                </button>
                            </div>
                        </form>
                    )}
                </div>

                {/* Sidebar — Customer Info */}
                <div className="w-72 flex-shrink-0 space-y-4">
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <h3 className="font-semibold text-gray-900 mb-3">Customer</h3>
                        <div className="space-y-2 text-sm">
                            <div className="flex items-center gap-2">
                                <div className="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                                    <span className="text-primary-700 font-bold">{customerName[0]?.toUpperCase()}</span>
                                </div>
                                <div>
                                    <p className="font-medium text-gray-900">{customerName}</p>
                                    {customer?.email && <p className="text-xs text-gray-500">{customer.email}</p>}
                                </div>
                            </div>
                            {customer?.phone && <p className="text-xs text-gray-500">📞 {customer.phone}</p>}
                            {customer?.company && <p className="text-xs text-gray-500">🏢 {customer.company}</p>}
                        </div>
                        {customer?.lead_stage && (
                            <div className="mt-3 pt-3 border-t border-gray-100">
                                <p className="text-xs text-gray-500 mb-1">Lead Stage</p>
                                <span className="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-medium capitalize">{customer.lead_stage}</span>
                            </div>
                        )}
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <h3 className="font-semibold text-gray-900 mb-3">Details</h3>
                        <div className="space-y-2 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Status</span>
                                <span className="font-medium capitalize">{conversation.status?.replace('_', ' ')}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Channel</span>
                                <span className="font-medium capitalize">{conversation.channel || 'chat'}</span>
                            </div>
                            {conversation.ai_employee && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">AI Employee</span>
                                    <span className="font-medium">{conversation.ai_employee.name}</span>
                                </div>
                            )}
                            <div className="flex justify-between">
                                <span className="text-gray-500">Started</span>
                                <span className="font-medium text-xs">{new Date(conversation.created_at).toLocaleDateString()}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </TenantLayout>
    );
}