import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

const oauthProviders = [
    { key: 'google_calendar', label: 'Google Calendar', icon: '📅' },
    { key: 'gmail', label: 'Gmail', icon: '✉️' },
];

const manualProviders = [
    { key: 'crm', label: 'CRM' },
    { key: 'microsoft_365', label: 'Microsoft 365' },
    { key: 'custom', label: 'Custom MCP Server' },
];

export default function Mcp({ connections = [], recentCalls = [] }) {
    const { flash } = usePage().props;

    const form = useForm({
        provider: 'custom',
        endpoint: '',
        credentials: { access_token: '', refresh_token: '' },
        pricing: { per_call: '' },
    });

    const submit = (e) => {
        e.preventDefault();
        form.post('/integrations/mcp');
    };

    return (
        <TenantLayout header="MCP Connections">
            <Head title="MCP Connections" />

            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}
            {flash?.error && <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-lg text-sm">{flash.error}</div>}

            <p className="text-sm text-gray-500 mb-6">
                Connect external MCP servers so your AI employees can use their tools.
            </p>

            <div className="grid grid-cols-2 gap-4 mb-8">
                {oauthProviders.map((p) => (
                    <Link
                        key={p.key}
                        href={`/integrations/mcp/oauth/${p.key}/redirect`}
                        className="flex items-center gap-3 bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow"
                    >
                        <span className="text-2xl">{p.icon}</span>
                        <div>
                            <h3 className="font-semibold text-gray-900">Connect {p.label}</h3>
                            <p className="text-xs text-gray-500">Sign in with Google to connect {p.label}.</p>
                        </div>
                    </Link>
                ))}
            </div>

            <form onSubmit={submit} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                <h3 className="font-semibold text-gray-900 mb-4">Connect manually</h3>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">Provider</label>
                        <select value={form.data.provider} onChange={(e) => form.setData('provider', e.target.value)} className="w-full px-3 py-2 border rounded-lg text-sm">
                            {manualProviders.map((p) => <option key={p.key} value={p.key}>{p.label}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">Server URL</label>
                        <input value={form.data.endpoint} onChange={(e) => form.setData('endpoint', e.target.value)} className="w-full px-3 py-2 border rounded-lg text-sm" placeholder="https://mcp.example.com" />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">Access Token</label>
                        <input value={form.data.credentials.access_token} onChange={(e) => form.setData('credentials.access_token', e.target.value)} className="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">Refresh Token</label>
                        <input value={form.data.credentials.refresh_token} onChange={(e) => form.setData('credentials.refresh_token', e.target.value)} className="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">Cost per call (USD)</label>
                        <input value={form.data.pricing.per_call} onChange={(e) => form.setData('pricing.per_call', e.target.value)} className="w-full px-3 py-2 border rounded-lg text-sm" placeholder="0.001" />
                    </div>
                </div>
                <button type="submit" disabled={form.processing} className="mt-4 px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium disabled:opacity-50">
                    Connect
                </button>
            </form>

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Provider</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Tools</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Expires</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {connections.length === 0 && (
                            <tr><td colSpan={5} className="px-4 py-8 text-center text-gray-400">No MCP connections yet.</td></tr>
                        )}
                        {connections.map((c) => (
                            <tr key={c.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3 font-medium text-gray-900 capitalize">{c.name}</td>
                                <td className="px-4 py-3">
                                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${c.is_connected ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}`}>{c.status}</span>
                                </td>
                                <td className="px-4 py-3 text-gray-500">{c.tool_count}</td>
                                <td className="px-4 py-3 text-xs text-gray-500">
                                    {c.expiring_soon ? <span className="text-amber-600">⚠️ {c.expires_at ? new Date(c.expires_at).toLocaleDateString() : 'Expiring soon'}</span> : (c.expires_at ? new Date(c.expires_at).toLocaleDateString() : '—')}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex gap-2">
                                        <button onClick={() => router.post(`/integrations/mcp/${c.id}/sync`)} className="text-xs text-primary-600 hover:text-primary-700">Sync</button>
                                        <button onClick={() => router.post(`/integrations/mcp/${c.id}/health`)} className="text-xs text-primary-600 hover:text-primary-700">Health</button>
                                        <button onClick={() => { if (confirm('Disconnect this provider?')) router.delete(`/integrations/mcp/${c.id}`); }} className="text-xs text-red-600 hover:text-red-700">Disconnect</button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <h3 className="font-semibold text-gray-900 mb-3">Recent MCP calls</h3>
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Tool</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Latency</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Cost</th>
                            <th className="text-left px-4 py-3 font-medium text-gray-500">When</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {recentCalls.length === 0 && <tr><td colSpan={5} className="px-4 py-8 text-center text-gray-400">No calls yet.</td></tr>}
                        {recentCalls.map((r) => (
                            <tr key={r.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3 text-gray-900">{r.tool}</td>
                                <td className="px-4 py-3"><span className={`px-2 py-0.5 rounded-full text-xs font-medium ${r.status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>{r.status}</span></td>
                                <td className="px-4 py-3 text-gray-500">{r.execution_time_ms}ms</td>
                                <td className="px-4 py-3 text-gray-500">${r.estimated_cost}</td>
                                <td className="px-4 py-3 text-gray-400 text-xs">{r.created_at ? new Date(r.created_at).toLocaleString() : ''}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </TenantLayout>
    );
}

