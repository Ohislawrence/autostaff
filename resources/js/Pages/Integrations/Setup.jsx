import { Head, usePage, router, Link } from '@inertiajs/react';
import { useState } from 'react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Setup({ channel, integration }) {
    const { errors, flash } = usePage().props;
    const [submitting, setSubmitting] = useState(false);
    const [syncing, setSyncing] = useState(false);
    const [testing, setTesting] = useState(false);
    const [form, setForm] = useState(() => {
        const initial = {};
        if (channel?.schema?.properties) {
            Object.keys(channel.schema.properties).forEach((key) => {
                const prop = channel.schema.properties[key];
                initial[key] = integration?.config?.[key] ?? (prop.default ?? (prop.type === 'boolean' ? false : ''));
            });
        }
        return initial;
    });

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm({ ...form, [name]: type === 'checkbox' ? checked : value });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setSubmitting(true);
        router.post(`/integrations/${channel.key}/save`, form, {
            onFinish: () => setSubmitting(false),
        });
    };

    const handleDisconnect = () => {
        if (confirm(`Are you sure you want to disconnect ${channel.name}?`)) {
            router.delete(`/integrations/${channel.key}/disconnect`);
        }
    };

    const handleSync = () => {
        setSyncing(true);
        router.post(`/integrations/${channel.key}/sync`, {}, {
            onFinish: () => setSyncing(false),
        });
    };

    const handleTest = () => {
        setTesting(true);
        router.post(`/integrations/${channel.key}/test`, form, {
            onFinish: () => setTesting(false),
        });
    };

    const isConnected = integration?.is_connected ?? false;

    return (
        <TenantLayout header={`${channel.name} Integration`}>
            <Head title={`${channel.name} Setup`} />

            <div className="max-w-2xl">
                {/* Back link */}
                <Link href="/integrations" className="text-sm text-gray-500 hover:text-gray-700 mb-4 inline-flex items-center gap-1">
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Integrations
                </Link>

                {flash?.success && (
                    <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>
                )}
                {flash?.error && (
                    <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-lg text-sm">{flash.error}</div>
                )}

                {/* Status badge */}
                <div className="mb-6 flex items-center gap-3">
                    <div className={`w-3 h-3 rounded-full ${isConnected ? 'bg-green-500' : 'bg-gray-300'}`} />
                    <span className="text-sm font-medium text-gray-700">
                        {isConnected ? 'Connected' : 'Not connected'}
                    </span>
                    {integration?.last_synced_at && (
                        <span className="text-xs text-gray-400">
                            Last synced: {new Date(integration.last_synced_at).toLocaleString()}
                        </span>
                    )}
                    {isConnected && ['woocommerce', 'shopify'].includes(channel.key) && (
                        <button
                            type="button"
                            onClick={handleSync}
                            disabled={syncing}
                            className="ml-auto px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-semibold hover:bg-blue-700 disabled:opacity-50"
                        >
                            {syncing ? 'Syncing...' : 'Sync Products'}
                        </button>
                    )}
                </div>

                {/* Setup form */}
                <form onSubmit={handleSubmit} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">Configuration</h3>
                    <p className="text-sm text-gray-500 mb-6">
                        Configure your {channel.name} integration settings below.
                    </p>

                    <div className="space-y-5">
                        {channel.schema?.properties && Object.entries(channel.schema.properties).map(([key, prop]) => {
                            const isRequired = (channel.schema.required || []).includes(key);
                            const fieldId = `field_${key}`;

                            return (
                                <div key={key}>
                                    <label htmlFor={fieldId} className="block text-sm font-medium text-gray-700 mb-1 capitalize">
                                        {key.replace(/_/g, ' ')}
                                        {isRequired && <span className="text-red-500 ml-1">*</span>}
                                        {integration?.saved_keys?.includes(key) && (
                                            <span className="ml-2 text-xs text-green-600 font-medium">✓ saved</span>
                                        )}
                                    </label>

                                    {prop.enum ? (
                                        <select
                                            id={fieldId}
                                            name={key}
                                            value={form[key] || ''}
                                            onChange={handleChange}
                                            required={isRequired}
                                            className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                                        >
                                            <option value="">Select...</option>
                                            {prop.enum.map((opt) => (
                                                <option key={opt} value={opt}>{opt}</option>
                                            ))}
                                        </select>
                                    ) : prop.type === 'boolean' ? (
                                        <div className="flex items-center gap-3">
                                            <input
                                                id={fieldId}
                                                name={key}
                                                type="checkbox"
                                                checked={form[key] || false}
                                                onChange={handleChange}
                                                className="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                            />
                                            <span className="text-sm text-gray-600">Enabled</span>
                                        </div>
                                    ) : prop.type === 'string' && (key.includes('secret') || key.includes('password') || key.includes('token') || key.includes('key')) ? (
                                        <input
                                            id={fieldId}
                                            name={key}
                                            type="password"
                                            value={form[key] || ''}
                                            onChange={handleChange}
                                            required={isRequired}
                                            placeholder={prop.description || ''}
                                            className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none font-mono"
                                        />
                                    ) : (
                                        <input
                                            id={fieldId}
                                            name={key}
                                            type="text"
                                            value={form[key] || ''}
                                            onChange={handleChange}
                                            required={isRequired}
                                            placeholder={prop.description || ''}
                                            className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                                        />
                                    )}

                                    {prop.description && (
                                        <p className="mt-1 text-xs text-gray-400">{prop.description}</p>
                                    )}
                                    {errors?.[key] && (
                                        <p className="mt-1 text-xs text-red-500">{errors[key]}</p>
                                    )}
                                </div>
                            );
                        })}
                    </div>

                    <div className="flex items-center gap-3 mt-6 pt-6 border-t border-gray-100">
                        {channel.key === 'woocommerce' && (
                            <button
                                type="button"
                                onClick={handleTest}
                                disabled={testing}
                                className="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 disabled:opacity-50 transition-colors"
                            >
                                {testing ? 'Testing…' : 'Test Connection'}
                            </button>
                        )}

                        <button
                            type="submit"
                            disabled={submitting}
                            className="px-5 py-2.5 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition-colors"
                        >
                            {submitting ? 'Saving...' : (isConnected ? 'Update Configuration' : 'Connect')}
                        </button>

                        {isConnected && (
                            <button
                                type="button"
                                onClick={handleDisconnect}
                                className="px-5 py-2.5 bg-red-50 text-red-600 rounded-lg text-sm font-semibold hover:bg-red-100 transition-colors"
                            >
                                Disconnect
                            </button>
                        )}
                    </div>
                </form>

                {/* Instructions / help */}
                {channel.key === 'email' && (
                    <div className="mt-6 bg-blue-50 rounded-xl border border-blue-100 p-5">
                        <h4 className="text-sm font-semibold text-blue-800 mb-2">📧 Email Integration Guide</h4>
                        <ol className="text-sm text-blue-700 space-y-2 list-decimal list-inside">
                            <li>Set your <code className="bg-blue-100 px-1 rounded text-xs">MAIL_MAILER</code> and <code className="bg-blue-100 px-1 rounded text-xs">MAIL_FROM_ADDRESS</code> in your <code className="bg-blue-100 px-1 rounded text-xs">.env</code> file</li>
                            <li>For inbound email, configure a webhook from your email provider (Mailgun, SendGrid, etc.) to <code className="bg-blue-100 px-1 rounded text-xs">/api/v1/webhooks/email</code></li>
                            <li>Select your preferred inbound/outbound drivers above</li>
                            <li>Set a webhook secret to verify incoming email webhooks</li>
                            <li>Optionally enable auto-reply so your AI Employee responds to emails automatically</li>
                        </ol>
                    </div>
                )}

                {channel.key === 'whatsapp' && (
                    <div className="mt-6 bg-green-50 rounded-xl border border-green-100 p-5">
                        <h4 className="text-sm font-semibold text-green-800 mb-2">📱 WhatsApp Integration Guide</h4>
                        <ol className="text-sm text-green-700 space-y-2 list-decimal list-inside">
                            <li>Create a <strong>WhatsApp Business Account</strong> at <a href="https://business.facebook.com" target="_blank" rel="noopener noreferrer" className="underline">business.facebook.com</a></li>
                            <li>In Meta Developer Portal, create an App and add <strong>WhatsApp</strong> product</li>
                            <li>Get your <strong>Access Token</strong> and <strong>Phone Number ID</strong> from the WhatsApp dashboard</li>
                            <li>Configure webhook URL: <code className="bg-green-100 px-1 rounded text-xs">{window.location.origin}/api/v1/webhooks/whatsapp</code></li>
                            <li>Set a <strong>Verify Token</strong> (any random string) - use the same value in both Meta and above</li>
                            <li>Subscribe to webhook events: <code className="bg-green-100 px-1 rounded text-xs">messages</code></li>
                            <li>Complete Meta Business verification (required for production)</li>
                        </ol>
                        <div className="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p className="text-xs text-yellow-800">
                                ⚠️ <strong>Note:</strong> WhatsApp Business API requires Meta Business verification. This process typically takes 2-5 business days. 
                                Test mode is available immediately with limited phone numbers.
                            </p>
                        </div>
                    </div>
                )}

                {channel.key === 'woocommerce' && (
                    <div className="mt-6 bg-purple-50 rounded-xl border border-purple-100 p-5">
                        <h4 className="text-sm font-semibold text-purple-800 mb-2">🛒 WooCommerce Integration Guide</h4>
                        <ol className="text-sm text-purple-700 space-y-2 list-decimal list-inside">
                            <li>In WooCommerce, go to <strong>Settings → Advanced → REST API</strong> and create a key with <strong>Read</strong> access — paste the Consumer Key and Consumer Secret above.</li>
                            <li>In WooCommerce, go to <strong>Settings → Advanced → Webhooks → Add webhook</strong>.</li>
                            <li>Set the <strong>Delivery URL</strong> to: <code className="bg-purple-100 px-1 rounded text-xs">{window.location.origin}/api/v1/webhooks/woocommerce</code></li>
                            <li>Set <strong>Topic</strong> to <code className="bg-purple-100 px-1 rounded text-xs">Product updated</code> (also add <code className="bg-purple-100 px-1 rounded text-xs">Product created</code> and <code className="bg-purple-100 px-1 rounded text-xs">Order updated</code> to keep orders in sync).</li>
                            <li>If you entered a Webhook Secret above, paste the same value into the webhook's <strong>Secret</strong> field.</li>
                            <li>Save the configuration above, then click <strong>Sync Products</strong> to import your catalogue.</li>
                        </ol>
                    </div>
                )}
            </div>
        </TenantLayout>
    );
}