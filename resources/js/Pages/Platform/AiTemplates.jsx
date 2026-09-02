import { usePage, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function AiTemplates({ templates, availableTools }) {
    const { flash } = usePage().props;
    const [editingTemplate, setEditingTemplate] = useState(null);
    const [showCreateForm, setShowCreateForm] = useState(false);

    const createForm = useForm({ 
        name: '', 
        role: '', 
        description: '', 
        system_instructions: '', 
        personality: '', 
        tone: 'professional', 
        template_type: '', 
        enabled_tools: [] 
    });

    const editForm = useForm({ 
        name: '', 
        role: '', 
        description: '', 
        system_instructions: '', 
        personality: '', 
        tone: '', 
        template_type: '', 
        enabled_tools: [] 
    });

    const handleEdit = (template) => {
        setEditingTemplate(template);
        editForm.setData({
            name: template.name || '',
            role: template.role || '',
            description: template.description || '',
            system_instructions: template.system_instructions || '',
            personality: template.personality || '',
            tone: template.tone || 'professional',
            template_type: template.template_type || '',
            enabled_tools: template.enabled_tools || []
        });
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        editForm.put(`/platform/templates/${editingTemplate.id}`, {
            onSuccess: () => {
                setEditingTemplate(null);
                editForm.reset();
            }
        });
    };

    const handleDelete = (template) => {
        if (confirm(`Are you sure you want to delete the template "${template.name}"?`)) {
            router.delete(`/platform/templates/${template.id}`);
        }
    };

    const handleToggle = (template) => {
        router.post(`/platform/templates/${template.id}/toggle`);
    };

    const handleCreate = (e) => {
        e.preventDefault();
        createForm.post('/platform/templates', {
            onSuccess: () => {
                createForm.reset();
                setShowCreateForm(false);
            }
        });
    };

    const toggleTool = (form, identifier) => {
        const current = form.data.enabled_tools || [];
        if (current.includes(identifier)) {
            form.setData('enabled_tools', current.filter(t => t !== identifier));
        } else {
            form.setData('enabled_tools', [...current, identifier]);
        }
    };

    return (
        <PlatformLayout title="AI Templates">
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">AI Employee Templates</h1>
                    <p className="text-sm text-gray-500 mt-1">Manage reusable AI employee templates for tenants</p>
                </div>
                <button 
                    onClick={() => setShowCreateForm(!showCreateForm)}
                    className="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700"
                >
                    {showCreateForm ? 'Cancel' : '+ Create Template'}
                </button>
            </div>

            {flash?.success && (
                <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg text-sm">
                    {flash.success}
                </div>
            )}
            {flash?.error && (
                <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-lg text-sm">
                    {flash.error}
                </div>
            )}

            {/* Create Form */}
            {showCreateForm && (
                <form onSubmit={handleCreate} className="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="font-semibold text-gray-900 mb-4">Create New Template</h3>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Template Name *</label>
                            <input 
                                value={createForm.data.name} 
                                onChange={e => createForm.setData('name', e.target.value)} 
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" 
                                placeholder="e.g., Sales Representative" 
                                required 
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                            <input 
                                value={createForm.data.role} 
                                onChange={e => createForm.setData('role', e.target.value)} 
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" 
                                placeholder="e.g., Sales Representative" 
                                required 
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Template Type</label>
                            <input 
                                value={createForm.data.template_type} 
                                onChange={e => createForm.setData('template_type', e.target.value)} 
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" 
                                placeholder="e.g., sales, support, hr" 
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Tone</label>
                            <select 
                                value={createForm.data.tone} 
                                onChange={e => createForm.setData('tone', e.target.value)} 
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                            >
                                <option value="professional">Professional</option>
                                <option value="friendly">Friendly</option>
                                <option value="casual">Casual</option>
                                <option value="formal">Formal</option>
                            </select>
                        </div>
                        <div className="col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-1">Personality</label>
                            <input 
                                value={createForm.data.personality} 
                                onChange={e => createForm.setData('personality', e.target.value)} 
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" 
                                placeholder="e.g., Enthusiastic, helpful, and proactive" 
                            />
                        </div>
                        <div className="col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea 
                                value={createForm.data.description} 
                                onChange={e => createForm.setData('description', e.target.value)} 
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" 
                                placeholder="Brief description of what this template does" 
                                rows={2} 
                            />
                        </div>
                        <div className="col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-1">System Instructions</label>
                            <textarea 
                                value={createForm.data.system_instructions} 
                                onChange={e => createForm.setData('system_instructions', e.target.value)} 
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono" 
                                placeholder="Detailed instructions for the AI employee's behavior and capabilities" 
                                rows={5} 
                            />
                        </div>
                        <div className="col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-2">Tools</label>
                            <p className="text-xs text-gray-500 mb-3">Select which tools this template enables for the AI employee.</p>
                            {(availableTools || []).length > 0 ? (
                                <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                                    {(availableTools || []).map((tool) => {
                                        const isSelected = (createForm.data.enabled_tools || []).includes(tool.identifier);
                                        return (
                                            <button
                                                key={tool.id}
                                                type="button"
                                                onClick={() => toggleTool(createForm, tool.identifier)}
                                                title={tool.description}
                                                className={`text-left p-2.5 rounded-lg border text-xs transition-all ${
                                                    isSelected ? 'border-purple-400 bg-purple-50 text-purple-700' : 'border-gray-200 text-gray-600 hover:border-purple-300'
                                                }`}
                                            >
                                                <span className="font-medium">{tool.name || tool.identifier}</span>
                                                {isSelected && <span className="ml-1 text-purple-600">✓</span>}
                                            </button>
                                        );
                                    })}
                                </div>
                            ) : (
                                <p className="text-xs text-gray-400">No tools available.</p>
                            )}
                        </div>
                    </div>
                    <div className="flex justify-end gap-2 mt-4">
                        <button 
                            type="button"
                            onClick={() => setShowCreateForm(false)}
                            className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            disabled={createForm.processing} 
                            className="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 disabled:opacity-50"
                        >
                            {createForm.processing ? 'Creating...' : 'Create Template'}
                        </button>
                    </div>
                </form>
            )}

            {/* Edit Modal */}
            {editingTemplate && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                        <form onSubmit={handleUpdate} className="p-6">
                            <h3 className="font-semibold text-gray-900 text-xl mb-4">Edit Template</h3>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Template Name *</label>
                                    <input 
                                        value={editForm.data.name} 
                                        onChange={e => editForm.setData('name', e.target.value)} 
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" 
                                        required 
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                                    <input 
                                        value={editForm.data.role} 
                                        onChange={e => editForm.setData('role', e.target.value)} 
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" 
                                        required 
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Template Type</label>
                                    <input 
                                        value={editForm.data.template_type} 
                                        onChange={e => editForm.setData('template_type', e.target.value)} 
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" 
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Tone</label>
                                    <select 
                                        value={editForm.data.tone} 
                                        onChange={e => editForm.setData('tone', e.target.value)} 
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                    >
                                        <option value="professional">Professional</option>
                                        <option value="friendly">Friendly</option>
                                        <option value="casual">Casual</option>
                                        <option value="formal">Formal</option>
                                    </select>
                                </div>
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Personality</label>
                                    <input 
                                        value={editForm.data.personality} 
                                        onChange={e => editForm.setData('personality', e.target.value)} 
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" 
                                    />
                                </div>
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea 
                                        value={editForm.data.description} 
                                        onChange={e => editForm.setData('description', e.target.value)} 
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" 
                                        rows={2} 
                                    />
                                </div>
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">System Instructions</label>
                                    <textarea 
                                        value={editForm.data.system_instructions} 
                                        onChange={e => editForm.setData('system_instructions', e.target.value)} 
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono" 
                                        rows={5} 
                                    />
                                </div>
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Tools</label>
                                    <p className="text-xs text-gray-500 mb-3">Select which tools this template enables for the AI employee.</p>
                                    {(availableTools || []).length > 0 ? (
                                        <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                                            {(availableTools || []).map((tool) => {
                                                const isSelected = (editForm.data.enabled_tools || []).includes(tool.identifier);
                                                return (
                                                    <button
                                                        key={tool.id}
                                                        type="button"
                                                        onClick={() => toggleTool(editForm, tool.identifier)}
                                                        title={tool.description}
                                                        className={`text-left p-2.5 rounded-lg border text-xs transition-all ${
                                                            isSelected ? 'border-purple-400 bg-purple-50 text-purple-700' : 'border-gray-200 text-gray-600 hover:border-purple-300'
                                                        }`}
                                                    >
                                                        <span className="font-medium">{tool.name || tool.identifier}</span>
                                                        {isSelected && <span className="ml-1 text-purple-600">✓</span>}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    ) : (
                                        <p className="text-xs text-gray-400">No tools available.</p>
                                    )}
                                </div>
                            </div>
                            <div className="flex justify-end gap-2 mt-6">
                                <button 
                                    type="button"
                                    onClick={() => {
                                        setEditingTemplate(null);
                                        editForm.reset();
                                    }}
                                    className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="submit" 
                                    disabled={editForm.processing} 
                                    className="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 disabled:opacity-50"
                                >
                                    {editForm.processing ? 'Updating...' : 'Update Template'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Templates Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {templates.map(template => (
                    <div key={template.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
                        <div className="flex items-start justify-between mb-3">
                            <div className="flex-1">
                                <h3 className="font-semibold text-gray-900 mb-1">{template.name}</h3>
                                <p className="text-xs text-gray-500">{template.role}</p>
                            </div>
                            <div className="flex items-center gap-2">
                                <button
                                    onClick={() => handleToggle(template)}
                                    className={`px-2 py-1 rounded-full text-xs font-medium ${
                                        template.is_active 
                                            ? 'bg-green-100 text-green-700 hover:bg-green-200' 
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                    }`}
                                >
                                    {template.is_active ? 'Published' : 'Draft'}
                                </button>
                            </div>
                        </div>

                        {template.template_type && (
                            <span className="inline-block px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-medium mb-2">
                                {template.template_type}
                            </span>
                        )}

                        {template.description && (
                            <p className="text-xs text-gray-600 line-clamp-2 mb-3">{template.description}</p>
                        )}

                        <div className="flex items-center gap-1 text-xs text-gray-400 mb-4">
                            {template.tone && <span>Tone: {template.tone}</span>}
                            {template.personality && <span> • Personality: {template.personality}</span>}
                        </div>

                        <div className="flex items-center gap-2 pt-3 border-t border-gray-100">
                            <button
                                onClick={() => handleEdit(template)}
                                className="flex-1 px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200 transition-colors"
                            >
                                Edit
                            </button>
                            <button
                                onClick={() => handleDelete(template)}
                                className="flex-1 px-3 py-1.5 text-sm font-medium text-red-600 bg-red-50 rounded hover:bg-red-100 transition-colors"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                ))}

                {templates.length === 0 && (
                    <div className="col-span-3 text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                        <svg className="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p className="text-sm text-gray-500 mb-2">No templates created yet</p>
                        <p className="text-xs text-gray-400 mb-4">Create your first AI employee template to get started</p>
                        <button 
                            onClick={() => setShowCreateForm(true)}
                            className="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700"
                        >
                            Create First Template
                        </button>
                    </div>
                )}
            </div>
        </PlatformLayout>
    );
}