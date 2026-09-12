import { usePage, router } from '@inertiajs/react';
import { useState } from 'react';

export default function OnboardingWizard({ step, steps, organization, aiEmployee, offering, pricePoint, icp, campaign, prospects }) {
    const { errors } = usePage().props;
    const [submitting, setSubmitting] = useState(false);

    const currentIndex = steps.findIndex(s => s.key === step);
    const totalSteps = steps.length;

    const handleSubmit = (url, data) => {
        setSubmitting(true);
        router.post(url, data, {
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-gray-50 to-primary-50 flex items-center justify-center p-4">
            <div className="w-full max-w-2xl">
                {/* Progress indicator */}
                <div className="mb-8">
                    <div className="flex items-center justify-center gap-2 mb-4">
                        {steps.map((s, i) => (
                            <div key={s.key} className="flex items-center">
                                <div className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-colors ${
                                    i < currentIndex
                                        ? 'bg-primary-600 text-white'
                                        : i === currentIndex
                                        ? 'bg-primary-600 text-white ring-4 ring-primary-100'
                                        : 'bg-gray-200 text-gray-400'
                                }`}>
                                    {i < currentIndex ? '✓' : i + 1}
                                </div>
                                {i < totalSteps - 1 && (
                                    <div className={`w-12 h-0.5 mx-1 transition-colors ${
                                        i < currentIndex ? 'bg-primary-400' : 'bg-gray-200'
                                    }`} />
                                )}
                            </div>
                        ))}
                    </div>
                    <h1 className="text-center text-xl font-bold text-gray-900">
                        {steps[currentIndex]?.label || 'Setup'}
                    </h1>
                    <p className="text-center text-sm text-gray-500 mt-1">
                        Step {currentIndex + 1} of {totalSteps}
                    </p>
                </div>

                {/* Card */}
                <div className="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                    {step === 'create_org' && (
                        <CreateOrganizationForm onSubmit={handleSubmit} errors={errors} submitting={submitting} organization={organization} />
                    )}

                    {step === 'configure_profile' && (
                        <ConfigureProfileForm onSubmit={handleSubmit} errors={errors} submitting={submitting} organization={organization} />
                    )}

                    {step === 'create_ai_employee' && (
                        <CreateAiEmployeeForm onSubmit={handleSubmit} errors={errors} submitting={submitting} organization={organization} />
                    )}

                    {step === 'sales_goal' && (
                        <SalesGoalForm onSubmit={handleSubmit} errors={errors} submitting={submitting} organization={organization} offering={offering} pricePoint={pricePoint} />
                    )}

                    {step === 'icp' && (
                        <IcpForm onSubmit={handleSubmit} errors={errors} submitting={submitting} organization={organization} icp={icp} />
                    )}

                    {step === 'launch' && (
                        <LaunchStep onSubmit={handleSubmit} submitting={submitting} organization={organization} offering={offering} pricePoint={pricePoint} icp={icp} />
                    )}

                    {step === 'results' && (
                        <ResultsStep organization={organization} aiEmployee={aiEmployee} campaign={campaign} prospects={prospects} />
                    )}

                    {step === 'complete' && (
                        <CompleteStep organization={organization} aiEmployee={aiEmployee} />
                    )}
                </div>
            </div>
        </div>
    );
}

function CreateOrganizationForm({ onSubmit, errors, submitting, organization }) {
    const [form, setForm] = useState({
        name: organization?.name || '',
        industry: organization?.industry || '',
    });

    const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });

    return (
        <form onSubmit={(e) => { e.preventDefault(); onSubmit('/onboarding/create-org', form); }}>
            <div className="text-center mb-6">
                <div className="w-16 h-16 bg-primary-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg className="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </div>
                <h2 className="text-lg font-semibold text-gray-900">Let's create your organization</h2>
                <p className="text-sm text-gray-500 mt-1">This will be your workspace for managing AI employees.</p>
            </div>

            <div className="space-y-4">
                <div>
                    <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">Organization Name *</label>
                    <input
                        id="name" name="name" type="text" required autoFocus
                        value={form.name}
                        onChange={handleChange}
                        placeholder="e.g. Acme Corp"
                        className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                    />
                    {errors?.name && <p className="mt-1 text-xs text-red-500">{errors.name}</p>}
                </div>
                <div>
                    <label htmlFor="industry" className="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                    <select
                        id="industry" name="industry"
                        value={form.industry}
                        onChange={handleChange}
                        className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                    >
                        <option value="">Select an industry (optional)</option>
                        <option value="Technology">Technology</option>
                        <option value="Healthcare">Healthcare</option>
                        <option value="Finance">Finance</option>
                        <option value="Education">Education</option>
                        <option value="Retail">Retail</option>
                        <option value="Real Estate">Real Estate</option>
                        <option value="Manufacturing">Manufacturing</option>
                        <option value="Consulting">Consulting</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <button
                type="submit" disabled={submitting}
                className="w-full mt-6 px-4 py-2.5 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition-colors"
            >
                {submitting ? 'Creating...' : 'Create Organization'}
            </button>
        </form>
    );
}

function ConfigureProfileForm({ onSubmit, errors, submitting, organization }) {
    const [form, setForm] = useState({
        website: organization?.website || '',
        email: organization?.email || '',
        phone: organization?.phone || '',
        description: organization?.description || '',
        timezone: organization?.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone,
        currency: organization?.currency || 'NGN',
        city: organization?.city || '',
        state: organization?.state || '',
        country: organization?.country || '',
    });

    const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });

    return (
        <form onSubmit={(e) => { e.preventDefault(); onSubmit('/onboarding/configure-profile', form); }}>
            <div className="text-center mb-6">
                <div className="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg className="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h2 className="text-lg font-semibold text-gray-900">Configure your profile</h2>
                <p className="text-sm text-gray-500 mt-1">Tell us a bit about your business.</p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="sm:col-span-2">
                    <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows={2} value={form.description} onChange={handleChange} placeholder="Brief description of your company" className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label htmlFor="website" className="block text-sm font-medium text-gray-700 mb-1">Website</label>
                    <input id="website" name="website" type="url" value={form.website} onChange={handleChange} placeholder="https://example.com" className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input id="email" name="email" type="email" value={form.email} onChange={handleChange} placeholder="contact@example.com" className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input id="phone" name="phone" type="text" value={form.phone} onChange={handleChange} placeholder="+1 234 567 8900" className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label htmlFor="timezone" className="block text-sm font-medium text-gray-700 mb-1">Timezone *</label>
                    <select id="timezone" name="timezone" value={form.timezone} onChange={handleChange} required className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        {['UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'Europe/London', 'Europe/Berlin', 'Asia/Dubai', 'Asia/Kolkata', 'Asia/Shanghai', 'Africa/Lagos'].map(tz => (
                            <option key={tz} value={tz}>{tz}</option>
                        ))}
                    </select>
                </div>
                <div>
                    <label htmlFor="currency" className="block text-sm font-medium text-gray-700 mb-1">Currency *</label>
                    <select id="currency" name="currency" value={form.currency} onChange={handleChange} required className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        {['NGN', 'USD', 'EUR', 'GBP', 'INR', 'CAD', 'AUD', 'JPY'].map(c => (
                            <option key={c} value={c}>{c}</option>
                        ))}
                    </select>
                </div>
                <div>
                    <label htmlFor="city" className="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input id="city" name="city" type="text" value={form.city} onChange={handleChange} placeholder="City" className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label htmlFor="state" className="block text-sm font-medium text-gray-700 mb-1">State / Region</label>
                    <input id="state" name="state" type="text" value={form.state} onChange={handleChange} placeholder="State" className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label htmlFor="country" className="block text-sm font-medium text-gray-700 mb-1">Country</label>
                    <input id="country" name="country" type="text" value={form.country} onChange={handleChange} placeholder="Country" className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
            </div>

            <button type="submit" disabled={submitting} className="w-full mt-6 px-4 py-2.5 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition-colors">
                {submitting ? 'Saving...' : 'Continue'}
            </button>
        </form>
    );
}

function CreateAiEmployeeForm({ onSubmit, errors, submitting, organization }) {
    const [form, setForm] = useState({
        name: '',
        personality: '',
        language: 'en',
    });

    const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });

    return (
        <form onSubmit={(e) => { e.preventDefault(); onSubmit('/onboarding/create-ai-employee', form); }}>
            <div className="text-center mb-6">
                <div className="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg className="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                    </svg>
                </div>
                <h2 className="text-lg font-semibold text-gray-900">Create your first AI Employee</h2>
                <p className="text-sm text-gray-500 mt-1">This AI will handle customer conversations for {organization?.name || 'your business'}.</p>
            </div>

            <div className="space-y-4">
                <div>
                    <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">AI Employee Name *</label>
                    <input id="name" name="name" type="text" required autoFocus value={form.name} onChange={handleChange} placeholder="e.g. Sarah - Sales Assistant" className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                    {errors?.name && <p className="mt-1 text-xs text-red-500">{errors.name}</p>}
                </div>
                <div>
                    <label htmlFor="personality" className="block text-sm font-medium text-gray-700 mb-1">Personality / System Prompt</label>
                    <textarea id="personality" name="personality" rows={3} value={form.personality} onChange={handleChange} placeholder={`You are a helpful and friendly AI assistant for ${organization?.name || 'our company'}. You help customers with questions about our products and services, schedule appointments, and provide support.`} className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label htmlFor="language" className="block text-sm font-medium text-gray-700 mb-1">Language</label>
                    <select id="language" name="language" value={form.language} onChange={handleChange} className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        <option value="en">English</option>
                        <option value="es">Spanish</option>
                        <option value="fr">French</option>
                        <option value="de">German</option>
                        <option value="pt">Portuguese</option>
                        <option value="ar">Arabic</option>
                        <option value="zh">Chinese</option>
                        <option value="hi">Hindi</option>
                    </select>
                </div>
            </div>

            <button type="submit" disabled={submitting} className="w-full mt-6 px-4 py-2.5 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition-colors">
                {submitting ? 'Creating...' : 'Create AI Employee'}
            </button>
        </form>
    );
}

function CompleteStep({ organization, aiEmployee }) {
    return (
        <div className="text-center">
            <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg className="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 className="text-xl font-bold text-gray-900 mb-2">You're all set! 🎉</h2>
            <p className="text-gray-500 mb-6">
                Welcome to {organization?.name || 'your organization'}. Your AI employee{aiEmployee ? ` "${aiEmployee.name}"` : ''} is ready to start handling conversations.
            </p>

            <div className="bg-gray-50 rounded-xl p-4 mb-6 text-left space-y-2">
                <div className="flex items-center gap-3 text-sm">
                    <div className="w-6 h-6 rounded bg-primary-100 flex items-center justify-center shrink-0">
                        <svg className="w-3.5 h-3.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <span>Organization created</span>
                </div>
                <div className="flex items-center gap-3 text-sm">
                    <div className="w-6 h-6 rounded bg-primary-100 flex items-center justify-center shrink-0">
                        <svg className="w-3.5 h-3.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <span>Profile configured</span>
                </div>
                <div className="flex items-center gap-3 text-sm">
                    <div className="w-6 h-6 rounded bg-primary-100 flex items-center justify-center shrink-0">
                        <svg className="w-3.5 h-3.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <span>AI employee created and active</span>
                </div>
            </div>

            <a
                href="/dashboard"
                className="inline-flex px-6 py-3 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-700 transition-colors"
            >
                Go to Dashboard
            </a>
        </div>
    );
}

function SalesGoalForm({ onSubmit, errors, submitting, organization, offering, pricePoint }) {
    const [form, setForm] = useState({
        offering: offering || '',
        price_point: pricePoint || '',
    });
    const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });
    const inputCls = "w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none";

    return (
        <form onSubmit={(e) => { e.preventDefault(); onSubmit('/onboarding/sales-goal', form); }}>
            <div className="text-center mb-6">
                <div className="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">💼</div>
                <h2 className="text-lg font-semibold text-gray-900">What do you sell?</h2>
                <p className="text-sm text-gray-500 mt-1">Nomdal will find and qualify customers for exactly this.</p>
            </div>
            <div className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">What do you sell? *</label>
                    <input name="offering" type="text" required autoFocus value={form.offering} onChange={handleChange} placeholder="e.g. Professional websites for SMEs" className={inputCls} />
                    {errors?.offering && <p className="mt-1 text-xs text-red-500">{errors.offering}</p>}
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">How much is your service? (optional)</label>
                    <input name="price_point" type="text" value={form.price_point} onChange={handleChange} placeholder="e.g. ₦250,000" className={inputCls} />
                </div>
            </div>
            <button type="submit" disabled={submitting} className="w-full mt-6 px-4 py-2.5 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition-colors">
                {submitting ? 'Saving...' : 'Continue'}
            </button>
        </form>
    );
}

function IcpForm({ onSubmit, errors, submitting, organization, icp }) {
    const [form, setForm] = useState({
        industry: (icp?.industry || []).join(', '),
        company_size: icp?.company_size || '',
        geography: (icp?.geography || []).join(', '),
        keywords: (icp?.keywords || []).join(', '),
        budget: icp?.budget || '',
        pain_points: icp?.pain_points || '',
    });
    const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });
    const inputCls = "w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none";

    return (
        <form onSubmit={(e) => { e.preventDefault(); onSubmit('/onboarding/icp', form); }}>
            <div className="text-center mb-6">
                <div className="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">🎯</div>
                <h2 className="text-lg font-semibold text-gray-900">Who do you want as customers?</h2>
                <p className="text-sm text-gray-500 mt-1">This becomes Nomdal's ideal customer profile for hunting.</p>
            </div>
            <div className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Business type / industry</label>
                    <input name="industry" type="text" value={form.industry} onChange={handleChange} placeholder="SMEs, retail, clinics" className={inputCls} />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Company size</label>
                    <input name="company_size" type="text" value={form.company_size} onChange={handleChange} placeholder="5–50 employees" className={inputCls} />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <input name="geography" type="text" value={form.geography} onChange={handleChange} placeholder="Nigeria, Lagos" className={inputCls} />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Keywords (what they might search)</label>
                    <input name="keywords" type="text" value={form.keywords} onChange={handleChange} placeholder="website, booking, e-commerce" className={inputCls} />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Customer budget</label>
                    <input name="budget" type="text" value={form.budget} onChange={handleChange} placeholder="₦150k–₦500k" className={inputCls} />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Their need / pain point</label>
                    <input name="pain_points" type="text" value={form.pain_points} onChange={handleChange} placeholder="outdated website, missed leads" className={inputCls} />
                </div>
            </div>
            <button type="submit" disabled={submitting} className="w-full mt-6 px-4 py-2.5 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition-colors">
                {submitting ? 'Saving...' : 'Continue'}
            </button>
        </form>
    );
}

function LaunchStep({ onSubmit, submitting, organization, offering, pricePoint, icp }) {
    const [name, setName] = useState('Alex — Sales Assistant');
    const industry = (icp?.industry || []).join(', ') || 'any industry';
    const geo = (icp?.geography || []).join(', ') || 'anywhere';

    return (
        <form onSubmit={(e) => { e.preventDefault(); onSubmit('/onboarding/launch', { employee_name: name }); }}>
            <div className="text-center mb-6">
                <div className="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">🤖</div>
                <h2 className="text-lg font-semibold text-gray-900">Hire your AI Sales Employee</h2>
                <p className="text-sm text-gray-500 mt-1">Here's what Nomdal will set up for you.</p>
            </div>

            <div className="space-y-3 mb-6">
                <div className="bg-gray-50 rounded-xl p-4">
                    <p className="text-xs text-gray-400 uppercase tracking-wide">AI Sales Employee</p>
                    <p className="font-medium text-gray-900">🎯 {name}</p>
                </div>
                <div className="bg-gray-50 rounded-xl p-4">
                    <p className="text-xs text-gray-400 uppercase tracking-wide">Offering</p>
                    <p className="font-medium text-gray-900">{offering || 'Your product/service'}{pricePoint ? ` — from ${pricePoint}` : ''}</p>
                </div>
                <div className="bg-gray-50 rounded-xl p-4">
                    <p className="text-xs text-gray-400 uppercase tracking-wide">Ideal Customer Profile</p>
                    <p className="font-medium text-gray-900">{industry} · {geo}</p>
                </div>
            </div>

            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Employee name</label>
                <input type="text" value={name} onChange={(e) => setName(e.target.value)} className="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
            </div>

            <button type="submit" disabled={submitting} className="w-full mt-6 px-4 py-2.5 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition-colors">
                {submitting ? 'Launching…' : '🚀 Launch & find my first customers'}
            </button>
        </form>
    );
}

function ResultsStep({ organization, aiEmployee, campaign, prospects }) {
    const list = prospects || [];
    return (
        <div className="text-center">
            <div className="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">🎉</div>
            <h2 className="text-lg font-semibold text-gray-900">Your AI Sales Employee is on the job</h2>
            <p className="text-sm text-gray-500 mt-1">
                {aiEmployee ? `"${aiEmployee.name}" is hunting for prospects.` : 'Your sales rep is hunting for prospects.'}
            </p>

            {list.length > 0 && (
                <div className="mt-6 text-left space-y-2">
                    <p className="text-xs text-gray-400 uppercase tracking-wide font-medium">Prospects found</p>
                    {list.map((p) => (
                        <div key={p.id} className="bg-gray-50 rounded-xl p-3 flex items-center gap-3">
                            <span className={`inline-flex items-center justify-center w-9 h-9 rounded-full text-xs font-bold shrink-0 ${p.score >= 7 ? 'bg-green-100 text-green-700' : p.score >= 4 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-200 text-gray-600'}`}>{p.score || '–'}</span>
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-medium text-gray-900 truncate">{p.name || 'Prospect'}</p>
                                <p className="text-xs text-gray-500 truncate">{p.company || ''}{p.location ? ` · ${p.location}` : ''}</p>
                                {p.qualification_notes && <p className="text-xs text-gray-400 mt-0.5 truncate">{p.qualification_notes}</p>}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {list.length === 0 && (
                <p className="mt-4 text-sm text-gray-400">Nomdal is searching for matching prospects. Check the Prospecting page in a moment.</p>
            )}

            <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="/prospecting" className="inline-flex px-6 py-3 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-700 transition-colors">View Prospects</a>
                <form method="POST" action="/onboarding/complete" className="inline-flex">
                    <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.content} />
                    <button type="submit" className="inline-flex px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-50 transition-colors">Go to Dashboard</button>
                </form>
            </div>
        </div>
    );
}

