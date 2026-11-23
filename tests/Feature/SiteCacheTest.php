<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_change_refreshes_site_page_cache_but_quiet_settings_do_not(): void
    {
        Setting::put('pinterest_enabled', '1');
        $this->assertNull(Setting::get('page_cache_version')); // sessiz ayar: site önbelleği tazelenmez

        Announcement::create(['text' => ['en' => 'Hello'], 'active' => true]);
        $this->assertNotNull(Setting::get('page_cache_version')); // testte konsol: hemen yazılır
    }
}
