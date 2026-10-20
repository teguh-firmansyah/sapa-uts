<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Ambil pengaturan umum (info sekolah + logo).
     */
    public function general()
    {
        $settings = Setting::getGroup('general');

        return response()->json([
            'school_name' => $settings['school_name'] ?? 'SAPA',
            'school_address' => $settings['school_address'] ?? '',
            'school_phone' => $settings['school_phone'] ?? '',
            'school_email' => $settings['school_email'] ?? '',
            'logo_url' => isset($settings['logo_path']) ? asset('storage/' . $settings['logo_path']) : null,
        ]);
    }

    /**
     * Update pengaturan umum.
     */
    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'school_name' => ['sometimes', 'string', 'max:150'],
            'school_address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'school_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'school_email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'logo' => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png,svg', 'max:1024'],
        ]);

        foreach (['school_name', 'school_address', 'school_phone', 'school_email'] as $field) {
            if (array_key_exists($field, $validated)) {
                Setting::set($field, $validated[$field] ?? '', 'general');
            }
        }

        if ($request->hasFile('logo')) {
            $oldPath = Setting::get('logo_path');
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('logo')->store('settings', 'public');
            Setting::set('logo_path', $path, 'general');
        }

        return response()->json([
            'message' => 'Pengaturan umum berhasil diperbarui.',
        ]);
    }

    public function appearance()
    {
        $settings = Setting::getGroup('appearance');

        return response()->json([
            'default_theme' => $settings['default_theme'] ?? 'dark',
            'allow_user_override' => ($settings['allow_user_override'] ?? '1') === '1',
        ]);
    }

    public function updateAppearance(Request $request)
    {
        $validated = $request->validate([
            'default_theme' => ['required', 'in:dark,light'],
            'allow_user_override' => ['required', 'boolean'],
        ]);

        Setting::set('default_theme', $validated['default_theme'], 'appearance');
        Setting::set('allow_user_override', $validated['allow_user_override'] ? '1' : '0', 'appearance');

        return response()->json(['message' => 'Pengaturan tampilan berhasil diperbarui.']);
    }

    public function seo()
    {
        $settings = Setting::getGroup('seo');

        return response()->json([
            'meta_title' => $settings['meta_title'] ?? 'SAPA — Sistem Aspirasi dan Pengaduan Sekolah',
            'meta_description' => $settings['meta_description'] ?? 'Platform pelaporan aspirasi, fasilitas, dan pengaduan bullying bagi warga sekolah.',
            'meta_keywords' => $settings['meta_keywords'] ?? 'SAPA, aspirasi sekolah, pengaduan sekolah, laporan bullying',
            'og_image_url' => isset($settings['og_image_path']) ? asset('storage/' . $settings['og_image_path']) : null,
            'favicon_url' => isset($settings['favicon_path']) ? asset('storage/' . $settings['favicon_path']) : null,
        ]);
    }

    public function updateSeo(Request $request)
    {
        $validated = $request->validate([
            'meta_title' => ['sometimes', 'string', 'max:70'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:160'],
            'meta_keywords' => ['sometimes', 'nullable', 'string', 'max:255'],
            'og_image' => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png', 'max:1024'],
            'favicon' => ['sometimes', 'nullable', 'file', 'mimes:ico,png', 'max:512'],
        ]);

        foreach (['meta_title', 'meta_description', 'meta_keywords'] as $field) {
            if (array_key_exists($field, $validated)) {
                Setting::set($field, $validated[$field] ?? '', 'seo');
            }
        }

        if ($request->hasFile('og_image')) {
            $old = Setting::get('og_image_path');
            if ($old) Storage::disk('public')->delete($old);
            $path = $request->file('og_image')->store('settings', 'public');
            Setting::set('og_image_path', $path, 'seo');
        }

        if ($request->hasFile('favicon')) {
            $old = Setting::get('favicon_path');
            if ($old) Storage::disk('public')->delete($old);
            $path = $request->file('favicon')->store('settings', 'public');
            Setting::set('favicon_path', $path, 'seo');
        }

        return response()->json(['message' => 'Pengaturan SEO berhasil diperbarui.']);
    }
}
