import { Head, Link, useForm, usePage } from '@inertiajs/react';
import TenantLayout from '@/Layouts/TenantLayout';

export default function Edit({ employee, availableTools, knowledgeBases }) {
    const { auth } = usePage().props;
    const isPlatformOwner = auth?.user?.roles?.includes('Platform Owner');

    const { data, setData, put, processing, errors } = useForm({
        name: employee.name || '',
        role: employee.role || '',
        description: employee.description || '',
        avatar: employee.avatar || '🤖',
        system_instructions: employee.system_instructions || '',
        personality: employee.personality || '',
        tone: employee.tone || 'professional',
        language: employee.language || 'en',
        ai_model: employee.ai_model || 'deepseek-chat',
        temperature: employee.temperature ?? 0.7,
        max_tool_calls: employee.max_tool_calls ?? 5,
        max_context_messages: employee.max_context_messages ?? 20,
        is_active: employee.is_active ?? false,
        business_knowledge_ids: employee.business_knowledge_ids || [],
        enabled_tools: (employee.tools || []).map(t => t.identifier || t.id),
        allowed_channels: employee.allowed_channels || ['web_chat'],
        working_hours: employee.working_hours || null,
        escalation_rules: employee.escalation_rules || null,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/ai-employees/${employee.id}`);
    };

    const toggleTool = (toolIdentifier) => {
        const current = [...data.enabled_tools];
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
        <TenantLayout header="Edit AI Employee">
            <Head title={`Edit ${employee.name}`} />

            <div className="max-w-3xl">
                <form onSubmit={handleSubmit}>
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold text-gray-900">Edit {employee.name}</h2>
                            <Link href="/ai-employees" className="text-sm text-gray-500 hover:text-gray-700">
                                ← Back to AI Employees
                            </Link>
                        </div>

                        {/* Basic Info */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                <input type="text" value={data.name} onChange={(e) => setData('name', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none" required />
                                {errors.name && <p className="text-red-500 text-xs mt-1">{errors.name}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                                <input type="text" value={data.role} onChange={(e) => setData('role', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none" required />
                                {errors.role && <p className="text-red-500 text-xs mt-1">{errors.role}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Avatar Emoji</label>
                            <input type="text" value={data.avatar} onChange={(e) => setData('avatar', e.target.value)}
                                className="w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none text-center text-xl" />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea value={data.description} onChange={(e) => setData('description', e.target.value)}
                                rows={2} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                                placeholder="What does this AI employee do?" />
                        </div>

                        {/* Personality & Tone */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Personality</label>
                                <input type="text" value={data.personality} onChange={(e) => setData('personality', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                                    placeholder="e.g., Friendly and helpful" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Tone</label>
                                <select value={data.tone} onChange={(e) => setData('tone', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                                    <option value="professional">Professional</option>
                                    <option value="friendly">Friendly</option>
                                    <option value="casual">Casual</option>
                                    <option value="formal">Formal</option>
                                </select>
                            </div>
                        </div>

                        {/* Model Config */}
                        <div className={`grid grid-cols-1 ${isPlatformOwner ? 'md:grid-cols-5' : 'md:grid-cols-3'} gap-4`}>
                            {isPlatformOwner && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">AI Model</label>
                                    <select value={data.ai_model} onChange={(e) => setData('ai_model', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                                        <option value="deepseek-chat">DeepSeek Chat</option>
                                        <option value="deepseek-reasoner">DeepSeek Reasoner</option>
                                    </select>
                                </div>
                            )}
                            {isPlatformOwner && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Temperature ({data.temperature})</label>
                                    <input type="range" min="0" max="2" step="0.1" value={data.temperature}
                                        onChange={(e) => setData('temperature', parseFloat(e.target.value))} className="w-full" />
                                </div>
                            )}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Max Context Messages</label>
                                <input type="number" value={data.max_context_messages}
                                    onChange={(e) => setData('max_context_messages', parseInt(e.target.value))}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none" min={5} max={100} />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Max Tool Calls</label>
                                <input type="number" value={data.max_tool_calls}
                                    onChange={(e) => setData('max_tool_calls', parseInt(e.target.value))}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none" min={1} max={20} />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Active</label>
                                <div className="flex items-center gap-3 mt-2">
                                    <input type="checkbox" checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                        className="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                    <span className="text-sm text-gray-600">{data.is_active ? 'Active' : 'Inactive'}</span>
                                </div>
                            </div>
                        </div>

                        {/* System Instructions */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">System Instructions</label>
                            <textarea value={data.system_instructions} onChange={(e) => setData('system_instructions', e.target.value)}
                                rows={5} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none font-mono"
                                placeholder="Detailed instructions for how this AI employee should behave..." />
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

                            <div className="mb-4">
                                <h4 className="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Built-in tools</h4>
                                <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                                    {builtInTools.filter(t => t.identifier).map((tool) => {
                                        const isSelected = data.enabled_tools.includes(tool.identifier);
                                        return (
                                            <button key={tool.id} type="button" onClick={() => toggleTool(tool.identifier)}
                                                className={`text-left p-2.5 rounded-lg border text-xs transition-all ${
                                                    isSelected ? 'border-primary-300 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                                }`}>
                                                <span className="font-medium">{tool.name || tool.identifier}</span>
                                                {isSelected && <span className="ml-1 text-primary-500">✓</span>}
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
                                            const isSelected = data.enabled_tools.includes(tool.identifier);
                                            return (
                                                <button key={tool.id} type="button" onClick={() => toggleTool(tool.identifier)}
                                                    className={`text-left p-2.5 rounded-lg border text-xs transition-all ${
                                                        isSelected ? 'border-primary-300 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                                    }`}>
                                                    <span className="font-medium">{tool.name || tool.identifier}</span>
                                                    <span className="ml-1 px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 text-[10px] font-medium">MCP</span>
                                                    {isSelected && <span className="ml-1 text-primary-500">✓</span>}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {availableTools.length === 0 && (
                                <p className="text-xs text-gray-400">No tools available.</p>
                            )}
                        </div>
                    </div>

                    {/* Submit */}
                    <div className="flex items-center gap-3 mt-6">
                        <button type="submit" disabled={processing}
                            className="px-5 py-2.5 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 disabled:opacity-50 transition-colors text-sm">
                            {processing ? 'Saving...' : 'Save Changes'}
                        </button>
                        <Link href="/ai-employees"
                            className="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors text-sm">
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </TenantLayout>
    );
}