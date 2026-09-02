import { Head, usePage, useForm } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Index({ subscription, plans, usage, limits, currentPlan, billingCurrency, ngnToUsdRate, invoices, manualPaymentEmail, manualPaymentInstructions, vatRate }) {
    const { flash } = usePage().props;
    const { post } = useForm();

    const symbol = { NGN: '₦', USD: '$' };
    const fmt = (amount, currency) => {
        if (!amount || Number(amount) <= 0) return 'Custom';
        const decimals = currency === 'NGN' ? 0 : 2;
        return `${symbol[currency] || ''}${Number(amount).toLocaleString(undefined, { maximumFractionDigits: decimals })}`;
    };
    const currentPrice = currentPlan
        ? (billingCurrency === 'NGN'
            ? { amount: currentPlan.price, currency: 'NGN' }
            : { amount: currentPlan.usd_price ?? (currentPlan.price / (ngnToUsdRate || 1500)), currency: 'USD' })
        : null;

    const getUsageColor = (percentage) => {
        if (percentage >= 90) return 'bg-red-500';
        if (percentage >= 80) return 'bg-yellow-500';
        return 'bg-green-500';
    };

    const getUsageTextColor = (percentage) => {
        if (percentage >= 90) return 'text-red-700';
        if (percentage >= 80) return 'text-yellow-700';
        return 'text-gray-700';
    };

    return (
        <TenantLayout header="Billing & Subscription">
            <Head title="Billing" />
            {flash?.success && <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">{flash.success}</div>}
            {flash?.error && <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-lg text-sm">{flash.error}</div>}

            {/* Current Plan */}
            <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-6 mb-6">
                <div className="flex items-center justify-between mb-4">
                    <div>
                        <h2 className="font-semibold text-gray-900">Current Plan</h2>
                        <p className="text-sm text-gray-600 mt-0.5">
                            {currentPlan ? `${currentPlan.name} — ${currentPrice ? fmt(currentPrice.amount, currentPrice.currency) : ''}/mo` : 'No active subscription'}
                        </p>
                    </div>
                    {subscription && (
                        <span className={`px-3 py-1 rounded-full text-xs font-medium ${subscription.status === 'active' ? 'bg-gradient-to-r from-green-400 to-green-500 text-white shadow-sm' : 'bg-gray-100 text-gray-600'}`}>
                            {subscription.status}
                        </span>
                    )}
                </div>

                {/* Usage with Progress Bars */}
                {limits && Object.keys(limits).length > 0 && (
                    <div className="space-y-4 mb-4">
                        {Object.entries(limits).map(([key, limit]) => (
                            <div key={key}>
                                <div className="flex items-center justify-between mb-1">
                                    <span className="text-sm font-medium text-gray-700 capitalize">{key.replace('_', ' ')}</span>
                                    <span className={`text-xs font-medium ${getUsageTextColor(limit.percentage)}`}>
                                        {limit.used.toLocaleString()} / {limit.limit.toLocaleString()}
                                    </span>
                                </div>
                                <div className="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                        className={`h-2 rounded-full transition-all ${getUsageColor(limit.percentage)}`}
                                        style={{ width: `${Math.min(limit.percentage, 100)}%` }}
                                    ></div>
                                </div>
                                {limit.percentage >= 80 && limit.percentage < 100 && (
                                    <p className="text-xs text-yellow-600 mt-1">⚠️ Approaching limit - consider upgrading</p>
                                )}
                                {limit.at_limit && (
                                    <p className="text-xs text-red-600 mt-1">🚫 Limit reached - upgrade to continue</p>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                {subscription && subscription.status === 'active' && (
                    <form onSubmit={(e) => { e.preventDefault(); if (confirm('Cancel your subscription?')) post('/billing/cancel'); }}>
                        <button type="submit" className="text-sm text-red-600 hover:text-red-700">Cancel Subscription</button>
                    </form>
                )}
            </div>

            {/* Plans */}
            <h2 className="font-semibold text-gray-900 mb-4">Available Plans</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {plans.map(plan => {
                    const isCurrentPlan = currentPlan?.id === plan.id;
                    const isPopular = plan.slug === 'business';
                    
                    return (
                        <div key={plan.id} className={`bg-white/80 backdrop-blur rounded-xl shadow-lg border p-6 relative transition-all hover:shadow-xl ${isCurrentPlan ? 'border-blue-400 ring-2 ring-blue-200' : isPopular ? 'border-amber-300 ring-2 ring-amber-100' : 'border-blue-100 hover:border-blue-200'}`}>
                            {isPopular && !isCurrentPlan && (
                                <span className="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-full text-xs font-bold shadow-md">
                                    MOST POPULAR
                                </span>
                            )}
                            {isCurrentPlan && (
                                <span className="px-2 py-0.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-full text-xs font-medium shadow-sm">Current Plan</span>
                            )}
                            <h3 className="font-semibold text-gray-900 mt-2">{plan.name}</h3>
                            <p className="text-xs text-gray-500 mt-1">{plan.description}</p>
                            <p className="text-2xl font-bold text-gray-900 mt-3">
                                {fmt(plan.display_total ?? plan.display_price, plan.display_currency)}
                                <span className="text-sm font-normal text-gray-400">/mo</span>
                            </p>
                            {plan.display_tax_amount > 0 && <p className="text-xs text-gray-400">incl. VAT ({vatRate}%)</p>}
                            <ul className="mt-4 space-y-1.5">
                                {plan.features?.map((f, i) => (
                                    <li key={i} className="text-xs text-gray-600 flex items-start gap-1.5">
                                        <span className="text-green-500 mt-0.5">✓</span> 
                                        <span>{f}</span>
                                    </li>
                                ))}
                            </ul>
                            {!isCurrentPlan && (
                                <form onSubmit={(e) => { e.preventDefault(); post('/billing/subscribe', { data: { plan_id: plan.id } }); }}>
                                    <button className={`w-full mt-4 px-4 py-2 rounded-xl text-sm font-medium transition-all shadow-md ${isPopular ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-white hover:from-amber-500 hover:to-amber-600' : 'bg-gradient-to-r from-blue-600 to-blue-700 text-white hover:from-blue-700 hover:to-blue-800'}`}>
                                        {plan.price > 0 ? 'Subscribe' : 'Contact Sales'}
                                    </button>
                                </form>
                            )}
                        </div>
                    );
                })}
            </div>

            {/* Manual payment */}
            <div className="mt-8 bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-6">
                <h2 className="font-semibold text-gray-900 mb-2">💵 Prefer to pay manually?</h2>
                <p className="text-sm text-gray-600">{manualPaymentInstructions || 'Bank transfer is available.'}</p>
                <p className="text-sm text-gray-700 mt-2">Email your payment proof to <strong>{manualPaymentEmail}</strong> and we'll activate your subscription.</p>
            </div>

            {/* Invoices */}
            {invoices?.length > 0 && (
                <div className="mt-8 bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-6">
                    <h2 className="font-semibold text-gray-900 mb-3">🧾 Invoices</h2>
                    <div className="space-y-2">
                        {invoices.map(inv => (
                            <div key={inv.id} className="flex items-center justify-between text-sm border-b border-gray-100 pb-2">
                                <div>
                                    <a href={`/billing/invoices/${inv.id}`} className="text-blue-600 hover:underline font-medium">{inv.invoice_number}</a>
                                    <p className="text-xs text-gray-500">{new Date(inv.created_at).toLocaleDateString()} · {inv.currency === 'NGN' ? '₦' : '$'}{Number(inv.total).toLocaleString()}</p>
                                </div>
                                <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${inv.status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}`}>{inv.status}</span>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </TenantLayout>
    );
}