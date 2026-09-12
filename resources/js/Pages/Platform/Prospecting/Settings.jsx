import { useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function ProspectingSettings({ settings, webhookUrl, webhookSecret, deliveryWebhookUrl }) {
    const { data, setData, put, processing } = useForm({
        telegram_bot_token: '',
        telegram_chat_id: settings.telegram_chat_id || '',
        alert_email: settings.alert_email || '',
        sender_name: settings.sender_name || '',
        sender_email: settings.sender_email || '',
        signature: settings.signature || '',
        deepseek_model: settings.deepseek_model || '',
        search_provider: settings.search_provider || 'none',
        search_api_key: '',
        daily_hunt_limit: settings.daily_hunt_limit || 50,
        daily_outreach_limit: settings.daily_outreach_limit || 50,
        qualification_threshold: settings.qualification_threshold || 7,
        auto_hunt: !!settings.auto_hunt,
        auto_outreach: !!settings.auto_outreach,
        postal_address: settings.postal_address || '',
        support_email: settings.support_email || '',
        default_legal_basis: settings.default_legal_basis || 'legitimate_interest',
        max_emails_per_hour: settings.max_emails_per_hour || 50,
        require_approval_ai_contacts: !!settings.require_approval_ai_contacts,
        regenerate_webhook_secret: false,
    });

    const submit = (e) => {
        e.preventDefault();
        put('/platform/prospecting/settings');
    };

    const field = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none';
    const label = 'block text-sm font-medium text-gray-700 mb-1';

    return (
        <PlatformLayout title="Prospecting Settings">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">🛠️ Prospecting Settings</h1>

            <form onSubmit={submit} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 max-w-3xl">
                <h3 className="font-semibold text-gray-900 mb-4">Alert Channels</h3>
                <div className="grid grid-cols-2 gap-4 mb-6">
                    <div><label className={label}>Telegram Bot Token</label><input className={field} type="password" placeholder={settings.has_telegram_token ? 'Configured — leave blank to keep' : 'Paste bot token'} value={data.telegram_bot_token} onChange={(e) => setData('telegram_bot_token', e.target.value)} /></div>
                    <div><label className={label}>Telegram Chat ID</label><input className={field} placeholder="e.g. 123456789" value={data.telegram_chat_id} onChange={(e) => setData('telegram_chat_id', e.target.value)} /></div>
                    <div className="col-span-2"><label className={label}>Alert Email</label><input className={field} type="email" value={data.alert_email} onChange={(e) => setData('alert_email', e.target.value)} /><p className="text-xs text-gray-400 mt-1">Replies are also emailed here the moment they arrive.</p></div>
                </div>

                <h3 className="font-semibold text-gray-900 mb-4">Sender Identity</h3>
                <div className="grid grid-cols-2 gap-4 mb-6">
                    <div><label className={label}>Sender Name</label><input className={field} value={data.sender_name} onChange={(e) => setData('sender_name', e.target.value)} /></div>
                    <div><label className={label}>Sender Email</label><input className={field} type="email" value={data.sender_email} onChange={(e) => setData('sender_email', e.target.value)} /></div>
                    <div className="col-span-2"><label className={label}>Email Signature</label><textarea className={field} rows={2} value={data.signature} onChange={(e) => setData('signature', e.target.value)} /></div>
                </div>

                <h3 className="font-semibold text-gray-900 mb-4">Hunting & AI</h3>
                <div className="grid grid-cols-2 gap-4 mb-6">
                    <div><label className={label}>DeepSeek Model</label><input className={field} placeholder="blank = platform default" value={data.deepseek_model} onChange={(e) => setData('deepseek_model', e.target.value)} /></div>
                    <div><label className={label}>Web Search Provider</label>
                        <select className={field} value={data.search_provider} onChange={(e) => setData('search_provider', e.target.value)}>
                            <option value="none">None (AI-generated only)</option>
                            <option value="serper">Serper.dev (Google)</option>
                            <option value="brave">Brave Search</option>
                        </select>
                    </div>
                    <div className="col-span-2"><label className={label}>Search API Key</label><input className={field} type="password" placeholder={settings.has_search_key ? 'Configured — leave blank to keep' : 'Serper.dev or Brave API key'} value={data.search_api_key} onChange={(e) => setData('search_api_key', e.target.value)} /><p className="text-xs text-gray-400 mt-1">Shared platform search. Tenants can use this or connect their own key.</p></div>
                </div>

                <h3 className="font-semibold text-gray-900 mb-4">Automation & Limits</h3>
                <div className="grid grid-cols-3 gap-4 mb-6">
                    <div><label className={label}>Daily Hunt Limit</label><input type="number" min="1" className={field} value={data.daily_hunt_limit} onChange={(e) => setData('daily_hunt_limit', e.target.value)} /></div>
                    <div><label className={label}>Daily Outreach Limit</label><input type="number" min="1" className={field} value={data.daily_outreach_limit} onChange={(e) => setData('daily_outreach_limit', e.target.value)} /></div>
                    <div><label className={label}>Qualify Threshold (1-10)</label><input type="number" min="1" max="10" className={field} value={data.qualification_threshold} onChange={(e) => setData('qualification_threshold', e.target.value)} /></div>
                </div>
                <div className="flex gap-6 mb-6">
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.auto_hunt} onChange={(e) => setData('auto_hunt', e.target.checked)} /> Auto-hunt daily</label>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.auto_outreach} onChange={(e) => setData('auto_outreach', e.target.checked)} /> Auto-outreach qualified</label>
                </div>

                <h3 className="font-semibold text-gray-900 mb-4">Compliance (CAN-SPAM / UK PECR)</h3>
                <div className="grid grid-cols-2 gap-4 mb-6">
                    <div className="col-span-2"><label className={label}>Physical Postal Address (required for sending)</label><textarea className={field} rows={2} value={data.postal_address} onChange={(e) => setData('postal_address', e.target.value)} /></div>
                    <div><label className={label}>Support Email</label><input className={field} type="email" value={data.support_email} onChange={(e) => setData('support_email', e.target.value)} /></div>
                    <div><label className={label}>Default Legal Basis</label>
                        <select className={field} value={data.default_legal_basis} onChange={(e) => setData('default_legal_basis', e.target.value)}>
                            <option value="legitimate_interest">Legitimate Interest</option>
                            <option value="consent">Consent</option>
                        </select>
                    </div>
                    <div><label className={label}>Max Emails / Hour</label><input type="number" min="1" className={field} value={data.max_emails_per_hour} onChange={(e) => setData('max_emails_per_hour', e.target.value)} /></div>
                    <div className="flex items-end pb-1"><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.require_approval_ai_contacts} onChange={(e) => setData('require_approval_ai_contacts', e.target.checked)} /> Require human approval for AI-generated contacts</label></div>
                </div>

                <h3 className="font-semibold text-gray-900 mb-4">Reply Webhook</h3>
                <div className="bg-gray-50 rounded-lg p-4 mb-4">
                    <p className="text-xs text-gray-500 mb-1">Point your email provider's inbound webhook at this URL (POST, JSON):</p>
                    <code className="block text-xs break-all bg-white border border-gray-200 rounded p-2 mb-2">{webhookUrl}</code>
                    <p className="text-xs text-gray-500 mb-1">Secret (already included in the URL above):</p>
                    <code className="block text-xs break-all bg-white border border-gray-200 rounded p-2">{webhookSecret}</code>
                    <p className="text-xs text-gray-500 mb-1 mt-3">Bounce / complaint webhook (for auto-suppression):</p>
                    <code className="block text-xs break-all bg-white border border-gray-200 rounded p-2">{deliveryWebhookUrl}</code>
                </div>
                <label className="flex items-center gap-2 text-sm mb-4"><input type="checkbox" checked={data.regenerate_webhook_secret} onChange={(e) => setData('regenerate_webhook_secret', e.target.checked)} /> Regenerate webhook secret on save</label>

                <button type="submit" disabled={processing} className="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Save Settings</button>
            </form>
        </PlatformLayout>
    );
}
