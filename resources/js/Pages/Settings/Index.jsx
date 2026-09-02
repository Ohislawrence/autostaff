import { Head, usePage, useForm } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index({ supportedCurrencies }) {
    const { auth, flash } = usePage().props;
    const { data, setData, put, processing } = useForm({
        currency: auth.organization?.currency || 'NGN',
    });

    const currencySymbol = (code) => {
        const map = {
            USD: '$', NGN: '₦', GHS: 'GH₵', KES: 'KSh', ZAR: 'R',
            EUR: '€', GBP: '£', CAD: 'CA$', AUD: 'A$', INR: '₹', JPY: '¥',
        };
        return map[code] || (code + ' ');
    };

    const handleCurrency = (e) => {
        e.preventDefault();
        put('/settings', { onSuccess: () => {} });
    };

    return (
        <TenantLayout header="Settings">
            <Head title="Settings" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}

            <div className="max-w-3xl space-y-6">
                {/* Business Profile */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 className="font-semibold text-gray-900 mb-4">Business Profile</h2>
                    <div className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">Business Name</label>
                            <input type="text" defaultValue={auth.organization?.name} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50" readOnly />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">Email</label>
                            <input type="text" defaultValue={auth.organization?.email || '—'} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50" readOnly />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">Industry</label>
                            <input type="text" defaultValue={auth.organization?.industry || '—'} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50" readOnly />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">Country / Timezone</label>
                            <input type="text" defaultValue={`${auth.organization?.country || '—'} / ${auth.organization?.timezone || '—'}`} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50" readOnly />
                        </div>
                    </div>
                </div>

                {/* Currency */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 className="font-semibold text-gray-900 mb-4">Currency</h2>
                    <p className="text-sm text-gray-500 mb-4">
                        This currency is used for orders, quotations, invoices, and reports across your AI employee.
                    </p>
                    <form onSubmit={handleCurrency} className="flex items-end gap-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">Display Currency</label>
                            <select
                                value={data.currency}
                                onChange={(e) => setData('currency', e.target.value)}
                                className="px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            >
                                {(supportedCurrencies || ['USD', 'NGN', 'GHS', 'KES', 'ZAR', 'EUR', 'GBP', 'CAD', 'AUD', 'INR', 'JPY']).map(c => (
                                    <option key={c} value={c}>{currencySymbol(c)} {c}</option>
                                ))}
                            </select>
                        </div>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 disabled:opacity-50"
                        >
                            {processing ? 'Saving...' : 'Save Currency'}
                        </button>
                    </form>
                </div>

                {/* Notifications */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 className="font-semibold text-gray-900 mb-4">Notifications</h2>
                    <div className="space-y-3">
                        <label className="flex items-center gap-3">
                            <input type="checkbox" defaultChecked className="rounded" />
                            <div><span className="text-sm font-medium text-gray-900">New Lead Notifications</span><p className="text-xs text-gray-500">Get notified when a new lead is created.</p></div>
                        </label>
                        <label className="flex items-center gap-3">
                            <input type="checkbox" defaultChecked className="rounded" />
                            <div><span className="text-sm font-medium text-gray-900">Escalation Alerts</span><p className="text-xs text-gray-500">Get notified when AI escalates to human.</p></div>
                        </label>
                        <label className="flex items-center gap-3">
                            <input type="checkbox" defaultChecked className="rounded" />
                            <div><span className="text-sm font-medium text-gray-900">New Order Notifications</span><p className="text-xs text-gray-500">Get notified when a new order is placed.</p></div>
                        </label>
                    </div>
                </div>

                {/* Danger Zone */}
                <div className="bg-white rounded-xl shadow-sm border border-red-200 p-6">
                    <h2 className="font-semibold text-red-700 mb-4">⚠️ Danger Zone</h2>
                    <p className="text-sm text-gray-600 mb-4">Irreversible actions. Please proceed with caution.</p>

                    <div className="space-y-4">
                        <div className="flex items-center justify-between p-4 bg-red-50 rounded-lg">
                            <div>
                                <p className="font-medium text-gray-900 text-sm">Deactivate Business</p>
                                <p className="text-xs text-gray-500">Temporarily disable your AI Employees and stop all operations.</p>
                            </div>
                            <button className="px-4 py-2 border border-red-300 text-red-700 rounded-lg text-sm font-medium hover:bg-red-100" disabled>Coming Soon</button>
                        </div>

                        <div className="flex items-center justify-between p-4 bg-red-50 rounded-lg">
                            <div>
                                <p className="font-medium text-gray-900 text-sm">Export All Data</p>
                                <p className="text-xs text-gray-500">Download all your business data including customers, conversations, and orders.</p>
                            </div>
                            <button className="px-4 py-2 border border-red-300 text-red-700 rounded-lg text-sm font-medium hover:bg-red-100" disabled>Coming Soon</button>
                        </div>

                        <div className="flex items-center justify-between p-4 bg-red-50 rounded-lg border border-red-300">
                            <div>
                                <p className="font-medium text-gray-900 text-sm">Delete Organization</p>
                                <p className="text-xs text-gray-500">Permanently delete all data. This action cannot be undone.</p>
                            </div>
                            <button
                                onClick={() => alert('For security, organization deletion requires contacting support.')}
                                className="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700"
                            >
                                Delete Organization
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </TenantLayout>
    );
}