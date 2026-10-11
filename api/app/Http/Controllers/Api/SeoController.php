<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SeoSetting;
use Illuminate\Http\JsonResponse;

class SeoController extends Controller
{
    public function settings(): JsonResponse
    {
        $keys = [
            'site_name', 'site_tagline', 'site_description',
            'meta_title_template', 'default_og_image', 'default_locale',
            'twitter_handle', 'facebook_url', 'instagram_url', 'linkedin_url',
            'google_analytics_id', 'plausible_domain', 'umami_website_id',
            'google_site_verification', 'bing_site_verification', 'yandex_verification',
            'robots_index', 'robots_follow', 'robots_max_image_preview',
            'breaking_show_badge', 'breaking_badge_all',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = SeoSetting::get($key);
        }

        return response()->json([
            'data' => $settings,
        ]);
    }
}
