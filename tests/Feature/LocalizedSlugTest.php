<?php

namespace Tests\Feature;

use App\Models\Blog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizedSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_slugs_are_made_for_latin_languages_only_and_never_change(): void
    {
        $blog = Blog::create(['slug' => 'athens-guide', 'active' => false, 'title' => [
            'en' => 'Athens guide', 'de' => 'Athen Reiseführer: Tipps', 'tr' => 'Atina gezi rehberi: İpuçları',
            'ru' => 'Путеводитель по Афинам', 'ja' => 'アテネ旅行ガイド', 'ar' => 'دليل أثينا', 'fr' => 'Athens guide',
        ]]);

        $this->assertSame('athen-reisefuehrer-tipps', $blog->slugs['de']);
        $this->assertSame('atina-gezi-rehberi-ipuclari', $blog->slugs['tr']);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $blog->slugs['ru']);
        $this->assertArrayNotHasKey('ja', $blog->slugs);
        $this->assertArrayNotHasKey('ar', $blog->slugs);
        $this->assertArrayNotHasKey('fr', $blog->slugs); // çevrilmemiş (İngilizceyle aynı)

        $blog->setTranslation('title', 'de', 'Ganz anderer Titel')->save();
        $this->assertSame('athen-reisefuehrer-tipps', $blog->fresh()->slugs['de']);

        $other = Blog::create(['slug' => 'other', 'active' => false, 'title' => ['en' => 'Other', 'de' => 'Athen Reiseführer: Tipps']]);
        $this->assertSame('athen-reisefuehrer-tipps-2', $other->slugs['de']);
    }
}
