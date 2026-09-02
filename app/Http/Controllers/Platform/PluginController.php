<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Models\PluginVersion;
use App\Services\Plugins\PluginPackageService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PluginController extends Controller
{
    public function __construct(protected PluginPackageService $packages) {}

    public function index()
    {
        $plugins = Plugin::with('versions')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Platform/Plugins', ['plugins' => $plugins]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->pluginRules());

        $plugin = Plugin::create($validated + ['is_published' => false]);

        // Optionally upload the first version package in the same step.
        if ($request->hasFile('package')) {
            $versionData = $request->validate([
                'version' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('plugin_versions', 'version')->where(fn ($q) => $q->where('plugin_id', $plugin->id)),
                ],
                'changelog' => ['nullable', 'string'],
                'package' => [
                    'required',
                    'file',
                    'max:51200',
                    function (string $attribute, mixed $value, \Closure $fail) {
                        if (strtolower($value->getClientOriginalExtension()) !== 'zip') {
                            $fail('The plugin package must be a .zip file.');
                        }
                    },
                ],
            ]);

            $this->packages->uploadVersion($plugin, $versionData['version'], $versionData['package'], $versionData['changelog'] ?? null);

            return back()->with('success', "Plugin '{$plugin->name}' created with version {$versionData['version']}.");
        }

        return back()->with('success', "Plugin '{$plugin->name}' created.");
    }

    public function update(Request $request, Plugin $plugin)
    {
        $plugin->update($request->validate($this->pluginRules($plugin)));

        return back()->with('success', "Plugin '{$plugin->name}' updated.");
    }

    public function destroy(Plugin $plugin)
    {
        $name = $plugin->name;
        $plugin->delete();

        return back()->with('success', "Plugin '{$name}' deleted.");
    }

    public function toggle(Plugin $plugin)
    {
        $plugin->update(['is_published' => ! $plugin->is_published]);

        return back()->with('success', $plugin->is_published ? "Plugin '{$plugin->name}' published." : "Plugin '{$plugin->name}' unpublished.");
    }

    public function storeVersion(Request $request, Plugin $plugin)
    {
        $validated = $request->validate([
            'version' => [
                'required',
                'string',
                'max:50',
                Rule::unique('plugin_versions', 'version')->where(fn ($q) => $q->where('plugin_id', $plugin->id)),
            ],
            'changelog' => ['nullable', 'string'],
            'package' => [
                'required',
                'file',
                'max:51200',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (strtolower($value->getClientOriginalExtension()) !== 'zip') {
                        $fail('The plugin package must be a .zip file.');
                    }
                },
            ],
        ]);

        $this->packages->uploadVersion($plugin, $validated['version'], $validated['package'], $validated['changelog'] ?? null);

        return back()->with('success', "Version {$validated['version']} uploaded.");
    }

    public function publishVersion(PluginVersion $version)
    {
        $this->packages->publish($version);

        return back()->with('success', "Version {$version->version} published.");
    }

    public function deprecateVersion(PluginVersion $version)
    {
        $this->packages->deprecate($version);

        return back()->with('success', "Version {$version->version} deprecated.");
    }

    public function destroyVersion(PluginVersion $version)
    {
        $this->packages->delete($version);

        return back()->with('success', "Version {$version->version} deleted.");
    }

    protected function pluginRules(?Plugin $plugin = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('plugins', 'slug')->ignore($plugin?->id)],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'homepage_url' => ['nullable', 'url', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'target_platform' => ['nullable', 'string', 'max:50'],
            'min_api_version' => ['nullable', 'string', 'max:20'],
            'max_api_version' => ['nullable', 'string', 'max:20'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
