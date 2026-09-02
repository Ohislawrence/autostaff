import { Head, usePage, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function ApiKeys({ keys = [], availableScopes = [] }) {
    const { flash } = usePage().props;
    const [copied, setCopied] = useState(false);
    const { data, setData, post, processing, reset } = useForm({
        name: '',
        scopes: [],
    });

    const copyKey = () => {
        if (!flash?.api_key) return;
        navigator.clipboard?.writeText(flash.api_key);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const toggleScope = (scope) => {
        const next = data.scopes.includes(scope)
            ? data.scopes.filter((s) => s !== scope)
            : [...data.scopes, scope];
        setData('scopes', next);
    };

    const submit = (e) => {
        e.preventDefault();
        post('/settings/api-keys', { onSuccess: () => reset('name') });
    };

    return (
        <TenantLayout header="API Keys">
            <Head title="API Keys" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}
            {flash?.error && <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-lg text-sm">{flash.error}</div>}

            {flash?.api_key && (
                <div className="mb-6 p-5 bg-amber-50 border border-amber-300 rounded-xl">
                    <h2 className="font-semibold text-amber-900 mb-2">⚠️ Copy your API key now</h2>
                    <p className="text-xs text-amber-800 mb-3">
                        This key will not be shown again. Store it somewhere safe (e.g. in your plugin settings).
                    </p>
                    <div className="flex items-center gap-2">
                        <code className="flex-1 px-3 py-2 bg-white border border-amber-200 rounded-lg text-sm font-mono break-all">
                            {flash.api_key}
                        </code>
                        <button
                            onClick={copyKey}
                            className="px-3 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 shrink-0"
                        >
                            {copied ? 'Copied!' : 'Copy'}
                        </button>
                    </div>
                </div>
            )}

            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 className="font-semibold text-gray-900 mb-4">Create API Key</h2>
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-xs font-medium text-gray-500 mb-1">Name</label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="e.g. WooCommerce plugin, Website form"
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            required
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-500 mb-1">Scopes (permissions)</label>
                        <div className="grid grid-cols-2 gap-2">
                            {availableScopes.map((scope) => (
                                <label key={scope} className="flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="checkbox"
                                        checked={data.scopes.includes(scope)}
                                        onChange={() => toggleScope(scope)}
                                        className="rounded"
                                    />
                                    {scope}
                                </label>
                            ))}
                        </div>
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 disabled:opacity-50"
                    >
                        {processing ? 'Creating…' : 'Create key'}
                    </button>
                </form>
            </div>
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 className="font-semibold text-gray-900 mb-4">Your API Keys</h2>
                {keys.length === 0 ? (
                    <p className="text-sm text-gray-500">No API keys yet. Create one to connect your apps and plugins.</p>
                ) : (
                    <div className="space-y-3">
                        {keys.map((key) => (
                            <div key={key.id} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-100">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <p className="font-medium text-gray-900 text-sm">{key.name}</p>
                                        <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${key.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                            {key.is_active ? 'Active' : 'Revoked'}
                                        </span>
                                    </div>
                                    <code className="text-xs text-gray-500">{key.key_prefix}••••••</code>
                                    <div className="flex flex-wrap gap-1 mt-1">
                                        {(key.scopes || []).map((scope) => (
                                            <span key={scope} className="px-1.5 py-0.5 bg-gray-200 text-gray-600 rounded text-[10px] font-mono">{scope}</span>
                                        ))}
                                    </div>
                                    <p className="text-[10px] text-gray-400 mt-1">
                                        Created {key.created_at}
                                        {key.last_used_at ? ` · Last used ${key.last_used_at}` : ''}
                                    </p>
                                </div>
                                {key.is_active && (
                                    <div className="flex items-center gap-2 shrink-0">
                                        <button
                                            onClick={() => router.post(`/settings/api-keys/${key.id}/regenerate`)}
                                            className="px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-100"
                                        >
                                            Regenerate
                                        </button>
                                        <button
                                            onClick={() => router.post(`/settings/api-keys/${key.id}/revoke`)}
                                            className="px-3 py-1.5 border border-red-300 text-red-700 rounded-lg text-xs font-medium hover:bg-red-100"
                                        >
                                            Revoke
                                        </button>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </TenantLayout>
    );
}
