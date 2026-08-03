<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
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
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'sometimes|string|max:255',
            'contact_email' => 'sometimes|nullable|email|max:255',
            'contact_phone' => 'sometimes|nullable|string|max:50',
            'address' => 'sometimes|nullable|string|max:500',
            'social_links' => 'sometimes|nullable|array',
            'social_links.twitter' => 'nullable|string|max:255',
            'social_links.facebook' => 'nullable|string|max:255',
            'social_links.linkedin' => 'nullable|string|max:255',
            'meta' => 'sometimes|nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = Setting::getSettings();

        $data = $request->only([
            'app_name',
            'contact_email',
            'contact_phone',
            'address',
            'social_links',
            'meta',
        ]);

        $settings->updateSettings($data);

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
