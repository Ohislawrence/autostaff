import { Head, usePage, router } from '@inertiajs/react';
import { useState } from 'react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Installations({ installations = [] }) {
    const { flash } = usePage().props;
    const [copied, setCopied] = useState(false);
    const [copiedSecret, setCopiedSecret] = useState(false);

    const copyKey = () => {
        if (!flash?.api_key) return;
        navigator.clipboard?.writeText(flash.api_key);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const copySecret = () => {
        if (!flash?.signing_secret) return;
        navigator.clipboard?.writeText(flash.signing_secret);
        setCopiedSecret(true);
        setTimeout(() => setCopiedSecret(false), 2000);
    };

    return (
        <TenantLayout header="Plugin Installations">
            <Head title="Plugin Installations" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}
            {(flash?.api_key || flash?.signing_secret) && (
                <div className="mb-6 p-5 bg-amber-50 border border-amber-300 rounded-xl">
                    <h2 className="font-semibold text-amber-900 mb-2">⚠️ Copy your credentials now</h2>
                    <p className="text-xs text-amber-800 mb-3">These will not be shown again. Paste them into your plugin settings.</p>
                    {flash?.api_key && (
                        <div className="mb-3">
                            <div className="text-xs font-semibold text-amber-900 uppercase tracking-wide mb-1">API Key</div>
                            <div className="flex items-center gap-2">
                                <code className="flex-1 px-3 py-2 bg-white border border-amber-200 rounded-lg text-sm font-mono break-all">{flash.api_key}</code>
                                <button onClick={copyKey} className="px-3 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 shrink-0">
                                    {copied ? 'Copied!' : 'Copy'}
                                </button>
                            </div>
                        </div>
                    )}
                    {flash?.signing_secret && (
                        <div>
                            <div className="text-xs font-semibold text-amber-900 uppercase tracking-wide mb-1">Signing Secret</div>
                            <div className="flex items-center gap-2">
                                <code className="flex-1 px-3 py-2 bg-white border border-amber-200 rounded-lg text-sm font-mono break-all">{flash.signing_secret}</code>
                                <button onClick={copySecret} className="px-3 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 shrink-0">
                                    {copiedSecret ? 'Copied!' : 'Copy'}
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            )}

            <p className="text-sm text-gray-500 mb-6">Manage the plugins you've installed on your own apps and sites.</p>

            {installations.length === 0 ? (
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center text-sm text-gray-500">
                    You haven't installed any plugins yet. Browse the <a href="/plugins" className="text-primary-600">plugin directory</a>.
                </div>
            ) : (
                <div className="space-y-4">
                    {installations.map((i) => (
                        <div key={i.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-xl">{i.plugin?.icon || '🧩'}</span>
                                        <h3 className="font-semibold text-gray-900">{i.plugin?.name || 'Unknown plugin'}</h3>
                                        <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${i.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                            {i.status}
                                        </span>
                                    </div>
                                    <div className="flex flex-wrap gap-3 text-xs text-gray-400 mt-2">
                                        <span>{i.site_url}</span>
                                        {i.site_label && <span>{i.site_label}</span>}
                                        {i.version && <span>v{i.version}</span>}
                                    </div>
                                    {i.api_key && (
                                        <div className="mt-2">
                                            <code className="text-xs text-gray-500">{i.api_key.key_prefix}••••••</code>
                                            <div className="flex flex-wrap gap-1 mt-1">
                                                {(i.api_key.scopes || []).map((s) => (
                                                    <span key={s} className="px-1.5 py-0.5 bg-gray-200 text-gray-600 rounded text-[10px] font-mono">{s}</span>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                    <p className="text-[10px] text-gray-400 mt-1">
                                        Installed {i.created_at}
                                        {i.last_seen_at ? ` · Last seen ${i.last_seen_at}` : ''}
                                    </p>
                                </div>
                                {i.status === 'active' && (
                                    <div className="flex items-center gap-2 shrink-0">
                                        <button onClick={() => router.post(`/installations/${i.id}/regenerate`)} className="px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-100">
                                            Regenerate key
                                        </button>
                                        <button onClick={() => router.post(`/installations/${i.id}/revoke`)} className="px-3 py-1.5 border border-red-300 text-red-700 rounded-lg text-xs font-medium hover:bg-red-100">
                                            Revoke
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </TenantLayout>
    );
}
