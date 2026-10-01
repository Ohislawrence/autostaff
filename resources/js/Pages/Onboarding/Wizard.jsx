import { usePage, router } from '@inertiajs/react';
import { useState } from 'react';

const inputClass =
    'w-full rounded-xl border border-ink/15 bg-white/70 px-4 py-2.5 text-sm text-ink outline-none transition placeholder:text-ink-faint focus:border-ink/40 focus:ring-2 focus:ring-periwinkle/30';

const labelClass = 'mb-1 block text-sm font-semibold text-ink';

const primaryButtonClass =
    'w-full rounded-full bg-ink px-4 py-3 text-sm font-bold text-bone transition hover:bg-forest disabled:cursor-not-allowed disabled:opacity-50';

const iconBoxClass = 'w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl';

export default function OnboardingWizard({ step, steps, organization, aiEmployee, offering, pricePoint, icp, campaign, prospects, userEmail, canUseProspecting = false, planName = 'Free' }) {
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
        <div className="relative min-h-screen overflow-hidden bg-bone font-sans text-ink">
            <div className="pointer-events-none absolute inset-0">
                <div className="absolute -top-40 -left-24 h-[30rem] w-[30rem] rounded-full bg-blush/50 blur-[120px]" />
                <div className="absolute top-1/4 -right-32 h-[26rem] w-[26rem] rounded-full bg-periwinkle/40 blur-[120px]" />
                <div className="absolute bottom-0 left-1/3 h-[22rem] w-[22rem] rounded-full bg-citron/50 blur-[110px]" />
            </div>

            <div className="relative flex min-h-screen items-center justify-center px-4 py-12">
                <div className="w-full max-w-2xl">
                    <div className="mb-8 flex items-center justify-center gap-3">
                        <img src="/images/nomdal-favicon.png" alt="Nomdal" className="h-10 w-10 object-contain" />
                        <div className="text-left">
                            <p className="font-display text-xl font-black tracking-tight text-ink leading-none">Nomdal</p>
                            <p className="text-xs text-ink-dim">Set up your workspace</p>
                        </div>
                    </div>

                    <div className="mb-8">
                        <div className="flex items-center justify-center gap-2 mb-4">
                            {steps.map((s, i) => (
                                <div key={s.key} className="flex items-center">
                                    <div className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-colors ${
                                        i < currentIndex
                                            ? 'bg-forest text-bone'
                                            : i === currentIndex
                                            ? 'bg-forest text-bone ring-4 ring-forest/20'
                                            : 'bg-ink/10 text-ink-faint'
                                    }`}>
                                        {i < currentIndex ? '✓' : i + 1}
                                    </div>
                                    {i < totalSteps - 1 && (
                                        <div className={`w-12 h-0.5 mx-1 transition-colors ${
                                            i < currentIndex ? 'bg-forest/60' : 'bg-ink/10'
                                        }`} />
                                    )}
                                </div>
                            ))}
                        </div>
                        <h1 className="text-center text-xl font-bold text-ink">
                            {steps[currentIndex]?.label || 'Setup'}
                        </h1>
                        <p className="text-center text-sm text-ink-dim mt-1">
                            Step {currentIndex + 1} of {totalSteps}
                        </p>
                    </div>

                    <div className="rounded-3xl border border-ink/10 bg-white/70 p-8 shadow-sm backdrop-blur">
                        {step === 'create_org' && (
                            <CreateOrganizationForm onSubmit={handleSubmit} errors={errors} submitting={submitting} organization={organization} />
                        )}

                        {step === 'configure_profile' && (
                            <ConfigureProfileForm onSubmit={handleSubmit} errors={errors} submitting={submitting} organization={organization} userEmail={userEmail} />
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
                            <LaunchStep onSubmit={handleSubmit} submitting={submitting} organization={organization} offering={offering} pricePoint={pricePoint} icp={icp} canUseProspecting={canUseProspecting} planName={planName} />
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
                <div className={`${iconBoxClass} bg-periwinkle/20 text-periwinkle-700`}>
                    <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </div>
                <h2 className="text-lg font-bold text-ink">Create your organization</h2>
                <p className="text-sm text-ink-dim mt-1">This is the workspace where your AI employees will live.</p>
            </div>

            <div className="space-y-4">
                <div>
                    <label htmlFor="name" className={labelClass}>Organization name *</label>
                    <input id="name" name="name" type="text" required autoFocus value={form.name} onChange={handleChange} placeholder="e.g. Acme Furniture" className={inputClass} />
                    {errors?.name && <p className="mt-1 text-xs text-wine">{errors.name}</p>}
                </div>
                <div>
                    <label htmlFor="industry" className={labelClass}>Industry</label>
                    <select id="industry" name="industry" value={form.industry} onChange={handleChange} className={inputClass}>
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

            <button type="submit" disabled={submitting} className={`mt-6 ${primaryButtonClass}`}>
                {submitting ? 'Creating…' : 'Create organization'}
            </button>
        </form>
    );
}

function ConfigureProfileForm({ onSubmit, errors, submitting, organization, userEmail }) {
    const [form, setForm] = useState({
        website: organization?.website || '',
        email: organization?.email || userEmail || '',
        phone: organization?.phone || '',
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
                <div className={`${iconBoxClass} bg-periwinkle/20`}>🏢</div>
                <h2 className="text-lg font-bold text-ink">Tell us about your business</h2>
                <p className="text-sm text-ink-dim mt-1">A few quick details so your AI sounds like you.</p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label htmlFor="website" className={labelClass}>Website</label>
                    <input id="website" name="website" type="url" value={form.website} onChange={handleChange} placeholder="https://example.com" className={inputClass} />
                </div>
                <div>
                    <label htmlFor="email" className={labelClass}>Contact email</label>
                    <input id="email" name="email" type="email" value={form.email} onChange={handleChange} placeholder="contact@example.com" className={inputClass} />
                </div>
                <div>
                    <label htmlFor="phone" className={labelClass}>Phone</label>
                    <input id="phone" name="phone" type="text" value={form.phone} onChange={handleChange} placeholder="+234 800 000 0000" className={inputClass} />
                </div>
                <div>
                    <label htmlFor="timezone" className={labelClass}>Timezone *</label>
                    <select id="timezone" name="timezone" value={form.timezone} onChange={handleChange} required className={inputClass}>
                        {['UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'Europe/London', 'Europe/Berlin', 'Asia/Dubai', 'Asia/Kolkata', 'Asia/Shanghai', 'Africa/Lagos'].map(tz => (
                            <option key={tz} value={tz}>{tz}</option>
                        ))}
                    </select>
                </div>
                <div>
                    <label htmlFor="currency" className={labelClass}>Currency *</label>
                    <select id="currency" name="currency" value={form.currency} onChange={handleChange} required className={inputClass}>
                        {['NGN', 'USD', 'EUR', 'GBP', 'INR', 'CAD', 'AUD', 'JPY'].map(c => (
                            <option key={c} value={c}>{c}</option>
                        ))}
                    </select>
                </div>
                <div>
                    <label htmlFor="city" className={labelClass}>City</label>
                    <input id="city" name="city" type="text" value={form.city} onChange={handleChange} placeholder="City" className={inputClass} />
                </div>
                <div>
                    <label htmlFor="state" className={labelClass}>State / region</label>
                    <input id="state" name="state" type="text" value={form.state} onChange={handleChange} placeholder="State" className={inputClass} />
                </div>
                <div>
                    <label htmlFor="country" className={labelClass}>Country</label>
                    <input id="country" name="country" type="text" value={form.country} onChange={handleChange} placeholder="Country" className={inputClass} />
                </div>
            </div>

            <button type="submit" disabled={submitting} className={`mt-6 ${primaryButtonClass}`}>
                {submitting ? 'Saving…' : 'Continue'}
            </button>
        </form>
    );
}

function CreateAiEmployeeForm({ onSubmit, errors, submitting, organization }) {
    const [form, setForm] = useState({ name: '', personality: '', language: 'en' });
    const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });

    return (
        <form onSubmit={(e) => { e.preventDefault(); onSubmit('/onboarding/create-ai-employee', form); }}>
            <div className="text-center mb-6">
                <div className={`${iconBoxClass} bg-periwinkle/20`}>🤖</div>
                <h2 className="text-lg font-bold text-ink">Create your first AI employee</h2>
                <p className="text-sm text-ink-dim mt-1">This AI handles customer conversations for {organization?.name || 'your business'}.</p>
            </div>
            <div className="space-y-4">
                <div>
                    <label htmlFor="name" className={labelClass}>AI employee name *</label>
                    <input id="name" name="name" type="text" required value={form.name} onChange={handleChange} placeholder="e.g. Ada" className={inputClass} />
                    {errors?.name && <p className="mt-1 text-xs text-wine">{errors.name}</p>}
                </div>
                <div>
                    <label htmlFor="personality" className={labelClass}>Personality <span className="font-normal text-ink-faint">(optional)</span></label>
                    <textarea id="personality" name="personality" rows={3} value={form.personality} onChange={handleChange} placeholder="e.g. Friendly, professional, and concise" className={inputClass} />
                </div>
                <div>
                    <label htmlFor="language" className={labelClass}>Language</label>
                    <select id="language" name="language" value={form.language} onChange={handleChange} className={inputClass}>
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
            <button type="submit" disabled={submitting} className={`mt-6 ${primaryButtonClass}`}>
                {submitting ? 'Creating…' : 'Create AI employee'}
            </button>
        </form>
    );
}

function CompleteStep({ organization, aiEmployee }) {
    return (
        <div className="text-center">
            <div className="w-20 h-20 bg-forest/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg className="w-10 h-10 text-forest" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 className="text-xl font-bold text-ink mb-2">You are all set! 🎉</h2>
            <p className="text-ink-dim mb-6">Welcome to {organization?.name || 'your organization'}. Your AI employee{aiEmployee ? ` "${aiEmployee.name}"` : ''} is ready to start conversations.</p>

            <div className="rounded-2xl border border-ink/10 bg-bone/60 p-4 mb-6 text-left space-y-2">
                {['Organization created', 'Profile configured', 'AI employee created and active'].map(item => (
                    <div key={item} className="flex items-center gap-3 text-sm text-ink">
                        <div className="w-6 h-6 rounded bg-forest/15 flex items-center justify-center shrink-0">
                            <svg className="w-3.5 h-3.5 text-forest" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <span>{item}</span>
                    </div>
                ))}
            </div>

            <a href="/dashboard" className="inline-flex rounded-full bg-ink px-8 py-3 text-sm font-bold text-bone transition hover:bg-forest">Go to dashboard</a>
        </div>
    );
}

function SalesGoalForm({ onSubmit, errors, submitting, organization, offering, pricePoint }) {
    const [form, setForm] = useState({ offering: offering || '', price_point: pricePoint || '' });
    const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });

    return (
        <form onSubmit={(e) => { e.preventDefault(); onSubmit('/onboarding/sales-goal', form); }}>
            <div className="text-center mb-6">
                <div className={`${iconBoxClass} bg-citron/30`}>💼</div>
                <h2 className="text-lg font-bold text-ink">What do you sell?</h2>
                <p className="text-sm text-ink-dim mt-1">This helps your AI talk confidently about your business.</p>
            </div>
            <div className="space-y-4">
                <div>
                    <label htmlFor="offering" className={labelClass}>What do you sell? *</label>
                    <input name="offering" type="text" required autoFocus value={form.offering} onChange={handleChange} placeholder="e.g. Professional websites for SMEs" className={inputClass} />
                    {errors?.offering && <p className="mt-1 text-xs text-wine">{errors.offering}</p>}
                </div>
                <div>
                    <label htmlFor="price_point" className={labelClass}>Price point <span className="font-normal text-ink-faint">(optional)</span></label>
                    <input name="price_point" type="text" value={form.price_point} onChange={handleChange} placeholder="e.g. from ₦150,000" className={inputClass} />
                </div>
            </div>
            <button type="submit" disabled={submitting} className={`mt-6 ${primaryButtonClass}`}>
                {submitting ? 'Saving…' : 'Continue'}
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

    return (
        <form onSubmit={(e) => { e.preventDefault(); onSubmit('/onboarding/icp', form); }}>
            <div className="text-center mb-6">
                <div className={`${iconBoxClass} bg-blush/30`}>🎯</div>
                <h2 className="text-lg font-bold text-ink">Who are your ideal customers?</h2>
                <p className="text-sm text-ink-dim mt-1">So your AI can focus on the right people.</p>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="sm:col-span-2">
                    <label htmlFor="industry" className={labelClass}>Business type / industry</label>
                    <input id="industry" name="industry" type="text" value={form.industry} onChange={handleChange} placeholder="e.g. SMEs, retail stores, clinics" className={inputClass} />
                    <p className="mt-1 text-xs text-ink-faint">Comma separated</p>
                </div>
                <div>
                    <label htmlFor="company_size" className={labelClass}>Company size</label>
                    <input id="company_size" name="company_size" type="text" value={form.company_size} onChange={handleChange} placeholder="e.g. 5–50 employees" className={inputClass} />
                </div>
                <div>
                    <label htmlFor="geography" className={labelClass}>Geography</label>
                    <input id="geography" name="geography" type="text" value={form.geography} onChange={handleChange} placeholder="e.g. Lagos, Abuja" className={inputClass} />
                    <p className="mt-1 text-xs text-ink-faint">Comma separated</p>
                </div>
                <div>
                    <label htmlFor="keywords" className={labelClass}>Keywords</label>
                    <input id="keywords" name="keywords" type="text" value={form.keywords} onChange={handleChange} placeholder="e.g. website design, branding" className={inputClass} />
                    <p className="mt-1 text-xs text-ink-faint">Comma separated</p>
                </div>
                <div>
                    <label htmlFor="budget" className={labelClass}>Budget</label>
                    <input id="budget" name="budget" type="text" value={form.budget} onChange={handleChange} placeholder="e.g. ₦50k – ₦200k" className={inputClass} />
                </div>
                <div className="sm:col-span-2">
                    <label htmlFor="pain_points" className={labelClass}>Pain points <span className="font-normal text-ink-faint">(optional)</span></label>
                    <textarea id="pain_points" name="pain_points" rows={3} value={form.pain_points} onChange={handleChange} placeholder="What problems do your customers face?" className={inputClass} />
                </div>
            </div>
            <button type="submit" disabled={submitting} className={`mt-6 ${primaryButtonClass}`}>
                {submitting ? 'Saving…' : 'Continue'}
            </button>
        </form>
    );
}

function LaunchStep({ onSubmit, submitting, organization, offering, pricePoint, icp, canUseProspecting, planName }) {
    const [name, setName] = useState('Alex — Sales Assistant');
    const industry = (icp?.industry || []).join(', ') || 'any industry';
    const geo = (icp?.geography || []).join(', ') || 'anywhere';

    return (
        <form onSubmit={(e) => { e.preventDefault(); onSubmit('/onboarding/launch', { employee_name: name }); }}>
            <div className="text-center mb-6">
                <div className={`${iconBoxClass} bg-periwinkle/20`}>🤖</div>
                <h2 className="text-lg font-bold text-ink">
                    {canUseProspecting ? 'Hire your AI sales rep' : 'Hire your AI sales assistant'}
                </h2>
                <p className="text-sm text-ink-dim mt-1">
                    {canUseProspecting
                        ? 'Nomdal will find and qualify your first prospects automatically.'
                        : 'Your AI will answer questions and capture leads on your website and WhatsApp.'}
                </p>
            </div>

            {!canUseProspecting && (
                <div className="mb-5 rounded-2xl border border-rust/30 bg-rust/5 px-4 py-3">
                    <p className="text-sm font-semibold text-ink">⚡ Automated prospecting is a Business-plan feature</p>
                    <p className="mt-1 text-sm text-ink-dim">
                        Finding and contacting customers automatically is available on the <span className="font-semibold text-ink">Business</span> plan and above.
                        You are on the <span className="font-semibold text-ink">{planName || 'Free'}</span> plan, so your AI will handle inbound conversations for now.
                    </p>
                    <a href="/billing" className="mt-2 inline-block text-sm font-semibold text-forest hover:underline">Upgrade to unlock prospecting →</a>
                </div>
            )}

            <div className="rounded-2xl border border-ink/10 bg-bone/60 p-4 mb-5">
                <p className="text-xs uppercase tracking-wide text-ink-faint font-medium mb-2">Your setup</p>
                <div className="space-y-1.5 text-sm text-ink">
                    <p><span className="text-ink-dim">Sells:</span> {offering || 'Not specified'}</p>
                    {pricePoint && <p><span className="text-ink-dim">Price point:</span> {pricePoint}</p>}
                    <p><span className="text-ink-dim">Ideal customer:</span> {industry} · {geo}</p>
                </div>
            </div>

            <div>
                <label htmlFor="employee_name" className={labelClass}>Employee name</label>
                <input id="employee_name" type="text" value={name} onChange={(e) => setName(e.target.value)} className={inputClass} />
            </div>

            <button type="submit" disabled={submitting} className={`mt-6 ${primaryButtonClass}`}>
                {submitting ? 'Launching…' : canUseProspecting ? '🚀 Launch & find my first customers' : '🚀 Launch my AI assistant'}
            </button>
        </form>
    );
}

function ResultsStep({ organization, aiEmployee, campaign, prospects }) {
    const list = prospects || [];
    return (
        <div className="text-center">
            <div className="w-16 h-16 bg-forest/10 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">🎉</div>
            <h2 className="text-lg font-bold text-ink">Your AI sales rep is on the job</h2>
            <p className="text-sm text-ink-dim mt-1">
                {aiEmployee ? `"${aiEmployee.name}" is hunting for prospects.` : 'Your sales rep is hunting for prospects.'}
            </p>

            {list.length > 0 && (
                <div className="mt-6 text-left space-y-2">
                    <p className="text-xs uppercase tracking-wide text-ink-faint font-medium">Prospects found</p>
                    {list.map((p) => (
                        <div key={p.id} className="rounded-xl border border-ink/10 bg-bone/60 p-3 flex items-center gap-3">
                            <span className={`inline-flex items-center justify-center w-9 h-9 rounded-full text-xs font-bold shrink-0 ${p.score >= 7 ? 'bg-forest/15 text-forest' : p.score >= 4 ? 'bg-accent-200 text-accent-800' : 'bg-ink/10 text-ink-faint'}`}>{p.score || '–'}</span>
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-medium text-ink truncate">{p.name || 'Prospect'}</p>
                                <p className="text-xs text-ink-dim truncate">{p.company || ''}{p.location ? ` · ${p.location}` : ''}</p>
                                {p.qualification_notes && <p className="text-xs text-ink-faint mt-0.5 truncate">{p.qualification_notes}</p>}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {list.length === 0 && (
                <p className="mt-4 text-sm text-ink-faint">Nomdal is searching for matching prospects. Check the Prospecting page in a moment.</p>
            )}

            <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="/prospecting" className="inline-flex rounded-full bg-ink px-6 py-3 text-sm font-bold text-bone transition hover:bg-forest">View prospects</a>
                <form method="POST" action="/onboarding/complete" className="inline-flex">
                    <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.content} />
                    <button type="submit" className="inline-flex rounded-full border border-ink/20 bg-white/60 px-6 py-3 text-sm font-bold text-ink transition hover:bg-white">Go to dashboard</button>
                </form>
            </div>
        </div>
    );
}
