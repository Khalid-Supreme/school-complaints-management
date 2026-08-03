<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_name',
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
     * Update settings.
     */
    public function updateSettings(array $data): self
    {
        $this->fill($data);
        $this->save();

        return $this;
    }
}
