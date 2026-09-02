import { Head, Link, useForm, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';
import { useState } from 'react';

export default function Create({ templates, availableTools, knowledgeBases }) {
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
    });

    const [selectedTemplate, setSelectedTemplate] = useState(null);

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
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/ai-employees');
    };

    const toggleTool = (toolIdentifier) => {
        const current = [...(data.enabled_tools || [])];
        if (current.includes(toolIdentifier)) {
            setData('enabled_tools', current.filter(t => t !== toolIdentifier));
        } else {
            setData('enabled_tools', [...current, toolIdentifier]);
        }
    };

    const toggleKnowledgeBase = (kbId) => {
        const current = [...(data.business_knowledge_ids || [])];
        if (current.includes(kbId)) {
            setData('business_knowledge_ids', current.filter(id => id !== kbId));
        } else {
            setData('business_knowledge_ids', [...current, kbId]);
        }
    };

    const builtInTools = availableTools.filter(t => !t.is_custom);
    const externalTools = availableTools.filter(t => t.is_custom);

    return (
        <TenantLayout header="Create AI Employee">
            <Head title="Create AI Employee" />

            <div className="max-w-3xl">
                {/* Helpful info banner */}
                <div className="mb-6 p-4 bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-xl shadow-sm">
                    <h3 className="text-sm font-semibold text-blue-900 mb-1">💡 Creating Your AI Employee</h3>
                    <p className="text-xs text-blue-700">
                        An AI Employee is a smart assistant that can handle customer conversations automatically. 
                        Choose a template below to get started quickly, or build a custom one from scratch. 
                        After creation, you'll need to <strong>activate</strong> it and <strong>connect channels</strong> (like web chat) before it can start working.
                    </p>
                </div>

                {/* Template Selection */}
                {!selectedTemplate && (
                    <div className="mb-8">
                        <h2 className="text-lg font-semibold text-gray-900 mb-3">Choose a Template</h2>
                        <p className="text-sm text-gray-500 mb-4">Start with a pre-configured AI employee template designed for specific business needs, or build a custom one from scratch.</p>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                            {templates.map((template) => {
                                const icons = {
                                    'sales': '💰',
                                    'support': '🎧',
                                    'receptionist': '📞',
                                    'lead_qualifier': '🎯',
                                    'ecommerce': '🛒',
                                    'hr_assistant': '👥',
                                    'booking_agent': '📅',
                                    'tech_support': '🔧',
                                };
                                return (
                                    <button
                                        key={template.id}
                                        onClick={() => applyTemplate(template)}
                                        className="text-left p-4 bg-white/80 backdrop-blur rounded-xl border border-blue-100 hover:border-blue-400 hover:shadow-lg hover:scale-105 transition-all group"
                                    >
                                        <div className="text-3xl mb-2 group-hover:scale-110 transition-transform">
                                            {icons[template.id] || '🤖'}
                                        </div>
                                        <h3 className="font-semibold text-gray-900 text-sm mb-1">{template.name}</h3>
                                        <p className="text-xs text-gray-500 line-clamp-3 leading-relaxed">{template.description}</p>
                                    </button>
                                );
                            })}
                            <button
                                onClick={() => setSelectedTemplate('custom')}
                                className="text-left p-4 bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl border-2 border-dashed border-amber-400 hover:border-amber-500 hover:shadow-lg hover:scale-105 transition-all group"
                            >
                                <div className="text-3xl mb-2 group-hover:scale-110 transition-transform">✨</div>
                                <h3 className="font-semibold text-amber-900 text-sm mb-1">Custom Build</h3>
                                <p className="text-xs text-amber-700 leading-relaxed">Build from scratch with complete control over personality, instructions, and tools.</p>
                            </button>
                        </div>
                    </div>
                )}

                {/* Creation Form */}
                {selectedTemplate && (
                    <form onSubmit={handleSubmit}>
                        <div className="bg-white/80 backdrop-blur rounded-xl shadow-lg border border-blue-100 p-6 space-y-5">
                            <div className="flex items-center justify-between">
                                <h2 className="text-lg font-semibold text-gray-900">
                                    {selectedTemplate === 'custom' ? 'Custom AI Employee' : 'Configure AI Employee'}
                                </h2>
                                <button
                                    type="button"
                                    onClick={() => setSelectedTemplate(null)}
                                    className="text-sm text-gray-500 hover:text-gray-700"
                                >
                                    ← Change Template
                                </button>
                            </div>

                            {/* Basic Info */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                                        placeholder="e.g., Sales Assistant"
                                        required
                                    />
                                    {errors.name && <p className="text-red-500 text-xs mt-1">{errors.name}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                                    <input
                                        type="text"
                                        value={data.role}
                                        onChange={(e) => setData('role', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                                        placeholder="e.g., Sales Representative"
                                        required
                                    />
                                    {errors.role && <p className="text-red-500 text-xs mt-1">{errors.role}</p>}
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Avatar Emoji</label>
                                <input
                                    type="text"
                                    value={data.avatar}
                                    onChange={(e) => setData('avatar', e.target.value)}
                                    className="w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none text-center text-xl"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <p className="text-xs text-gray-500 mb-1">Briefly explain what this AI employee does and when customers should interact with it.</p>
                                <textarea
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={2}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                                    placeholder="What does this AI employee do?"
                                />
                            </div>

                            {/* Personality & Tone */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Personality</label>
                                    <p className="text-xs text-gray-500 mb-1">How should your AI employee come across? (e.g., Friendly, Professional, Enthusiastic)</p>
                                    <input
                                        type="text"
                                        value={data.personality}
                                        onChange={(e) => setData('personality', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                                        placeholder="e.g., Friendly and helpful"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Tone</label>
                                    <p className="text-xs text-gray-500 mb-1">The overall communication style</p>
                                    <select
                                        value={data.tone}
                                        onChange={(e) => setData('tone', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                                    >
                                        <option value="professional">Professional</option>
                                        <option value="friendly">Friendly</option>
                                        <option value="casual">Casual</option>
                                        <option value="formal">Formal</option>
                                    </select>
                                </div>
                            </div>

                            {/* Model Config */}
                            <div className={`grid grid-cols-1 ${isPlatformOwner ? 'md:grid-cols-4' : 'md:grid-cols-2'} gap-4`}>
                                {isPlatformOwner && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">AI Model</label>
                                        <select
                                            value={data.ai_model}
                                            onChange={(e) => setData('ai_model', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                                        >
                                            <option value="deepseek-chat">DeepSeek Chat</option>
                                            <option value="deepseek-reasoner">DeepSeek Reasoner</option>
                                        </select>
                                    </div>
                                )}
                                {isPlatformOwner && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Temperature ({data.temperature})</label>
                                        <input
                                            type="range"
                                            min="0"
                                            max="2"
                                            step="0.1"
                                            value={data.temperature}
                                            onChange={(e) => setData('temperature', parseFloat(e.target.value))}
                                            className="w-full"
                                        />
                                    </div>
                                )}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Max Context Messages</label>
                                    <input
                                        type="number"
                                        value={data.max_context_messages}
                                        onChange={(e) => setData('max_context_messages', parseInt(e.target.value))}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                                        min={5}
                                        max={100}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Max Tool Calls</label>
                                    <input
                                        type="number"
                                        value={data.max_tool_calls}
                                        onChange={(e) => setData('max_tool_calls', parseInt(e.target.value))}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                                        min={1}
                                        max={20}
                                    />
                                </div>
                            </div>

                            {/* System Instructions */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">System Instructions</label>
                                <p className="text-xs text-gray-500 mb-1">Detailed guidelines for how this AI should behave, what it should prioritize, and any specific rules it must follow.</p>
                                <textarea
                                    value={data.system_instructions}
                                    onChange={(e) => setData('system_instructions', e.target.value)}
                                    rows={5}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none font-mono"
                                    placeholder="Detailed instructions for how this AI employee should behave..."
                                />
                                <p className="text-xs text-gray-400 mt-1">💡 Tip: Be specific about what the AI should and shouldn't do.</p>
                            </div>

                            {/* Knowledge Bases */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Knowledge Access
                                    <span className="ml-2 text-xs font-normal text-gray-500">(Optional)</span>
                                </label>
                                <p className="text-xs text-gray-500 mb-3">
                                    Choose which knowledge bases this AI can access. Leave empty to allow access to all organizational knowledge.
                                </p>
                                {knowledgeBases && knowledgeBases.length > 0 ? (
                                    <>
                                        <div className="space-y-2">
                                            {knowledgeBases.map((kb) => {
                                                const isSelected = (data.business_knowledge_ids || []).includes(kb.id);
                                                return (
                                                    <button
                                                        key={kb.id}
                                                        type="button"
                                                        onClick={() => toggleKnowledgeBase(kb.id)}
                                                        className={`w-full text-left p-3 rounded-lg border transition-all ${
                                                            isSelected
                                                                ? 'border-blue-400 bg-blue-50 shadow-sm'
                                                                : 'border-gray-200 hover:border-blue-300'
                                                        }`}
                                                    >
                                                        <div className="flex items-center justify-between">
                                                            <div className="flex-1">
                                                                <span className={`text-sm font-medium ${isSelected ? 'text-blue-900' : 'text-gray-900'}`}>
                                                                    📚 {kb.name}
                                                                </span>
                                                                {kb.description && (
                                                                    <p className={`text-xs mt-1 ${isSelected ? 'text-blue-700' : 'text-gray-500'}`}>
                                                                        {kb.description}
                                                                    </p>
                                                                )}
                                                            </div>
                                                            {isSelected && <span className="text-blue-600 font-bold">✓</span>}
                                                        </div>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                        <p className="text-xs text-gray-400 mt-2">
                                            {data.business_knowledge_ids.length === 0 
                                                ? '💡 No knowledge bases selected. This employee will have access to all organizational knowledge.'
                                                : `✅ ${data.business_knowledge_ids.length} knowledge base(s) selected.`
                                            }
                                        </p>
                                    </>
                                ) : (
                                    <div className="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                        <p className="text-xs text-gray-600">
                                            📚 No knowledge bases created yet. <Link href="/knowledge" className="text-blue-600 hover:underline">Create knowledge bases</Link> to provide this AI with specific documentation to reference.
                                        </p>
                                    </div>
                                )}
                            </div>

                            {/* Available Tools */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Available Tools</label>
                                <p className="text-xs text-gray-500 mb-3">Select which actions this AI employee can perform. Tools allow the AI to search products, create orders, schedule appointments, and more.</p>

                                <div className="mb-4">
                                    <h4 className="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Built-in tools</h4>
                                    <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                                        {builtInTools.filter(t => t.identifier).map((tool) => {
                                            const isSelected = (data.enabled_tools || []).includes(tool.identifier);
                                            return (
                                                <button
                                                    key={tool.id}
                                                    type="button"
                                                    onClick={() => toggleTool(tool.identifier)}
                                                    title={tool.description}
                                                    className={`text-left p-2.5 rounded-lg border text-xs transition-all ${
                                                        isSelected
                                                            ? 'border-blue-400 bg-blue-50 text-blue-700 shadow-sm'
                                                            : 'border-gray-200 text-gray-600 hover:border-blue-300'
                                                    }`}
                                                >
                                                    <span className="font-medium">{tool.name || tool.identifier}</span>
                                                    {isSelected && <span className="ml-1 text-blue-600">✓</span>}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>

                                {externalTools.length > 0 && (
                                    <div>
                                        <h4 className="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">External tools (MCP)</h4>
                                        <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                                            {externalTools.filter(t => t.identifier).map((tool) => {
                                                const isSelected = (data.enabled_tools || []).includes(tool.identifier);
                                                return (
                                                    <button
                                                        key={tool.id}
                                                        type="button"
                                                        onClick={() => toggleTool(tool.identifier)}
                                                        title={tool.description}
                                                        className={`text-left p-2.5 rounded-lg border text-xs transition-all ${
                                                            isSelected
                                                                ? 'border-blue-400 bg-blue-50 text-blue-700 shadow-sm'
                                                                : 'border-gray-200 text-gray-600 hover:border-blue-300'
                                                        }`}
                                                    >
                                                        <span className="font-medium">{tool.name || tool.identifier}</span>
                                                        <span className="ml-1 px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 text-[10px] font-medium">MCP</span>
                                                        {isSelected && <span className="ml-1 text-blue-600">✓</span>}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                                {availableTools.length === 0 && (
                                    <div className="p-4 bg-yellow-50 border border-yellow-100 rounded-lg">
                                        <p className="text-xs text-yellow-700">⚠️ No tools available. Please run database seeder to create default tools: <code className="bg-yellow-100 px-1 py-0.5 rounded">php artisan db:seed --class=ToolsTableSeeder</code></p>
                                    </div>
                                )}
                                {availableTools.length > 0 && (
                                    <p className="text-xs text-gray-400 mt-2">💡 Tip: Hover over each tool to see what it does.</p>
                                )}
                            </div>
                        </div>

                        {/* Submit */}
                        <div className="flex items-center gap-3 mt-6">
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-medium hover:from-blue-700 hover:to-blue-800 disabled:opacity-50 transition-all shadow-md shadow-blue-200 text-sm"
                            >
                                {processing ? 'Creating...' : 'Create AI Employee'}
                            </button>
                            <Link
                                href="/ai-employees"
                                className="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors text-sm"
                            >
                                Cancel
                            </Link>
                        </div>
                    </form>
                )}
            </div>
        </TenantLayout>
    );
}