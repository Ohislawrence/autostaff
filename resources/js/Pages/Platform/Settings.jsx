import { usePage } from '@inertiajs/react';
import PlatformLayout from '@/Layouts/PlatformLayout';

export default function Settings({ settings }) {
    return (
        <PlatformLayout title="Settings">
            <h1 className="text-2xl font-bold text-gray-900 mb-6">Platform Settings</h1>
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 max-w-2xl">
                <div className="space-y-4">
                    <div><label className="block text-sm font-medium text-gray-700">Application Name</label><input type="text" defaultValue={settings.app_name} className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50" readOnly /></div>
                    <div><label className="block text-sm font-medium text-gray-700">Application URL</label><input type="text" defaultValue={settings.app_url} className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50" readOnly /></div>
                    <div><label className="block text-sm font-medium text-gray-700">AI Model</label><input type="text" defaultValue={settings.deepseek_model} className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50" readOnly /></div>
                    <div className="pt-4 border-t border-gray-200"><p className="text-sm text-gray-500">Platform settings are managed via environment variables. Edit your <code className="bg-gray-100 px-1 rounded">.env</code> file to change these values.</p></div>
                </div>
            </div>
        </PlatformLayout>
    );
}