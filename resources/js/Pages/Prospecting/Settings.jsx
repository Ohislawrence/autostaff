import { Head, Link, useForm, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function SearchSettings({ settings }) {
    const { flash } = usePage().props;
    const { data, setData, put, processing, errors } = useForm({
        provider: settings.provider || 'none',
        api_key: '',
    });

    const submit = (e) => {
        e.preventDefault();
        put('/prospecting/settings');
    };

    const field = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none';
    const label = 'block text-sm font-medium text-gray-700 mb-1';

    const usingOwnKey = data.provider === 'serper' || data.provider === 'brave';
    const platformLabel = settings.platform_provider === 'brave' ? 'Brave Search' : 'Google (Serper.dev)';

    return (
        <TenantLayout header="Search Settings">
            <Head title="Search Settings" />

            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-xl text-sm">{flash.success}</div>}
            {flash?.error && <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-xl text-sm">{flash.error}</div>}

            <div className="max-w-2xl">
                <div className="flex items-center justify-between mb-2">
                    <h1 className="text-2xl font-bold text-gray-900">🔎 Web search</h1>
                    <Link href="/prospecting" className="text-sm text-blue-600 hover:underline">← Back to prospecting</Link>
                </div>
                <p className="text-sm text-gray-500 mb-6">
                    Nomdal uses web search to find real prospects for your campaigns. Use the platform's shared search, bring your own key — or turn it off to use AI suggestions instead.
                </p>

                {settings.using_platform && (
                    <div className="mb-6 p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl text-sm">
                        ✓ Using Nomdal's shared search ({platformLabel}). Bring your own key below if you'd like faster or higher-volume searches.
                    </div>
                )}

                {settings.has_key && (
                    <div className="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">
                        ✓ Your search is connected ({settings.provider}). Your campaigns will find live prospects.
                    </div>
                )}

                {!settings.has_key && !settings.using_platform && (
                    <div className="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-sm">
                        ⚠️ Web search isn't connected yet. Nomdal will use AI-generated suggestions until you connect a search provider.
                    </div>
                )}

                <form onSubmit={submit} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
                    <div>
                        <label className={label}>Search provider</label>
                        <select className={field} value={data.provider} onChange={(e) => setData('provider', e.target.value)}>
                            <option value="platform">
                                {settings.platform_available
                                    ? `Nomdal shared search (${platformLabel})`
                                    : 'Nomdal shared search (not configured)'}
                            </option>
                            <option value="serper">Serper.dev — own key (Google results)</option>
                            <option value="brave">Brave Search — own key</option>
                            <option value="none">None (AI suggestions only)</option>
                        </select>
                        {data.provider === 'platform' && !settings.platform_available && (
                            <p className="mt-2 text-xs text-amber-600">
                                The platform shared search isn't configured yet. Choose another option, or contact your provider.
                            </p>
                        )}
                    </div>

                    {usingOwnKey && (
                        <div>
                            <label className={label}>API key</label>
                            <input
                                type="password"
                                className={field}
                                placeholder={settings.has_key ? 'Configured — leave blank to keep it' : 'Paste your API key'}
                                value={data.api_key}
                                onChange={(e) => setData('api_key', e.target.value)}
                                autoComplete="off"
                            />
                            {errors?.api_key && <p className="mt-1 text-xs text-red-500">{errors.api_key}</p>}
                            <p className="mt-2 text-xs text-gray-400">
                                Your key is encrypted and used only for your own campaigns. {data.provider === 'serper' ? 'Get a key at serper.dev' : 'Get a key at brave.com/search/api'}.
                            </p>
                        </div>
                    )}

                    <div className="flex items-center gap-3 pt-2">
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50">
                            {data.provider === 'none' ? 'Disable search' : data.provider === 'platform' ? 'Use shared search' : 'Save'}
                        </button>
                    </div>
                </form>
            </div>
        </TenantLayout>
    );
}
