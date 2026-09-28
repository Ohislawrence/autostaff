import { Head, usePage, useForm, Link } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';
import { useState } from 'react';

export default function Setup({ employee, channels, widget_settings }) {
    const { flash } = usePage().props;
    const { post } = useForm();
    const [copied, setCopied] = useState(null);

    const themeForm = useForm({
        primary_color: widget_settings?.primary_color || '#4F46E5',
        greeting: widget_settings?.greeting || '👋 Hi! How can we help you today?',
        position: widget_settings?.position || 'bottom-right',
    });

    const saveTheme = (e) => {
        e.preventDefault();
        themeForm.post(`/ai-employees/${employee.id}/channels/widget`);
    };

    const toggle = (channelId) => {
        post(`/ai-employees/${employee.id}/channels/toggle`, {
            data: { channel: channelId },
        });
    };

    const copySnippet = (snippet) => {
        navigator.clipboard.writeText(snippet);
        setCopied('snippet');
        setTimeout(() => setCopied(null), 2000);
    };

    return (
        <TenantLayout header={`Channels — ${employee.name}`}>
            <Head title={`Channels — ${employee.name}`} />

            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}

            <p className="text-sm text-gray-500 mb-6">
                Connect communication channels so customers can reach {employee.name}. Each channel can be enabled or disabled independently.
            </p>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {channels.map(channel => (
                    <div key={channel.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div className="flex items-start justify-between mb-4">
                            <div className="flex items-center gap-3">
                                <span className="text-2xl">{channel.icon}</span>
                                <div>
                                    <h3 className="font-semibold text-gray-900">{channel.name}</h3>
                                    <p className="text-xs text-gray-500">{channel.description}</p>
                                    {channel.status === 'coming_soon' && (
                                        <span className="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium mt-1 inline-block">Coming Soon</span>
                                    )}
                                </div>
                            </div>
                            {channel.status !== 'coming_soon' && (
                                <button
                                    onClick={() => toggle(channel.id)}
                                    className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${channel.connected ? 'bg-green-500' : 'bg-gray-300'}`}
                                >
                                    <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${channel.connected ? 'translate-x-6' : 'translate-x-1'}`} />
                                </button>
                            )}
                        </div>

                        {/* Web Chat snippet */}
                        {channel.id === 'web_chat' && channel.connected && channel.snippet && (
                            <div className="mt-4 p-3 bg-gray-900 rounded-lg">
                                <div className="flex items-center justify-between mb-2">
                                    <span className="text-xs text-gray-400">Embed Code</span>
                                    <button onClick={() => copySnippet(channel.snippet)}
                                        className="text-xs text-primary-400 hover:text-primary-300">
                                        {copied === 'snippet' ? '✓ Copied!' : 'Copy'}
                                    </button>
                                </div>
                                <code className="text-xs text-green-300 break-all font-mono">{channel.snippet}</code>
                            </div>
                        )}

                        {/* Widget theme editor */}
                        {channel.id === 'web_chat' && channel.connected && (
                            <form onSubmit={saveTheme} className="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200 space-y-4">
                                <h4 className="text-sm font-semibold text-gray-800">🎨 Widget Theme</h4>

                                <div>
                                    <label className="block text-xs font-medium text-gray-600 mb-1">Primary Colour</label>
                                    <div className="flex items-center gap-3">
                                        <input
                                            type="color"
                                            value={themeForm.data.primary_color}
                                            onChange={(e) => themeForm.setData('primary_color', e.target.value)}
                                            className="h-9 w-12 rounded border border-gray-300 cursor-pointer"
                                        />
                                        <input
                                            type="text"
                                            value={themeForm.data.primary_color}
                                            onChange={(e) => themeForm.setData('primary_color', e.target.value)}
                                            placeholder="#4F46E5"
                                            className="w-28 px-2 py-1.5 border border-gray-300 rounded-lg text-xs font-mono"
                                        />
                                    </div>
                                    {themeForm.errors.primary_color && (
                                        <p className="mt-1 text-xs text-red-500">{themeForm.errors.primary_color}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-gray-600 mb-1">Greeting Message</label>
                                    <input
                                        type="text"
                                        value={themeForm.data.greeting}
                                        onChange={(e) => themeForm.setData('greeting', e.target.value)}
                                        className="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-gray-600 mb-1">Position</label>
                                    <select
                                        value={themeForm.data.position}
                                        onChange={(e) => themeForm.setData('position', e.target.value)}
                                        className="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs"
                                    >
                                        <option value="bottom-right">Bottom Right</option>
                                        <option value="bottom-left">Bottom Left</option>
                                    </select>
                                </div>

                                <button
                                    type="submit"
                                    disabled={themeForm.processing}
                                    className="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 disabled:opacity-50"
                                >
                                    {themeForm.processing ? 'Saving…' : 'Save Theme'}
                                </button>
                            </form>
                        )}

                        {/* WhatsApp config */}
                        {channel.id === 'whatsapp' && channel.connected && (
                            <div className="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-xs text-yellow-700">
                                <strong>Setup Required:</strong> Configure your WhatsApp Business API credentials in your environment variables.
                                Set <code className="bg-yellow-100 px-1 rounded">WHATSAPP_ACCESS_TOKEN</code>,{' '}
                                <code className="bg-yellow-100 px-1 rounded">WHATSAPP_PHONE_NUMBER_ID</code>, and{' '}
                                <code className="bg-yellow-100 px-1 rounded">WHATSAPP_VERIFY_TOKEN</code>.
                            </div>
                        )}

                        {/* Email config */}
                        {channel.id === 'email' && channel.connected && (
                            <div className="mt-4 space-y-3">
                                <div className="p-4 bg-blue-50 border border-blue-100 rounded-lg">
                                    <h4 className="text-sm font-semibold text-blue-800 mb-2">📬 Email Setup Instructions</h4>
                                    <ol className="list-decimal list-inside space-y-1.5 text-xs text-blue-700">
                                        <li>Go to <strong>Integrations → Email</strong> to configure your email provider (SMTP, Mailgun, SendGrid, etc.)</li>
                                        <li>Set your <code className="bg-blue-100 px-1 rounded text-xs">MAIL_FROM_ADDRESS</code> — the email your AI sends from</li>
                                        <li>For incoming messages, configure a webhook from your email provider to:<br />
                                            <code className="bg-blue-100 px-1 rounded text-xs font-mono">{window.location.origin}/api/v1/webhooks/email</code>
                                        </li>
                                        <li>Set a webhook secret in the email integration for security verification</li>
                                        <li>Once configured, customers emailing your business address will get AI-powered replies</li>
                                    </ol>
                                </div>
                                <Link
                                    href="/integrations/email"
                                    className="inline-flex items-center gap-1 px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Configure Email Integration
                                </Link>
                            </div>
                        )}
                        {channel.id === 'email' && !channel.connected && (
                            <div className="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-500">
                                Toggle ON to enable email for this AI Employee. Then configure your email provider in Integrations.
                            </div>
                        )}

                        {/* API info */}
                        {channel.id === 'api' && (
                            <div className="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                <p className="text-xs text-gray-600 mb-2">Send messages via the API:</p>
                                <code className="text-xs text-gray-700 font-mono">
                                    POST /chat/{employee.uuid}/message
                                </code>
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </TenantLayout>
    );
}