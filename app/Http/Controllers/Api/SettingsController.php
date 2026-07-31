<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
                'logo_url' => $settings->logo_url,
                'favicon_url' => $settings->favicon_url,
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

        // Handle file uploads
        $files = [];
        if ($request->hasFile('logo')) {
            $request->validate([
                'logo' => 'image|mimes:png,jpg,jpeg,svg|max:2048',
            ]);
            $files['logo'] = $request->file('logo');
        }

        if ($request->hasFile('favicon')) {
            $request->validate([
                'favicon' => 'image|mimes:png,ico|max:512',
            ]);
            $files['favicon'] = $request->file('favicon');
        }

        $data = $request->only([
            'app_name',
            'contact_email',
            'contact_phone',
            'address',
            'social_links',
            'meta',
        ]);

        $settings->updateSettings($data, $files);

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'data' => [
                'app_name' => $settings->app_name,
                'logo_url' => $settings->logo_url,
                'favicon_url' => $settings->favicon_url,
                'contact_email' => $settings->contact_email,
                'contact_phone' => $settings->contact_phone,
                'address' => $settings->address,
                'social_links' => $settings->social_links,
                'meta' => $settings->meta,
            ],
        ]);
    }

    /**
     * Upload logo only.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $settings = Setting::getSettings();
        $settings->deleteFile($settings->getAttributes()['logo_url'] ?? null);
        $settings->logo_url = $request->file('logo')->store('settings', 'public');
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Logo uploaded successfully.',
            'data' => ['logo_url' => $settings->logo_url],
        ]);
    }

    /**
     * Upload favicon only.
     */
    public function uploadFavicon(Request $request): JsonResponse
    {
        $request->validate([
            'favicon' => 'required|mimes:png,ico|max:512',
        ]);

        $settings = Setting::getSettings();
        $settings->deleteFile($settings->getAttributes()['favicon_url'] ?? null);
        $settings->favicon_url = $request->file('favicon')->store('settings', 'public');
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Favicon uploaded successfully.',
            'data' => ['favicon_url' => $settings->favicon_url],
        ]);
    }

    /**
     * Remove logo.
     */
    public function removeLogo(Request $request): JsonResponse
    {
        $settings = Setting::getSettings();
        $settings->deleteFile($settings->getAttributes()['logo_url'] ?? null);
        $settings->logo_url = null;
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Logo removed successfully.',
        ]);
    }

    /**
     * Remove favicon.
     */
    public function removeFavicon(Request $request): JsonResponse
    {
        $settings = Setting::getSettings();
        $settings->deleteFile($settings->getAttributes()['favicon_url'] ?? null);
        $settings->favicon_url = null;
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Favicon removed successfully.',
        ]);
    }
}