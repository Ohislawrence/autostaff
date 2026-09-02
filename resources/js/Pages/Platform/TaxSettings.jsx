import { useForm } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function TaxSettings({ settings }) {
    const { data, setData, put, processing } = useForm({
        vat_rate: settings.vat_rate ?? 7.5,
        additional_taxes: settings.additional_taxes || [],
        manual_payment_email: settings.manual_payment_email || '',
        manual_payment_instructions: settings.manual_payment_instructions || '',
        company_name: settings.company_name || '',
        company_address: settings.company_address || '',
        company_tax_id: settings.company_tax_id || '',
        invoice_prefix: settings.invoice_prefix || 'INV',
    });

    const updateTax = (i, key, value) => {
        const taxes = [...data.additional_taxes];
        taxes[i] = { ...taxes[i], [key]: value };
        setData('additional_taxes', taxes);
    };
    const addTax = () => setData('additional_taxes', [...data.additional_taxes, { name: '', rate: '' }]);
    const removeTax = (i) => setData('additional_taxes', data.additional_taxes.filter((_, idx) => idx !== i));

    const field = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none';
    const label = 'block text-sm font-medium text-gray-700 mb-1';

    return (
        <PlatformLayout title="Tax & Billing Settings">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">💰 Tax & Billing Settings</h1>

            <form onSubmit={(e) => { e.preventDefault(); put('/platform/tax-settings'); }} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 max-w-3xl space-y-8">
                <div>
                    <h3 className="font-semibold text-gray-900 mb-3">VAT & Government Taxes</h3>
                    <div className="grid grid-cols-2 gap-4">
                        <div><label className={label}>VAT Rate (%)</label><input type="number" step="0.01" min="0" max="100" className={field} value={data.vat_rate} onChange={e => setData('vat_rate', e.target.value)} /></div>
                    </div>
                    <div className="mt-4">
                        <p className="text-sm font-medium text-gray-700 mb-2">Additional government taxes</p>
                        {data.additional_taxes.map((t, i) => (
                            <div key={i} className="flex gap-2 mb-2">
                                <input className={field} placeholder="Tax name (e.g. Withholding Tax)" value={t.name} onChange={e => updateTax(i, 'name', e.target.value)} />
                                <input type="number" step="0.01" min="0" max="100" className={field} placeholder="Rate %" value={t.rate} onChange={e => updateTax(i, 'rate', e.target.value)} />
                                <button type="button" onClick={() => removeTax(i)} className="px-3 py-2 bg-red-50 text-red-600 rounded-lg text-sm">✕</button>
                            </div>
                        ))}
                        <button type="button" onClick={addTax} className="text-sm text-blue-600 hover:underline">+ Add tax</button>
                    </div>
                </div>

                <div>
                    <h3 className="font-semibold text-gray-900 mb-3">Manual Payment</h3>
                    <div className="grid grid-cols-2 gap-4">
                        <div><label className={label}>Manual Payment Email</label><input type="email" className={field} value={data.manual_payment_email} onChange={e => setData('manual_payment_email', e.target.value)} /></div>
                        <div><label className={label}>Invoice Prefix</label><input className={field} value={data.invoice_prefix} onChange={e => setData('invoice_prefix', e.target.value)} /></div>
                        <div className="col-span-2"><label className={label}>Manual Payment Instructions (bank transfer details)</label><textarea rows={3} className={field} value={data.manual_payment_instructions} onChange={e => setData('manual_payment_instructions', e.target.value)} placeholder="Bank name, account name, account number, sort code, etc." /></div>
                    </div>
                </div>

                <div>
                    <h3 className="font-semibold text-gray-900 mb-3">Invoice Details (Nigerian approved)</h3>
                    <div className="grid grid-cols-2 gap-4">
                        <div><label className={label}>Company Name</label><input className={field} value={data.company_name} onChange={e => setData('company_name', e.target.value)} /></div>
                        <div><label className={label}>TIN / VAT Number</label><input className={field} value={data.company_tax_id} onChange={e => setData('company_tax_id', e.target.value)} /></div>
                        <div className="col-span-2"><label className={label}>Company Address</label><textarea rows={2} className={field} value={data.company_address} onChange={e => setData('company_address', e.target.value)} /></div>
                    </div>
                </div>

                <button type="submit" disabled={processing} className="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Save Settings</button>
            </form>
        </PlatformLayout>
    );
}
