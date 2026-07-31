<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_name',
        'logo_url',
        'favicon_url',
        'contact_email',
        'contact_phone',
        'address',
        'social_links',
        'meta',
    ];

    protected $casts = [
        'social_links' => 'array',
        'meta' => 'array',
    ];

    /**
     * Get the single settings instance (singleton pattern).
     */
    public static function getSettings(): self
    {
        return self::firstOrCreate([], [
            'app_name' => config('app.name', 'SchoolVoice'),
        ]);
    }

    /**
     * Get the full URL for the logo.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($raw = $this->attributes['logo_url'] ?? null) {
            return Storage::url($raw);
        }
        return null;
    }

    /**
     * Get the full URL for the favicon.
     */
    public function getFaviconUrlAttribute(): ?string
    {
        if ($raw = $this->attributes['favicon_url'] ?? null) {
            return Storage::url($raw);
        }
        return null;
    }

    /**
     * Update settings with optional file uploads.
     */
    public function updateSettings(array $data, ?array $files = null): self
    {
        if (isset($files['logo'])) {
            $this->deleteFile($this->attributes['logo_url'] ?? null);
            $this->logo_url = $files['logo']->store('settings', 'public');
        }

        if (isset($files['favicon'])) {
            $this->deleteFile($this->attributes['favicon_url'] ?? null);
            $this->favicon_url = $files['favicon']->store('settings', 'public');
        }

        $this->fill($data);
        $this->save();

        return $this;
    }

    /**
     * Delete a file from storage.
     */
    public function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}