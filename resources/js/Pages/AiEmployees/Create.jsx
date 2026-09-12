import { Head, Link, useForm, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';
import { useState } from 'react';

const PROSPECTING_TOOLS = ['hunt_icp', 'run_outbound_pipeline'];

const INDUSTRIES = ['Technology', 'Healthcare', 'Finance', 'Real Estate', 'Legal', 'E-commerce', 'Manufacturing', 'Education', 'Hospitality', 'Professional Services'];
const COMPANY_SIZES = ['1-10', '11-50', '51-200', '201-500', '500+'];
const GEOGRAPHIES = ['United States', 'United Kingdom', 'Europe', 'Canada', 'Australia', 'Global'];
const JOB_TITLES = ['Founder', 'CEO', 'Owner', 'Director', 'VP', 'Manager'];

export default function Create({ templates, availableTools, knowledgeBases, personas }) {
    const { auth } = usePage().props;
    const isPlatformOwner = auth?.user?.roles?.includes('Platform Owner');

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        role: '',
        description: '',
        avatar: '🤖',
        system_instructions: '',
        personality: '',
        tone: 'professional',
        language: 'en',
        ai_model: 'deepseek-chat',
        temperature: 0.7,
        max_tool_calls: 5,
        max_context_messages: 20,
        business_knowledge_ids: [],
        enabled_tools: [],
        allowed_channels: ['web_chat'],
        working_hours: null,
        escalation_rules: null,
        campaign: {
            name: '',
            offer: '',
            buyer_persona_id: '',
            icp_industry: '',
            icp_company_size: '',
            icp_geography: '',
            icp_job_titles: '',
            daily_limit: 25,
            postal_address: '',
            compliance_regions: 'us, uk',
        },
    });

    const [step, setStep] = useState(1);
    const [selectedTemplate, setSelectedTemplate] = useState(null);
    const [setupCampaign, setSetupCampaign] = useState(false);

    const isProspecting = (data.enabled_tools || []).some((t) => PROSPECTING_TOOLS.includes(t));
    const steps = isProspecting
        ? ['Employee', 'Basics', 'Skills', 'Prospecting', 'Review']
        : ['Employee', 'Basics', 'Skills', 'Review'];
    const maxStep = steps.length;

    const applyTemplate = (template) => {
        setSelectedTemplate(template.id);
        setData({
            ...data,
            name: template.name,
            role: template.role,
            description: template.description,
            personality: template.personality,
            tone: template.tone,
            system_instructions: template.instructions ?? template.system_instructions ?? '',
            enabled_tools: template.tools ?? template.enabled_tools ?? [],
        });
        setStep(2);
    };

    const next = () => setStep((s) => Math.min(s + 1, maxStep));
    const back = () => setStep((s) => Math.max(s - 1, 1));

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/ai-employees');
    };

    const toggleTool = (id) => {
        const current = [...(data.enabled_tools || [])];
        setData('enabled_tools', current.includes(id) ? current.filter((t) => t !== id) : [...current, id]);
    };

    const toggleKnowledgeBase = (kbId) => {
        const current = [...(data.business_knowledge_ids || [])];
        setData('business_knowledge_ids', current.includes(kbId) ? current.filter((id) => id !== kbId) : [...current, kbId]);
    };

    const toggleChip = (field, value) => {
        const current = (data.campaign[field] || '').split(',').map((s) => s.trim()).filter(Boolean);
        const nextVal = current.includes(value) ? current.filter((v) => v !== value).join(', ') : [...current, value].join(', ');
        setData('campaign', { ...data.campaign, [field]: nextVal });
    };

    const setCampaign = (field, value) => {
        const patch = { ...data.campaign, [field]: value };
        if (field === 'offer') patch.name = value.trim();
        setData('campaign', patch);
    };

    const builtInTools = availableTools.filter((t) => !t.is_custom);
    const externalTools = availableTools.filter((t) => t.is_custom);

    const field = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none';
    const label = 'block text-sm font-medium text-gray-700 mb-1';
    const helper = 'text-xs text-gray-500 mb-1';

    const templateIcons = {
        sales: '💰',
        support: '🎧',
        receptionist: '📞',
        lead_qualifier: '🎯',
        ecommerce: '🛒',
        hr_assistant: '👥',
        booking_agent: '📅',
        tech_support: '🔧',
        sales_development_rep: '📈',
    };

    return (
        <TenantLayout header="Create AI Employee">
            <Head title="Create AI Employee" />
            <div className="max-w-3xl">
                {/* Step indicator */}
                <div className="mb-6 p-4 bg-white rounded-xl border border-gray-200">
                    <div className="flex items-center gap-2 flex-wrap">
                        {steps.map((s, i) => (
                            <div key={s} className="flex items-center gap-2">
                                <div className={`flex items-center justify-center w-7 h-7 rounded-full text-xs font-semibold ${i + 1 === step ? 'bg-blue-600 text-white' : i + 1 < step ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-400'}`}>
                                    {i + 1}
                                </div>
                                <span className={`text-xs font-medium ${i + 1 === step ? 'text-blue-700' : 'text-gray-400'}`}>{s}</span>
                                {i < steps.length - 1 && <div className="w-6 h-px bg-gray-200" />}
                            </div>
                        ))}
                    </div>
                </div>
                {/* Step 1 — Employee */}
                {step === 1 && (
                    <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-1">Choose an AI employee</h2>
                        <p className="text-sm text-gray-500 mb-4">Ready-made employees for common jobs. Pick one and we'll pre-fill its role, instructions, and tools — or build from scratch.</p>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            {templates.map((template) => (
                                <button key={template.id} onClick={() => applyTemplate(template)} className="text-left p-4 bg-white rounded-xl border border-blue-100 hover:border-blue-400 hover:shadow-lg transition-all group">
                                    <div className="flex items-center gap-2.5 mb-2">
                                        <span className="text-2xl shrink-0">{templateIcons[template.id] || '🤖'}</span>
                                        <div className="min-w-0">
                                            <h3 className="font-semibold text-gray-900 text-sm truncate">{template.name}</h3>
                                            <p className="text-[11px] font-medium text-blue-600 truncate">{template.role}</p>
                                        </div>
                                    </div>
                                    <p className="text-xs text-gray-500 line-clamp-3 leading-relaxed">{template.description}</p>
                                </button>
                            ))}
                            <button onClick={() => { setSelectedTemplate('custom'); setStep(2); }} className="text-left p-4 bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl border-2 border-dashed border-amber-400 hover:border-amber-500 transition-all group">
                                <div className="flex items-center gap-2.5 mb-2">
                                    <span className="text-2xl shrink-0">✨</span>
                                    <div className="min-w-0">
                                        <h3 className="font-semibold text-amber-900 text-sm truncate">Build from scratch</h3>
                                        <p className="text-[11px] font-medium text-amber-700 truncate">Custom employee</p>
                                    </div>
                                </div>
                                <p className="text-xs text-amber-700 leading-relaxed">Create your own employee with full control over personality, instructions, and tools.</p>
                            </button>
                        </div>
                    </div>
                )}
                {/* Step 2 — Basics */}
                {step === 2 && (
                    <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-6 space-y-5">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold text-gray-900">{selectedTemplate === 'custom' ? 'Custom AI Employee' : 'Configure your employee'}</h2>
                            <button type="button" onClick={() => setStep(1)} className="text-sm text-gray-500 hover:text-gray-700">← Change employee</button>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className={label}>Name *</label>
                                <input className={field} value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="e.g. Alex" required />
                                {errors.name && <p className="text-red-500 text-xs mt-1">{errors.name}</p>}
                            </div>
                            <div>
                                <label className={label}>Role *</label>
                                <input className={field} value={data.role} onChange={(e) => setData('role', e.target.value)} placeholder="e.g. Sales Development Rep" required />
                                {errors.role && <p className="text-red-500 text-xs mt-1">{errors.role}</p>}
                            </div>
                        </div>
                        <div>
                            <label className={label}>Avatar emoji</label>
                            <input type="text" value={data.avatar} onChange={(e) => setData('avatar', e.target.value)} className="w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm text-center text-xl outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label className={label}>Description</label>
                            <p className={helper}>Briefly explain what this employee does and when customers should interact with it.</p>
                            <textarea rows={2} className={field} value={data.description} onChange={(e) => setData('description', e.target.value)} placeholder="What does this AI employee do?" />
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className={label}>Personality</label>
                                <p className={helper}>How should your employee come across? (e.g. Friendly, Professional, Enthusiastic)</p>
                                <input className={field} value={data.personality} onChange={(e) => setData('personality', e.target.value)} placeholder="e.g. Friendly and helpful" />
                            </div>
                            <div>
                                <label className={label}>Tone</label>
                                <p className={helper}>The overall communication style.</p>
                                <select className={field} value={data.tone} onChange={(e) => setData('tone', e.target.value)}>
                                    <option value="professional">Professional</option>
                                    <option value="friendly">Friendly</option>
                                    <option value="casual">Casual</option>
                                    <option value="formal">Formal</option>
                                </select>
                            </div>
                        </div>
                        <WizardNav back={back} next={next} disabled={!data.name || !data.role} />
                    </div>
                )}

                {/* Step 3 — Skills */}
                {step === 3 && (
                    <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-6 space-y-5">
                        <h2 className="text-lg font-semibold text-gray-900">Skills & knowledge</h2>
                        <div>
                            <label className={label}>System instructions</label>
                            <p className={helper}>Detailed instructions that tell your employee how to behave and do its job. Templates fill this in for you.</p>
                            <textarea rows={8} className={`${field} font-mono text-xs`} value={data.system_instructions} onChange={(e) => setData('system_instructions', e.target.value)} placeholder="You are a..." />
                        </div>
                        {knowledgeBases.length > 0 && (
                            <div>
                                <label className={label}>Knowledge bases</label>
                                <p className={helper}>Give your employee access to your business knowledge to answer questions accurately.</p>
                                <div className="flex flex-wrap gap-2">
                                    {knowledgeBases.map((kb) => {
                                        const active = (data.business_knowledge_ids || []).includes(kb.id);
                                        return (
                                            <button key={kb.id} type="button" onClick={() => toggleKnowledgeBase(kb.id)} className={`px-3 py-1.5 rounded-full text-xs font-medium border ${active ? 'border-blue-400 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600 hover:border-blue-300'}`}>
                                                {kb.name} {active && '✓'}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                        <div>
                            <label className={label}>Tools</label>
                            <p className={helper}>What your employee is allowed to do. Hover over a tool to see what it does.</p>
                            {availableTools.length === 0 && (
                                <div className="p-4 bg-yellow-50 border border-yellow-100 rounded-lg">
                                    <p className="text-xs text-yellow-700">⚠️ No tools available. Run <code className="bg-yellow-100 px-1 py-0.5 rounded">php artisan db:seed --class=ToolsTableSeeder</code> to create defaults.</p>
                                </div>
                            )}
                            <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                                {builtInTools.map((tool) => {
                                    const active = (data.enabled_tools || []).includes(tool.identifier);
                                    return (
                                        <button key={tool.id} type="button" onClick={() => toggleTool(tool.identifier)} title={tool.description} className={`text-left p-2.5 rounded-lg border text-xs transition-all ${active ? 'border-blue-400 bg-blue-50 text-blue-700 shadow-sm' : 'border-gray-200 text-gray-600 hover:border-blue-300'}`}>
                                            <span className="font-medium">{tool.name || tool.identifier}</span>
                                            {active && <span className="ml-1 text-blue-600">✓</span>}
                                        </button>
                                    );
                                })}
                            </div>
                            {externalTools.length > 0 && (
                                <div className="mt-3">
                                    <p className="text-xs font-medium text-gray-600 mb-2">External tools (MCP)</p>
                                    <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                                        {externalTools.filter((t) => t.identifier).map((tool) => {
                                            const active = (data.enabled_tools || []).includes(tool.identifier);
                                            return (
                                                <button key={tool.id} type="button" onClick={() => toggleTool(tool.identifier)} title={tool.description} className={`text-left p-2.5 rounded-lg border text-xs transition-all ${active ? 'border-blue-400 bg-blue-50 text-blue-700 shadow-sm' : 'border-gray-200 text-gray-600 hover:border-blue-300'}`}>
                                                    <span className="font-medium">{tool.name || tool.identifier}</span>
                                                    <span className="ml-1 px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 text-[10px] font-medium">MCP</span>
                                                    {active && <span className="ml-1 text-blue-600">✓</span>}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </div>
                        <WizardNav back={back} next={next} />
                    </div>
                )}
                {/* Step 4 — Prospecting (conditional) */}
                {isProspecting && step === 4 && (
                    <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-6 space-y-5">
                        <h2 className="text-lg font-semibold text-gray-900">🎯 Set up your first prospecting campaign</h2>
                        <p className="text-sm text-gray-500">Your SDR needs to know who to reach out to and what you're selling. This is optional — you can skip it and add campaigns later.</p>

                        {!setupCampaign ? (
                            <div className="flex flex-col sm:flex-row gap-3">
                                <button type="button" onClick={() => setSetupCampaign(true)} className="px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Set up a campaign now</button>
                                <button type="button" onClick={() => next()} className="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">Skip for now</button>
                            </div>
                        ) : (
                            <>
                                <div>
                                    <label className={label}>What are you selling?</label>
                                    <p className={helper}>Your one-line value proposition — this becomes the campaign name. e.g. "Professional websites for dental clinics".</p>
                                    <input className={field} value={data.campaign.offer} onChange={(e) => setCampaign('offer', e.target.value)} placeholder="e.g. Professional websites for dental clinics" />
                                </div>

                                {(personas || []).length > 0 && (
                                    <div>
                                        <label className={label}>Buyer persona (optional)</label>
                                        <p className={helper}>Target a specific decision-maker persona to sharpen your outreach.</p>
                                        <select className={field} value={data.campaign.buyer_persona_id} onChange={(e) => setCampaign('buyer_persona_id', e.target.value)}>
                                            <option value="">None — use ICP only</option>
                                            {personas.map((p) => <option key={p.id} value={p.id}>{p.avatar || '🧑‍💼'} {p.name}</option>)}
                                        </select>
                                    </div>
                                )}

                                <div>
                                    <label className={label}>Who is your ideal customer?</label>
                                    <p className={helper}>The type of company most likely to buy from you. We'll use this to find matching prospects.</p>
                                    <div className="space-y-4 mt-2">
                                        <ChipGroup label="Industry" values={INDUSTRIES} selected={data.campaign.icp_industry} onToggle={(v) => toggleChip('icp_industry', v)} />
                                        <ChipGroup label="Company size" values={COMPANY_SIZES} selected={data.campaign.icp_company_size} onToggle={(v) => toggleChip('icp_company_size', v)} />
                                        <ChipGroup label="Location" values={GEOGRAPHIES} selected={data.campaign.icp_geography} onToggle={(v) => toggleChip('icp_geography', v)} />
                                        <ChipGroup label="Who you want to reach (job title)" values={JOB_TITLES} selected={data.campaign.icp_job_titles} onToggle={(v) => toggleChip('icp_job_titles', v)} />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className={label}>Emails per day</label>
                                        <p className={helper}>How many cold emails your AI sends daily. Start low (10–25) to protect your email reputation.</p>
                                        <input type="number" min={1} max={500} className={field} value={data.campaign.daily_limit} onChange={(e) => setCampaign('daily_limit', parseInt(e.target.value) || 25)} />
                                    </div>
                                    <div>
                                        <label className={label}>Where are you based?</label>
                                        <p className={helper}>Your business address — required for email compliance (CAN-SPAM).</p>
                                        <input className={field} value={data.campaign.postal_address} onChange={(e) => setCampaign('postal_address', e.target.value)} placeholder="e.g. 123 Main St, Austin, TX" />
                                    </div>
                                </div>

                                <WizardNav back={back} next={next} />
                            </>
                        )}
                    </div>
                )}

                {/* Final step — Review */}
                {step === maxStep && (
                    <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-6 space-y-5">
                        <h2 className="text-lg font-semibold text-gray-900">Review & create</h2>

                        <div className="p-4 bg-gray-50 rounded-lg border border-gray-100 text-sm space-y-1">
                            <p><span className="font-medium text-gray-700">Name:</span> {data.name || '—'}</p>
                            <p><span className="font-medium text-gray-700">Role:</span> {data.role || '—'}</p>
                            <p><span className="font-medium text-gray-700">Tools:</span> {(data.enabled_tools || []).length} enabled</p>
                            {isProspecting && (
                                <p><span className="font-medium text-gray-700">Prospecting:</span> {data.campaign.offer ? `Selling "${data.campaign.offer}"` : 'Skipped — you can add a campaign later'}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {isPlatformOwner && (
                                <div>
                                    <label className={label}>AI model</label>
                                    <select className={field} value={data.ai_model} onChange={(e) => setData('ai_model', e.target.value)}>
                                        <option value="deepseek-chat">DeepSeek Chat</option>
                                        <option value="deepseek-reasoner">DeepSeek Reasoner</option>
                                    </select>
                                </div>
                            )}
                            {isPlatformOwner && (
                                <div>
                                    <label className={label}>Temperature ({data.temperature})</label>
                                    <input type="range" min="0" max="2" step="0.1" value={data.temperature} onChange={(e) => setData('temperature', parseFloat(e.target.value))} className="w-full" />
                                </div>
                            )}
                            <div>
                                <label className={label}>Max context messages</label>
                                <input type="number" min={5} max={100} className={field} value={data.max_context_messages} onChange={(e) => setData('max_context_messages', parseInt(e.target.value))} />
                            </div>
                            <div>
                                <label className={label}>Max tool calls</label>
                                <input type="number" min={1} max={20} className={field} value={data.max_tool_calls} onChange={(e) => setData('max_tool_calls', parseInt(e.target.value))} />
                            </div>
                        </div>

                        <div className="flex items-center gap-3 pt-3">
                            <button type="button" onClick={handleSubmit} disabled={processing} className="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-medium hover:from-blue-700 hover:to-blue-800 disabled:opacity-50 transition-all shadow-md shadow-blue-200 text-sm">
                                {processing ? 'Creating…' : 'Create AI Employee'}
                            </button>
                            <button type="button" onClick={back} className="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors text-sm">Back</button>
                            <Link href="/ai-employees" className="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors text-sm">Cancel</Link>
                        </div>
                    </div>
                )}
            </div>
        </TenantLayout>
    );
}
function WizardNav({ back, next, disabled }) {
    return (
        <div className="flex items-center justify-between pt-4 border-t border-gray-100">
            <button type="button" onClick={back} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">Back</button>
            <button type="button" onClick={next} disabled={disabled} className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50">Continue</button>
        </div>
    );
}

function ChipGroup({ label, values, selected, onToggle }) {
    const active = (selected || '').split(',').map((s) => s.trim()).filter(Boolean);
    return (
        <div>
            <p className="text-xs font-medium text-gray-600 mb-1.5">{label}</p>
            <div className="flex flex-wrap gap-2">
                {values.map((v) => {
                    const isOn = active.includes(v);
                    return (
                        <button key={v} type="button" onClick={() => onToggle(v)} className={`px-3 py-1.5 rounded-full text-xs font-medium border transition-colors ${isOn ? 'border-blue-400 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600 hover:border-blue-300'}`}>
                            {v} {isOn && '✓'}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
