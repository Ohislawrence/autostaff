import { Head, Link, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index() {
    const { flash } = usePage().props;

    const integrations = [
        { name: 'Website Chat', key: 'webchat', icon: '💬', desc: 'Embeddable chat widget for your business website.', status: 'Available', color: 'bg-green-100 text-green-700' },
        { name: 'Email', key: 'email', icon: '📧', desc: 'Handle customer inquiries via email. Connect SMTP, Mailgun, or any provider.', status: 'Available', color: 'bg-green-100 text-green-700', href: '/integrations/email' },
        { name: 'WhatsApp', key: 'whatsapp', icon: '📱', desc: 'Connect WhatsApp Business API to handle customer messages.', status: 'Available', color: 'bg-green-100 text-green-700', href: '/integrations/whatsapp' },
        { name: 'MCP External Tools', key: 'mcp', icon: '🤖', desc: 'Connect Gmail, Calendar, CRM, Microsoft 365 and custom MCP servers as AI tools.', status: 'Available', color: 'bg-green-100 text-green-700', href: '/integrations/mcp' },
        { name: 'REST API', key: 'api', icon: '🔌', desc: 'Integrate with your existing systems via our API.', status: 'Available', color: 'bg-green-100 text-green-700' },
        { name: 'Webhooks', key: 'webhooks', icon: '🔄', desc: 'Receive real-time event notifications to your endpoint.', status: 'Available', color: 'bg-green-100 text-green-700' },
        { name: 'WooCommerce', key: 'woocommerce', icon: '🛒', desc: 'Connect your WooCommerce store for product-aware AI.', status: 'Available', color: 'bg-green-100 text-green-700', href: '/integrations/woocommerce' },
        { name: 'Shopify', key: 'shopify', icon: '🛍️', desc: 'Connect your Shopify store for product-aware AI.', status: 'Available', color: 'bg-green-100 text-green-700', href: '/integrations/shopify' },
    ];

    return (
        <TenantLayout header="Integrations">
            <Head title="Integrations" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}

            <p className="text-sm text-gray-500 mb-6">Connect your AI Employee platform to external services and channels.</p>

            <div className="grid grid-cols-2 gap-4">
                {integrations.map(i => {
                    const card = (
                        <div key={i.key} className="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
                            <div className="flex items-start gap-3">
                                <span className="text-2xl">{i.icon}</span>
                                <div className="flex-1">
                                    <div className="flex items-center justify-between mb-1">
                                        <h3 className="font-semibold text-gray-900">{i.name}</h3>
                                        <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${i.color}`}>
                                            {i.status}
                                        </span>
                                    </div>
                                    <p className="text-xs text-gray-500 mt-1">{i.desc}</p>
                                    {i.href && (
                                        <Link href={i.href} className="mt-3 inline-block text-xs text-primary-600 font-medium hover:text-primary-700">
                                            Configure →
                                        </Link>
                                    )}
                                </div>
                            </div>
                        </div>
                    );

                    return card;
                })}
            </div>
        </TenantLayout>
    );
}