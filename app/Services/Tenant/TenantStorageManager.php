<?php

namespace App\Services\Tenant;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class TenantStorageManager
{
    /**
     * Get the tenant-specific filesystem disk instance.
     */
    public function disk(): Filesystem
    {
        return Storage::disk('tenant');
    }

    /**
     * Get the tenant's root storage path.
     */
    public function tenantPath(int $organizationId): string
    {
        return 'org_' . $organizationId;
    }

    /**
     * Store a file in the tenant's isolated directory.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $category  e.g. 'knowledge', 'attachments', 'logos'
     */
    public function store(int $organizationId, $file, string $category, ?string $filename = null): string
    {
        $base = $this->tenantPath($organizationId) . '/' . $category;

        return $file->storeAs($base, $filename ?? $file->hashName(), 'tenant');
    }

    /**
     * Store raw content as a file.
     */
    public function put(int $organizationId, string $category, string $filename, string $content): string
    {
        $path = $this->tenantPath($organizationId) . '/' . $category . '/' . $filename;
        $this->disk()->put($path, $content);

        return $path;
    }

    /**
     * Delete a file from the tenant's storage area.
     */
    public function delete(string $path): bool
    {
        return $this->disk()->delete($path);
    }

    /**
     * Check if a file exists.
     */
    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    /**
     * Get the full local path to a tenant file.
     */
    public function fullPath(string $path): string
    {
        return $this->disk()->path($path);
    }

    /**
     * Get a download/serve response for a tenant file.
     */
    public function download(string $path, ?string $name = null)
    {
        return $this->disk()->download($path, $name);
    }

    /**
     * List all files for a tenant, optionally filtered by category.
     */
    public function listFiles(int $organizationId, ?string $category = null): array
    {
        $prefix = $this->tenantPath($organizationId);
        if ($category) {
            $prefix .= '/' . $category;
        }

        $files = [];
        $allFiles = $this->disk()->allFiles($prefix);

        foreach ($allFiles as $filePath) {
            $files[] = [
                'path' => $filePath,
                'name' => basename($filePath),
                'category' => $this->extractCategory($filePath, $organizationId),
                'size' => $this->disk()->size($filePath),
                'last_modified' => $this->disk()->lastModified($filePath),
                'mime_type' => $this->disk()->mimeType($filePath),
            ];
        }

        return $files;
    }

    /**
     * Get total storage usage in bytes for a tenant.
     */
    public function totalUsage(int $organizationId): int
    {
        $prefix = $this->tenantPath($organizationId);
        $files = $this->disk()->allFiles($prefix);
        $total = 0;

        foreach ($files as $filePath) {
            $total += $this->disk()->size($filePath);
        }

        return $total;
    }

    /**
     * Extract the category from a tenant file path.
     */
    protected function extractCategory(string $path, int $organizationId): string
    {
        $prefix = 'org_' . $organizationId . '/';
        $relative = substr($path, strlen($prefix));
        $parts = explode('/', $relative, 2);

        return $parts[0] ?? 'unknown';
    }

    /**
     * Format bytes to human-readable size.
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}