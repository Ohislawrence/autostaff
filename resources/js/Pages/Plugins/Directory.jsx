import { Head, usePage, useForm } from '@inertiajs/react';
import { useState } from 'react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Directory({ plugins = [] }) {
    const { flash } = usePage().props;
    const [installing, setInstalling] = useState(null);
    const [copied, setCopied] = useState(false);
    const [copiedSecret, setCopiedSecret] = useState(false);
    const installForm = useForm({ site_url: '', site_label: '' });

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

    const submitInstall = (e) => {
        e.preventDefault();
        installForm.post(`/plugins/${installing}/install`, {
            onSuccess: () => { installForm.reset(); setInstalling(null); },
        });
    };

    return (
        <TenantLayout header="Plugin Directory">
            <Head title="Plugin Directory" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}
            {(flash?.api_key || flash?.signing_secret) && (
                <div className="mb-6 p-5 bg-amber-50 border border-amber-300 rounded-xl">
                    <h2 className="font-semibold text-amber-900 mb-2">⚠️ Copy your credentials now</h2>
                    <p className="text-xs text-amber-800 mb-3">These will not be shown again. Paste them into your plugin settings.</p>
                    {flash?.api_key && (
                        <div className="flex items-center gap-2 mb-2">
                            <code className="flex-1 px-3 py-2 bg-white border border-amber-200 rounded-lg text-sm font-mono break-all">{flash.api_key}</code>
                            <button onClick={copyKey} className="px-3 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 shrink-0">
                                {copied ? 'Copied!' : 'Copy'}
                            </button>
                        </div>
                    )}
                    {flash?.signing_secret && (
                        <div className="flex items-center gap-2">
                            <code className="flex-1 px-3 py-2 bg-white border border-amber-200 rounded-lg text-sm font-mono break-all">{flash.signing_secret}</code>
                            <button onClick={copySecret} className="px-3 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 shrink-0">
                                {copiedSecret ? 'Copied!' : 'Copy'}
                            </button>
                        </div>
                    )}
                </div>
            )}
            <p className="text-sm text-gray-500 mb-6">
                Browse and download plugins to connect your apps (e.g. WordPress) to your AI Employee.
            </p>

            {plugins.length === 0 ? (
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center text-sm text-gray-500">
                    No plugins are currently available.
                </div>
            ) : (
                <div className="grid grid-cols-2 gap-4">
                    {plugins.map((p) => (
                        <div key={p.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
                            <div className="flex items-start gap-3">
                                <span className="text-2xl">{p.icon || '🧩'}</span>
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center justify-between gap-2">
                                        <h3 className="font-semibold text-gray-900">{p.name}</h3>
                                        {p.target_platform && (
                                            <span className="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs font-medium shrink-0">
                                                {p.target_platform}
                                            </span>
                                        )}
                                    </div>
                                    <p className="text-xs text-gray-500 mt-1">{p.short_description}</p>
                                    <div className="flex items-center gap-3 text-xs text-gray-400 mt-2">
                                        {p.category && <span>{p.category}</span>}
                                        {p.author && <span>by {p.author}</span>}
                                        <span>{p.downloads_count} downloads</span>
                                    </div>

                                    {p.current_version ? (
                                        <div className="mt-3 p-3 bg-gray-50 rounded-lg">
                                            <div className="flex items-center justify-between">
                                                <span className="text-xs font-mono text-gray-700">v{p.current_version.version}</span>
                                                <span className="text-xs text-gray-400">{(p.current_version.file_size / 1024).toFixed(1)} KB</span>
                                            </div>
                                            {p.current_version.changelog && (
                                                <p className="text-xs text-gray-500 mt-1">{p.current_version.changelog}</p>
                                            )}
                                            <div className="flex gap-2 mt-2">
                                                <a
                                                    href={`/plugins/${p.id}/versions/${p.current_version.id}/download`}
                                                    className="inline-block px-3 py-1.5 bg-primary-600 text-white rounded-lg text-xs font-medium hover:bg-primary-700"
                                                >
                                                    Download .zip
                                                </a>
                                                <button
                                                    onClick={() => setInstalling(installing === p.id ? null : p.id)}
                                                    className="px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-100"
                                                >
                                                    Install
                                                </button>
                                            </div>
                                        </div>
                                    ) : (
                                        <p className="text-xs text-amber-600 mt-3">No published version yet.</p>
                                    )}

                                    {installing === p.id && (
                                        <form onSubmit={submitInstall} className="mt-3 p-3 border border-gray-200 rounded-lg bg-gray-50">
                                            <p className="text-xs font-medium text-gray-700 mb-2">Install on your site</p>
                                            <input
                                                value={installForm.data.site_label}
                                                onChange={(e) => installForm.setData('site_label', e.target.value)}
                                                placeholder="Label (optional, e.g. Main store)"
                                                className="w-full px-3 py-2 border border-gray-300 rounded text-sm mb-2"
                                            />
                                            <input
                                                value={installForm.data.site_url}
                                                onChange={(e) => installForm.setData('site_url', e.target.value)}
                                                placeholder="https://your-site.com"
                                                type="url"
                                                className="w-full px-3 py-2 border border-gray-300 rounded text-sm mb-2"
                                                required
                                            />
                                            <div className="flex gap-2">
                                                <button type="submit" disabled={installForm.processing} className="px-3 py-1.5 bg-primary-600 text-white rounded text-xs">
                                                    Install & generate key
                                                </button>
                                                <button type="button" onClick={() => setInstalling(null)} className="px-3 py-1.5 border rounded text-xs">
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </TenantLayout>
    );
}
