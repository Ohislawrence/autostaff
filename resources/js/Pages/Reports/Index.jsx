import { Head, usePage, useForm } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index({ reports, report_alerts }) {
    const { auth, flash } = usePage().props;
    const currency = auth.organization?.currency || 'NGN';
    const symbol = { USD: '$', NGN: '₦', GHS: 'GH₵', KES: 'KSh', ZAR: 'R', EUR: '€', GBP: '£', CAD: 'CA$', AUD: 'A$', INR: '₹', JPY: '¥' }[currency] || (currency + ' ');

    const alertForm = useForm({
        enabled: report_alerts?.enabled ?? false,
        frequency: report_alerts?.frequency ?? 'weekly',
        email: report_alerts?.recipients?.[0] ?? auth.user?.email ?? '',
    });

    const saveAlerts = (e) => {
        e.preventDefault();
        alertForm.post('/reports/alerts');
    };

    return (
        <TenantLayout header="Reports">
            <Head title="Reports" />
            <p className="text-sm text-gray-500 mb-4">Automated business performance reports, generated on a schedule.</p>

            <form onSubmit={saveAlerts} className="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
                <h3 className="font-semibold text-gray-900 mb-3">📧 Email Summary Alerts</h3>
                {flash?.success && <p className="mb-3 text-sm text-green-600">{flash.success}</p>}

                <label className="flex items-center gap-2 mb-4">
                    <input
                        type="checkbox"
                        checked={alertForm.data.enabled}
                        onChange={(e) => alertForm.setData('enabled', e.target.checked)}
                        className="h-4 w-4 rounded border-gray-300"
                    />
                    <span className="text-sm text-gray-700">Email me a summary of my AI employee's work</span>
                </label>

                {alertForm.data.enabled && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Frequency</label>
                            <select
                                value={alertForm.data.frequency}
                                onChange={(e) => alertForm.setData('frequency', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            >
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Send to email</label>
                            <input
                                type="email"
                                value={alertForm.data.email}
                                onChange={(e) => alertForm.setData('email', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                placeholder="you@example.com"
                            />
                            {alertForm.errors.email && <p className="mt-1 text-xs text-red-500">{alertForm.errors.email}</p>}
                        </div>
                    </div>
                )}

                <button
                    type="submit"
                    disabled={alertForm.processing}
                    className="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 disabled:opacity-50"
                >
                    {alertForm.processing ? 'Saving…' : 'Save Alert Settings'}
                </button>
            </form>

            <div className="space-y-4">
                {reports.map(r => (
                    <div key={r.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <div className="flex items-center justify-between mb-3">
                            <h3 className="font-semibold text-gray-900 capitalize">{r.period} Report</h3>
                            <span className="text-xs text-gray-400">{r.period_start} → {r.period_end}</span>
                        </div>
                        <p className="text-sm text-gray-600 mb-3">{r.summary_text}</p>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <Metric label="Conversations" value={r.metrics?.conversations ?? 0} />
                            <Metric label="Leads" value={r.metrics?.leads ?? 0} />
                            <Metric label="Orders" value={r.metrics?.orders ?? 0} />
                            <Metric label="Revenue" value={`${symbol}${Number(r.metrics?.revenue ?? 0).toLocaleString()}`} />
                            <Metric label="Escalations" value={r.metrics?.escalations ?? 0} />
                            <Metric label="AI Resolution" value={`${r.metrics?.ai_resolution_rate ?? 0}%`} />
                            <Metric label="AI Runs" value={r.metrics?.ai_runs ?? 0} />
                            <Metric label="Knowledge Gaps" value={r.metrics?.open_knowledge_gaps ?? 0} />
                        </div>
                    </div>
                ))}
                {reports.length === 0 && (
                    <p className="text-center text-gray-400 py-12">No reports generated yet.</p>
                )}
            </div>
        </TenantLayout>
    );
}

function Metric({ label, value }) {
    return (
        <div className="bg-gray-50 rounded-lg p-3">
            <p className="text-lg font-bold text-gray-900">{value}</p>
            <p className="text-xs text-gray-500">{label}</p>
        </div>
    );
}