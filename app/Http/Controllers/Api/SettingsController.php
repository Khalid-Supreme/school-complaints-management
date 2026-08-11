<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Support\InputSanitizer;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Get application settings.
     */
    public function index(): JsonResponse
    {
        $settings = Setting::getSettings();

        return response()->json([
            'success' => true,
            'data' => [
                'app_name' => $settings->app_name,
                'contact_email' => $settings->contact_email,
                'contact_phone' => $settings->contact_phone,
                'address' => $settings->address,
                'social_links' => $settings->social_links,
                'meta' => $settings->meta,
            ],
        ]);
    }

    /**
     * Update application settings.
     */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $settings = Setting::getSettings();

        $data = $request->only([
            'app_name',
            'contact_email',
            'contact_phone',
            'address',
            'social_links',
            'meta',
        ]);

        // Sanitize stored text values against active content. Keys explicitly
        // presented as null (empty -> null normalization) are preserved so
        // optional fields can be cleared.
        foreach (['app_name', 'address', 'contact_phone'] as $field) {
            if (array_key_exists($field, $data) && is_string($data[$field])) {
                $data[$field] = InputSanitizer::clean($data[$field]);
            }
        }
        if (isset($data['social_links']) && is_array($data['social_links'])) {
            $data['social_links'] = array_map(fn ($value) => InputSanitizer::clean($value), $data['social_links']);
        }
        if (isset($data['meta']) && is_array($data['meta'])) {
            $data['meta'] = array_map(fn ($value) => InputSanitizer::clean($value), $data['meta']);
        }

        $settings->updateSettings($data);

        $this->auditLogger->log(
            AuditAction::SettingsUpdated,
            $request->user(),
            $settings,
            'Application settings updated',
            ['changed_fields' => array_keys($data)]
        );

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'data' => [
                'app_name' => $settings->app_name,
                'contact_email' => $settings->contact_email,
                'contact_phone' => $settings->contact_phone,
                'address' => $settings->address,
                'social_links' => $settings->social_links,
                'meta' => $settings->meta,
            ],
        ]);
    }
}
